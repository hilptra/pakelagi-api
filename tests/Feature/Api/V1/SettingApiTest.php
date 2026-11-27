<?php

namespace Tests\Feature\Api\V1;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

