<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConversationInboxOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversations_index_is_ordered_by_last_message_newest_first(): void
    {
        $user = User::factory()->create();
        $token = Jwt::issueForUser($user)['token'];

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

        $older = Conversation::query()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'title' => 'Old thread',
            'status' => 'open',
            'platform_conversation_id' => 'PSID-OLD',
            'metadata' => null,
        ]);

        $newer = Conversation::query()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'title' => 'New thread',
            'status' => 'open',
            'platform_conversation_id' => 'PSID-NEW',
            'metadata' => null,
        ]);

        $tOld = now()->subHours(2);
        $tNew = now();
        DB::table('messages')->insert([
            [
                'conversation_id' => $older->id,
                'user_id' => null,
                'platform_message_id' => 'm-old',
                'text' => 'old',
                'is_from_user' => 0,
                'direction' => 'in',
                'sender_type' => 'customer',
                'raw_payload' => null,
                'attachments' => null,
                'created_at' => $tOld,
                'updated_at' => $tOld,
            ],
            [
                'conversation_id' => $newer->id,
                'user_id' => null,
                'platform_message_id' => 'm-new',
                'text' => 'new',
                'is_from_user' => 0,
                'direction' => 'in',
                'sender_type' => 'customer',
                'raw_payload' => null,
                'attachments' => null,
                'created_at' => $tNew,
                'updated_at' => $tNew,
            ],
        ]);

        $response = $this->getJson('/api/conversations', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('_id')->all();
        $this->assertSame([(string) $newer->id, (string) $older->id], $ids);
    }
}
