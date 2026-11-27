<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Pengaturan')]
class SettingController extends Controller
{
    /**
     * Ambil pengaturan WhatsApp publik untuk website
     */
    public function whatsapp(): JsonResponse
    {
        $number = Setting::getValue('admin_whatsapp');

        return response()->json([
            'data' => [
                'whatsapp_number' => $number,
                'whatsapp_url' => $number ? "https://wa.me/{$number}" : null,
            ],
        ]);
    }
}

