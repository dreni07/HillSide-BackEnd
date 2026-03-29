<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookIngressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.webhook_verify_token' => 'verify-secret',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.webhook_skip_signature' => false,
        ]);
    }

    public function test_meta_webhook_verify_returns_hub_challenge(): void
    {
        $response = $this->get('/api/webhooks/meta?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'verify-secret',
            'hub.challenge' => 'CHALLENGE_OK',
        ]));

        $response->assertStatus(200);
        $response->assertSee('CHALLENGE_OK', false);
    }

    public function test_meta_webhook_verify_rejects_wrong_token(): void
    {
        $response = $this->get('/api/webhooks/meta?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong',
            'hub.challenge' => 'x',
        ]));

        $response->assertStatus(403);
    }

    public function test_meta_webhook_post_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/webhooks/meta', ['object' => 'page', 'entry' => []], [
            'X-Hub-Signature-256' => 'sha256=deadbeef',
        ]);

        $response->assertStatus(403);
    }

    public function test_meta_webhook_post_ingests_facebook_text_message_idempotently(): void
    {
        $user = User::factory()->create();

        Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'facebook',
            'name' => 'Test Page',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => 'PAGE123',
            'meta_access_token' => 'page-token',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'PAGE123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'PSID999'],
                            'recipient' => ['id' => 'PAGE123'],
                            'timestamp' => 1_700_000_000,
                            'message' => [
                                'mid' => 'm_unique_mid',
                                'text' => 'hello from customer',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = 'sha256='.hash_hmac('sha256', $raw, 'app-secret');

        $this->call('POST', '/api/webhooks/meta', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $sig,
        ], $raw)->assertOk();

        $this->assertSame(1, Message::query()->count());
        $this->assertDatabaseHas('messages', [
            'sender_type' => 'customer',
            'direction' => 'in',
        ]);

        $this->assertSame(1, Contact::query()->count());
        $this->assertSame(1, ContactIdentity::query()->count());
        $this->assertDatabaseHas('contact_identities', [
            'channel_id' => Channel::query()->where('meta_page_id', 'PAGE123')->value('id'),
            'platform_user_id' => 'PSID999',
        ]);
        $conversation = Conversation::query()->first();
        $this->assertNotNull($conversation);
        $this->assertNotNull($conversation->contact_id);
        $this->assertSame(
            $conversation->contact_id,
            ContactIdentity::query()->value('contact_id')
        );

        $this->call('POST', '/api/webhooks/meta', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $sig,
        ], $raw)->assertOk();

        $this->assertSame(1, Message::query()->count());
    }

    public function test_viber_webhook_post_ingests_text_with_valid_signature(): void
    {
        $user = User::factory()->create();

        Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'viber',
            'name' => 'Bot',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => null,
            'meta_access_token' => 'viber-auth-token',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => 'botPaId1',
            'webhook_verify_token' => null,
        ]);

        $payload = [
            'event' => 'message',
            'timestamp' => 1_700_000_000,
            'message_token' => 55_555,
            'sender' => ['id' => 'viberUser1', 'name' => 'U'],
            'receiver' => 'botPaId1',
            'message' => [
                'type' => 'text',
                'text' => 'hi viber',
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $raw, 'viber-auth-token');

        $this->call('POST', '/api/webhooks/viber', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_VIBER_CONTENT_SIGNATURE' => $sig,
        ], $raw)->assertOk();

        $this->assertSame(1, Message::query()->count());
        $this->assertDatabaseHas('messages', [
            'sender_type' => 'customer',
            'direction' => 'in',
        ]);

        $this->assertDatabaseHas('contacts', [
            'name' => 'U',
        ]);
        $this->assertDatabaseHas('contact_identities', [
            'platform_user_id' => 'viberUser1',
        ]);
        $this->assertNotNull(Conversation::query()->value('contact_id'));
    }
}
