<?php

namespace App\Support;

use InvalidArgumentException;

class PhoneNumber
{
    /**
     * Menormalkan nomor telepon / WhatsApp menjadi format internasional standar (misal: 6281234567890).
     *
     * Format yang didukung:
     * - 08xxxxxxxxxx -> 628xxxxxxxxxx
     * - 628xxxxxxxxxx -> 628xxxxxxxxxx
     * - +628xxxxxxxxxx -> 628xxxxxxxxxx
     * - 8xxxxxxxxxx -> 628xxxxxxxxxx
     *
     * @throws InvalidArgumentException jika nomor tidak valid
     */
    public static function normalize(string $number): string
    {
        $trimmed = trim($number);

        // Hanya boleh mengandung digit, spasi, tanda tambah diawal, atau tanda hubung
        if (! preg_match('/^\+?[0-9\s\-]+$/', $trimmed)) {
            throw new InvalidArgumentException('Nomor WhatsApp hanya boleh mengandung angka, spasi, atau tanda tambah.');
        }

        // Hapus semua karakter non-digit
        $digits = preg_replace('/[^0-9]/', '', $trimmed);

        if (empty($digits)) {
            throw new InvalidArgumentException('Nomor WhatsApp wajib diisi.');
        }

        // Normalisasi awalan Indonesia
        if (str_starts_with($digits, '08')) {
            $digits = '628' . substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '628' . substr($digits, 1);
        }

        // Validasi panjang dan awalan nomor
        $length = strlen($digits);
        if ($length < 10 || $length > 15) {
            throw new InvalidArgumentException('Panjang nomor WhatsApp harus antara 10 hingga 15 digit angka.');
        }

        return $digits;
    }

    /**
     * Memeriksa apakah nomor telepon valid.
     */
    public static function isValid(string $number): bool
    {
        try {
            static::normalize($number);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}

