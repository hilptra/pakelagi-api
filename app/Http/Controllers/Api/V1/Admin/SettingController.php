<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWhatsAppSettingRequest;
use App\Models\Setting;
use App\Services\FrontendCache;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Admin - Pengaturan')]
class SettingController extends Controller
{
    /**
     * Ambil nomor WhatsApp admin saat ini
     */
    public function getWhatsApp(): JsonResponse
    {
        $number = Setting::getValue('admin_whatsapp');

        return response()->json([
            'data' => [
                'whatsapp_number' => $number,
                'whatsapp_url' => $number ? "https://wa.me/{$number}" : null,
            ],
        ]);
    }

    /**
     * Perbarui nomor WhatsApp admin
     */
    public function updateWhatsApp(
        UpdateWhatsAppSettingRequest $request,
        FrontendCache $frontendCache,
    ): JsonResponse {
        $normalized = $request->normalizedWhatsAppNumber();

        Setting::setValue('admin_whatsapp', $normalized);
        $frontendCache->settings();

        return response()->json([
            'data' => [
                'whatsapp_number' => $normalized,
                'whatsapp_url' => "https://wa.me/{$normalized}",
            ],
            'message' => 'Nomor WhatsApp admin berhasil diperbarui.',
        ]);
    }
}

