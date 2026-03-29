<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OutboundSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.outbound.skip_network' => false]);
    }

    public function test_facebook_reply_calls_meta_me_messages_and_stores_platform_id(): void
    {
        Http::fake([
            '*graph.facebook.com*' => Http::response([
                'recipient_id' => 'PSID-1',
                'message_id' => 'mid.graph.test',
            ], 200),
        ]);

        $user = User::factory()->create();
        $channel = Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'facebook',
            'name' => 'Page',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => 'PAGE1',
            'meta_access_token' => 'page-token-xyz',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $conversation = Conversation::query()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'title' => null,
            'status' => 'open',
            'platform_conversation_id' => 'PSID-1',
            'metadata' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->postJson(
            '/api/conversations/'.$conversation->id.'/messages',
            ['text' => 'Hello customer'],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.platformMessageId', 'mid.graph.test');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com')
                && str_contains($request->url(), '/me/messages')
                && $request['recipient']['id'] === 'PSID-1'
                && $request['messaging_type'] === 'RESPONSE'
                && $request['message']['text'] === 'Hello customer';
        });

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'platform_message_id' => 'mid.graph.test',
            'direction' => 'out',
        ]);
    }

    public function test_meta_token_error_returns_channel_token_invalid(): void
    {
        Http::fake([
            '*graph.facebook.com*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 400),
        ]);

        $user = User::factory()->create();
        $channel = Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'instagram',
            'name' => 'IG',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => 'IG123',
            'meta_access_token' => 'bad',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $conversation = Conversation::query()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'title' => null,
            'status' => 'open',
            'platform_conversation_id' => 'IGSID-9',
            'metadata' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->postJson(
            '/api/conversations/'.$conversation->id.'/messages',
            ['text' => 'Hi'],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertStatus(502);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('errorCode', 'channel_token_invalid');
        $this->assertDatabaseCount('messages', 0);

        $channel->refresh();
        $this->assertNotNull($channel->connection_error);
        $this->assertNotNull($channel->connection_error_at);
        $this->assertSame('channel_token_invalid', $channel->connection_error_code);
    }

    public function test_whatsapp_reply_uses_phone_number_id_path(): void
    {
        Http::fake([
            '*graph.facebook.com*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        $user = User::factory()->create();
        $channel = Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'whatsapp',
            'name' => 'WA',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => null,
            'meta_access_token' => 'wa-system-token',
            'whatsapp_phone_number_id' => 'PHONE_NUM_ID',
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $conversation = Conversation::query()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'title' => null,
            'status' => 'open',
            'platform_conversation_id' => '355691234567',
            'metadata' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->postJson(
            '/api/conversations/'.$conversation->id.'/messages',
            ['text' => 'WA hello'],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.platformMessageId', 'wamid.test');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'PHONE_NUM_ID/messages')
                && $request->hasHeader('Authorization')
                && ($request['to'] ?? null) === '355691234567'
                && ($request['type'] ?? null) === 'text';
        });
    }

    public function test_viber_reply_posts_to_chatapi(): void
    {
        Http::fake([
            '*chatapi.viber.com*' => Http::response([
                'status' => 0,
                'status_message' => 'ok',
                'message_token' => 998877,
            ], 200),
        ]);

        $user = User::factory()->create();
        $channel = Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'viber',
            'name' => 'Bot',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => null,
            'meta_access_token' => 'viber-secret',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => 'bot1',
            'webhook_verify_token' => null,
        ]);

        $conversation = Conversation::query()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'title' => null,
            'status' => 'open',
            'platform_conversation_id' => 'viberUserX',
            'metadata' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->postJson(
            '/api/conversations/'.$conversation->id.'/messages',
            ['text' => 'Viber hi'],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.platformMessageId', '998877');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'chatapi.viber.com')
                && ($request['receiver'] ?? null) === 'viberUserX'
                && ($request['type'] ?? null) === 'text';
        });
    }
}
