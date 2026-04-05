<?php

namespace Tests\Feature;

use App\Enums\AiResponseStyle;
use App\Enums\AiTone;
use App\Enums\SalesApproach;
use App\Jobs\GenerateInboundAiReplyJob;
use App\Models\AiConfig;
use App\Models\AiPersonality;
use App\Models\AiRestriction;
use App\Models\AiSalesman;
use App\Models\Business;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InboundAiAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.webhook_verify_token' => 'verify-secret',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.webhook_skip_signature' => false,
            'services.outbound.skip_network' => true,
        ]);
    }

    private function seedActiveAiConfig(User $user, string $greetingMessage = 'AI says hello'): AiConfig
    {
        $business = Business::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Business',
            'description' => 'A test business for AI auto-reply.',
            'website' => 'https://example.com',
            'timezone' => 'UTC',
        ]);

        $personality = AiPersonality::query()->create([
            'tone' => AiTone::PROFESSIONAL,
            'response_style' => AiResponseStyle::BALANCED,
            'language' => 'en',
            'greeting_message' => $greetingMessage,
            'farewell_message' => null,
            'custom_instructions' => null,
        ]);

        $restrictions = AiRestriction::query()->create([
            'allowed_topics' => null,
            'restricted_topics' => null,
            'blocked_words' => null,
            'max_response_length' => null,
            'content_guidelines' => null,
        ]);

        $salesman = AiSalesman::query()->create([
            'sales_approach' => SalesApproach::CONSULTATIVE,
            'upsell_enabled' => false,
        ]);

        return AiConfig::query()->create([
            'business_id' => $business->id,
            'ai_personality_id' => $personality->id,
            'ai_restrictions_id' => $restrictions->id,
            'ai_salesman_id' => $salesman->id,
            'is_active' => true,
        ]);
    }

    public function test_meta_webhook_does_not_queue_ai_job_when_auto_reply_disabled(): void
    {
        config(['ai.auto_reply.enabled' => false]);
        Queue::fake();

        $user = User::factory()->create();
        $this->seedActiveAiConfig($user);

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

        Queue::assertNotPushed(GenerateInboundAiReplyJob::class);
    }

    public function test_meta_webhook_queues_ai_reply_job_when_auto_reply_enabled(): void
    {
        config(['ai.auto_reply.enabled' => true]);
        Queue::fake();

        $user = User::factory()->create();
        $this->seedActiveAiConfig($user);

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
                                'mid' => 'm_ai_queue_mid',
                                'text' => 'hello queue',
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

        $inboundId = (int) Message::query()->where('platform_message_id', 'm_ai_queue_mid')->value('id');
        $this->assertGreaterThan(0, $inboundId);

        Queue::assertPushed(GenerateInboundAiReplyJob::class, function (GenerateInboundAiReplyJob $job) use ($inboundId): bool {
            return $job->messageId === $inboundId;
        });
    }

    public function test_generate_inbound_ai_reply_job_sends_and_persists_outbound_ai_message(): void
    {
        config([
            'ai.auto_reply.enabled' => true,
            'services.outbound.skip_network' => true,
        ]);

        $user = User::factory()->create();
        $this->seedActiveAiConfig($user, 'Thanks from our AI team.');

        $channel = Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'facebook',
            'name' => 'Page',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => 'P1',
            'meta_access_token' => 'tok',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $conversation = Conversation::query()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'title' => null,
            'status' => 'open',
            'platform_conversation_id' => 'PSID-42',
            'metadata' => null,
        ]);

        $inbound = Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'platform_message_id' => 'in-1',
            'text' => 'Customer question',
            'is_from_user' => false,
            'direction' => 'in',
            'sender_type' => 'customer',
            'raw_payload' => null,
            'attachments' => null,
        ]);

        $job = new GenerateInboundAiReplyJob($inbound->id);
        $this->app->call([$job, 'handle']);

        $this->assertSame(2, Message::query()->where('conversation_id', $conversation->id)->count());

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'sender_type' => 'ai',
            'text' => 'Thanks from our AI team.',
        ]);
    }
}
