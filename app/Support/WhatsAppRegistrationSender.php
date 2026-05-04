<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppRegistrationSender
{
    public static function sendOfficialWelcome(User $user, string $password, string $districtName): bool
    {
        $message = self::buildOfficialWelcomeMessage($user, $password, $districtName);

        return self::sendMessage((string) ($user->phone ?? ''), $message);
    }

    /**
     * @param  array<int, string>  $categories
     * @param  array<int, string>  $districts
     */
    public static function sendCommitteeWelcome(User $user, string $password, array $categories, array $districts): bool
    {
        $message = self::buildCommitteeWelcomeMessage($user, $password, $categories, $districts);

        return self::sendMessage((string) ($user->phone ?? ''), $message);
    }

    private static function buildOfficialWelcomeMessage(User $user, string $password, string $districtName): string
    {
        $websiteUrl = rtrim((string) config('app.url'), '/');

        return implode("\n", [
            '*Assalamu\'alaikum warahmatullahi wabarakatuh.*,
            '',
            '*Selamat, akun official e-MTQ Anda sudah berhasil didaftarkan.*',
            '',
            '*Website e-MTQ*',
            '',
            $websiteUrl !== '' ? 'Klik link berikut untuk login: '.$websiteUrl : '-',
            '',
            '',
            '*Data Akun*',
            '- Nama: '.($user->name ?: '-'),
            '- Role: Official',
            '- NIP/Nomor Induk: '.($user->nomor_induk ?: '-'),
            '- Email: '.($user->email ?: '-'),
            '- Password: *'.$password.'*',
            '- Kecamatan: *'.$districtName.'*',
            '',
            'Silakan login ke e-MTQ dengan data di atas dan mohon jaga kerahasiaan akun.',
        ]);
    }

    /**
     * @param  array<int, string>  $categories
     * @param  array<int, string>  $districts
     */
    private static function buildCommitteeWelcomeMessage(User $user, string $password, array $categories, array $districts): string
    {
        $websiteUrl = rtrim((string) config('app.url'), '/');

        return implode("\n", [
            '*Assalamu\'alaikum warahmatullahi wabarakatuh.*',
            '',
            '*Selamat, akun panitia e-MTQ Anda sudah berhasil didaftarkan.*',
            '',
            '*Website e-MTQ*',
            '',
            $websiteUrl !== '' ? 'Klik link berikut untuk login: '.$websiteUrl : '-',
            '',
            '',
            '*Data Akun*',
            '- Nama: '.($user->name ?: '-'),
            '- Role: Panitia',
            '- NIP/Nomor Induk: '.($user->nomor_induk ?: '-'),
            '- Email: '.($user->email ?: '-'),
            '- Password: *'.$password.'*',
            '',
            '*Hak Akses Golongan*',
            $categories !== [] ? implode(', ', $categories) : '-',
            '',
            '*Hak Akses Kecamatan Verifikator*',
            $districts !== [] ? implode(', ', $districts) : '-',
            '',
            'Silakan login ke e-MTQ dengan data di atas dan mohon jaga kerahasiaan akun.',
        ]);
    }

    private static function sendMessage(string $number, string $message): bool
    {
        $baseUrl = trim((string) config('services.whatsapp.base_url'));
        $apiKey = trim((string) config('services.whatsapp.api_key'));
        $sender = trim((string) config('services.whatsapp.sender'));
        $normalizedNumber = self::normalizeNumber($number);

        if ($baseUrl === '' || $apiKey === '' || $sender === '' || $normalizedNumber === '') {
            Log::warning('WhatsApp registration message skipped because the configuration or recipient number is incomplete.', [
                'recipient' => $normalizedNumber,
            ]);

            return false;
        }

        $endpoint = rtrim($baseUrl, '/').'/send-message';

        try {
            $response = Http::asJson()
                ->timeout(20)
                ->retry(2, 500)
                ->post($endpoint, [
                    'api_key' => $apiKey,
                    'sender' => $sender,
                    'number' => $normalizedNumber,
                    'message' => $message,
                    'footer' => 'Sent via e-MTQ Kab.Tanah Datar',
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp registration message failed.', [
                    'recipient' => $normalizedNumber,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            Log::warning('WhatsApp registration message could not be sent.', [
                'recipient' => $normalizedNumber,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private static function normalizeNumber(string $number): string
    {
        return preg_replace('/\D+/', '', trim($number)) ?: '';
    }
}
