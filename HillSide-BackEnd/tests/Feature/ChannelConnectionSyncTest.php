<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChannelConnectionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_connection_updates_meta_token_expiry_from_debug_token(): void
    {
        $expires = now()->addDays(14)->timestamp;

        Http::fake([
            '*graph.facebook.com*' => Http::response([
                'data' => [
                    'is_valid' => true,
                    'expires_at' => $expires,
                ],
            ], 200),
        ]);

        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'secret',
            'services.meta.graph_base_url' => 'https://graph.facebook.com/v21.0',
        ]);

        $user = User::factory()->create();
        $channel = Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'facebook',
            'name' => 'Page',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => 'P1',
            'meta_access_token' => 'page-token',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->postJson(
            '/api/channels/'.$channel->id.'/sync-connection',
            [],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.tokenStatus', 'valid');

        $channel->refresh();
        $this->assertNotNull($channel->meta_token_expires_at);
        $this->assertSame($expires, $channel->meta_token_expires_at->timestamp);
    }

    public function test_sync_connection_registers_viber_webhook(): void
    {
        Http::fake([
            '*chatapi.viber.com/pa/set_webhook*' => Http::response([
                'status' => 0,
                'status_message' => 'ok',
            ], 200),
        ]);

        config([
            'services.integrations.public_api_url' => 'https://api.example.test',
        ]);

        $user = User::factory()->create();
        $channel = Channel::query()->create([
            'user_id' => $user->id,
            'platform' => 'viber',
            'name' => 'Bot',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => null,
            'meta_access_token' => 'viber-auth',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => 'b1',
            'webhook_verify_token' => 'verify-x',
        ]);

        $token = Jwt::issueForUser($user)['token'];

        $response = $this->postJson(
            '/api/channels/'.$channel->id.'/sync-connection',
            [],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertOk();
        $response->assertJsonPath('data.tokenStatus', 'valid');

        $channel->refresh();
        $this->assertNotNull($channel->viber_webhook_registered_at);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'set_webhook')) {
                return false;
            }
            $decoded = json_decode($request->body(), true);

            return is_array($decoded) && ($decoded['url'] ?? null) === 'https://api.example.test/api/webhooks/viber';
        });
    }

    public function test_sync_connection_forbidden_for_other_user_channel(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $channel = Channel::query()->create([
            'user_id' => $owner->id,
            'platform' => 'facebook',
            'name' => 'Page',
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => 'P1',
            'meta_access_token' => 't',
            'whatsapp_phone_number_id' => null,
            'viber_bot_id' => null,
            'webhook_verify_token' => null,
        ]);

        $token = Jwt::issueForUser($other)['token'];

        $this->postJson(
            '/api/channels/'.$channel->id.'/sync-connection',
            [],
            ['Authorization' => 'Bearer '.$token]
        )->assertForbidden();
    }
}
