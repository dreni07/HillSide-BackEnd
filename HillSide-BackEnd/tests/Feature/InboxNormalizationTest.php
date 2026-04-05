<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.outbound.skip_network' => true]);
    }

    public function test_thread_endpoint_returns_conversation_and_messages_shape(): void
    {
        $user = User::factory()->create();

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
            'title' => 'Klient VIP',
            'status' => 'open',
            'platform_conversation_id' => 'PSID-1',
            'metadata' => ['foo' => 'bar'],
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'platform_message_id' => 'm1',
            'text' => 'Hi',
            'is_from_user' => false,
            'direction' => 'in',
            'sender_type' => 'customer',
            'raw_payload' => ['source' => 'test'],
            'attachments' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->getJson(
            '/api/conversations/'.$conversation->id.'/messages',
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => [
                'conversation' => [
                    '_id',
                    'title',
                    'status',
                    'platformUserId',
                    'platformConversationId',
                    'lastMessageAt',
                    'lastUserMessageAt',
                    'metadata',
                    'contactId',
                ],
                'messages' => [
                    [
                        '_id',
                        'direction',
                        'content' => ['text'],
                        'timestamp',
                        'platformMessageId',
                        'senderType',
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('data.conversation.title', 'Klient VIP');
        $response->assertJsonPath('data.conversation.status', 'open');
        $response->assertJsonPath('data.conversation.platformUserId', 'PSID-1');
        $response->assertJsonPath('data.conversation.platformConversationId', 'PSID-1');
        $response->assertJsonPath('data.conversation.metadata.foo', 'bar');
        $response->assertJsonPath('data.messages.0.direction', 'in');
        $response->assertJsonPath('data.messages.0.senderType', 'customer');
        $response->assertJsonPath('data.messages.0.content.text', 'Hi');
        $response->assertJsonPath('data.conversation.contactId', null);
    }

    public function test_thread_endpoint_includes_contact_payload_when_linked(): void
    {
        $user = User::factory()->create();

        $channel = Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'whatsapp',
            'name' => 'WA',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => null,
            'meta_access_token' => 'tok',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $contact = Contact::query()->create([
            'user_id' => $user->id,
            'name' => 'Jane Doe',
            'email' => null,
            'phone' => '+15550001',
            'notes' => null,
        ]);

        $conversation = Conversation::query()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'title' => null,
            'status' => 'open',
            'platform_conversation_id' => '+15550001',
            'metadata' => null,
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'platform_message_id' => 'm1',
            'text' => 'Hi',
            'is_from_user' => false,
            'direction' => 'in',
            'sender_type' => 'customer',
            'raw_payload' => null,
            'attachments' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->getJson(
            '/api/conversations/'.$conversation->id.'/messages',
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertOk();
        $response->assertJsonPath('data.conversation.contactId._id', (string) $contact->id);
        $response->assertJsonPath('data.conversation.contactId.name', 'Jane Doe');
        $response->assertJsonPath('data.conversation.contactId.phone', '+15550001');
    }

    public function test_store_message_persists_outbound_human_agent(): void
    {
        $user = User::factory()->create();

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
            'platform_conversation_id' => 'PSID-1',
            'metadata' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->postJson(
            '/api/conversations/'.$conversation->id.'/messages',
            ['text' => 'Reply from CRM'],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.direction', 'out');
        $response->assertJsonPath('data.senderType', 'human_agent');
        $response->assertJsonPath('data.content.text', 'Reply from CRM');
        $this->assertStringStartsWith('dev_skip:', (string) $response->json('data.platformMessageId'));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'text' => 'Reply from CRM',
            'direction' => 'out',
            'sender_type' => 'human_agent',
            'is_from_user' => 1,
        ]);
    }

    public function test_store_message_accepts_sender_type_ai(): void
    {
        $user = User::factory()->create();

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
            'platform_conversation_id' => 'PSID-1',
            'metadata' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->postJson(
            '/api/conversations/'.$conversation->id.'/messages',
            ['text' => 'AI says hi', 'senderType' => 'ai'],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.senderType', 'ai');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
        ]);
    }
}
