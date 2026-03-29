<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelOwnerCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_update_client_channel_access_token(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false]);

        $channel = Channel::query()->create([
            'user_id' => $client->id,
            'platform' => 'facebook',
            'name' => 'Page',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => 'P1',
            'meta_access_token' => 'original-token',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $token = Jwt::issueForUser($admin)['token'];

        $response = $this->putJson(
            '/api/channels/'.$channel->id,
            ['accessToken' => 'hijack-attempt'],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertStatus(403);

        $this->assertSame('original-token', $channel->fresh()->meta_access_token);
    }

    public function test_owner_can_update_access_token_and_clears_connection_error(): void
    {
        $client = User::factory()->create(['is_admin' => false]);

        $channel = Channel::query()->create([
            'user_id' => $client->id,
            'platform' => 'facebook',
            'name' => 'Page',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => 'P1',
            'meta_access_token' => 'old',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
            'connection_error' => 'bad',
            'connection_error_code' => 'channel_token_invalid',
            'connection_error_at' => now(),
        ]);

        $token = Jwt::issueForUser($client)['token'];

        $response = $this->putJson(
            '/api/channels/'.$channel->id,
            ['accessToken' => 'new-valid-token'],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.connectionError', null);

        $channel->refresh();
        $this->assertSame('new-valid-token', $channel->meta_access_token);
        $this->assertNull($channel->connection_error);
        $this->assertNull($channel->connection_error_code);
        $this->assertNull($channel->connection_error_at);
    }
}
