<?php

namespace Tests\Feature\Api\V1;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class SettingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_fetch_whatsapp_setting(): void
    {
        Setting::setValue('admin_whatsapp', '6289501085225');

        $this->getJson('/api/v1/settings/whatsapp')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6289501085225',
                    'whatsapp_url' => 'https://wa.me/6289501085225',
                ],
            ]);
    }

    public function test_public_gets_null_if_whatsapp_setting_not_set(): void
    {
        $this->getJson('/api/v1/settings/whatsapp')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => null,
                    'whatsapp_url' => null,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_or_update_admin_setting(): void
    {
        $this->getJson('/api/v1/admin/settings/whatsapp')
            ->assertUnauthorized();

        $this->putJson('/api/v1/admin/settings/whatsapp', [
            'whatsapp_number' => '081234567890',
        ])->assertUnauthorized();
    }

    public function test_admin_can_read_and_update_whatsapp_setting_with_normalization(): void
    {
        $admin = User::factory()->create();

        // 1. Input dengan format lokal 08...
        $this->actingAs($admin)
            ->putJson('/api/v1/admin/settings/whatsapp', [
                'whatsapp_number' => '0812-3456-7890',
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6281234567890',
                    'whatsapp_url' => 'https://wa.me/6281234567890',
                ],
            ]);

        $this->assertSame('6281234567890', Setting::getValue('admin_whatsapp'));

        // 2. Input dengan format internasional +62...
        $this->actingAs($admin)
            ->putJson('/api/v1/admin/settings/whatsapp', [
                'whatsapp_number' => '+62 899-1234-5678',
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6289912345678',
                    'whatsapp_url' => 'https://wa.me/6289912345678',
                ],
            ]);

        // 3. Admin read
        $this->actingAs($admin)
            ->getJson('/api/v1/admin/settings/whatsapp')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6289912345678',
                    'whatsapp_url' => 'https://wa.me/6289912345678',
                ],
            ]);
    }

    public function test_invalid_whatsapp_number_is_rejected(): void
    {
        $admin = User::factory()->create();

        // Karakter huruf
        $this->actingAs($admin)
            ->putJson('/api/v1/admin/settings/whatsapp', [
                'whatsapp_number' => '0812abcd3456',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp_number');

        // Terlalu pendek
        $this->actingAs($admin)
            ->putJson('/api/v1/admin/settings/whatsapp', [
                'whatsapp_number' => '08123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp_number');

        // Kosong
        $this->actingAs($admin)
            ->putJson('/api/v1/admin/settings/whatsapp', [
                'whatsapp_number' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp_number');
    }

    public function test_public_endpoint_falls_back_to_database_when_cache_read_fails(): void
    {
        Setting::updateOrCreate(
            ['key' => 'admin_whatsapp'],
            ['value' => '6289501085225']
        );

        Cache::shouldReceive('get')
            ->once()
            ->with('settings:admin_whatsapp')
            ->andThrow(new RuntimeException('Cache read failed'));

        Cache::shouldReceive('put')
            ->once()
            ->with('settings:admin_whatsapp', '6289501085225', 3600)
            ->andReturn(true);

        Log::shouldReceive('warning')->once();

        $this->getJson('/api/v1/settings/whatsapp')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6289501085225',
                    'whatsapp_url' => 'https://wa.me/6289501085225',
                ],
            ]);
    }

    public function test_public_endpoint_returns_database_value_when_cache_write_fails(): void
    {
        Setting::updateOrCreate(
            ['key' => 'admin_whatsapp'],
            ['value' => '6289501085225']
        );

        Cache::shouldReceive('get')
            ->once()
            ->with('settings:admin_whatsapp')
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->with('settings:admin_whatsapp', '6289501085225', 3600)
            ->andThrow(new RuntimeException('Cache write failed'));

        Log::shouldReceive('warning')->once();

        $this->getJson('/api/v1/settings/whatsapp')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6289501085225',
                    'whatsapp_url' => 'https://wa.me/6289501085225',
                ],
            ]);
    }

    public function test_public_endpoint_works_when_both_cache_read_and_write_fail(): void
    {
        Setting::updateOrCreate(
            ['key' => 'admin_whatsapp'],
            ['value' => '6289501085225']
        );

        Cache::shouldReceive('get')
            ->once()
            ->with('settings:admin_whatsapp')
            ->andThrow(new RuntimeException('Cache store unavailable'));

        Cache::shouldReceive('put')
            ->once()
            ->with('settings:admin_whatsapp', '6289501085225', 3600)
            ->andThrow(new RuntimeException('Cache store unavailable'));

        Log::shouldReceive('warning')->twice();

        $this->getJson('/api/v1/settings/whatsapp')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6289501085225',
                    'whatsapp_url' => 'https://wa.me/6289501085225',
                ],
            ]);
    }

    public function test_admin_update_succeeds_and_reads_latest_value_when_cache_invalidation_fails(): void
    {
        $admin = User::factory()->create();

        Setting::updateOrCreate(
            ['key' => 'admin_whatsapp'],
            ['value' => '6281111111111']
        );

        Cache::shouldReceive('forget')
            ->once()
            ->with('settings:admin_whatsapp')
            ->andThrow(new RuntimeException('Cache invalidation failed'));

        Cache::shouldReceive('get')
            ->once()
            ->with('settings:admin_whatsapp')
            ->andThrow(new RuntimeException('Cache store unavailable'));

        Cache::shouldReceive('put')
            ->once()
            ->with('settings:admin_whatsapp', '6282222222222', 3600)
            ->andThrow(new RuntimeException('Cache store unavailable'));

        Log::shouldReceive('warning')->times(3);

        $this->actingAs($admin)
            ->putJson('/api/v1/admin/settings/whatsapp', [
                'whatsapp_number' => '0822-2222-2222',
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6282222222222',
                    'whatsapp_url' => 'https://wa.me/6282222222222',
                ],
            ]);

        $this->getJson('/api/v1/settings/whatsapp')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'whatsapp_number' => '6282222222222',
                    'whatsapp_url' => 'https://wa.me/6282222222222',
                ],
            ]);
    }
}

