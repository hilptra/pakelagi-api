<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RevalidateFrontendCache implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(public readonly array $tags)
    {
        $this->afterCommit();
    }

    /**
     * Jeda (detik) sebelum percobaan ulang ke-2 dan ke-3.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(): void
    {
        $url = config('pakelagi.frontend.revalidate_url');
        $secret = config('pakelagi.frontend.revalidate_secret');

        if (! $url || ! $secret) {
            return;
        }

        Http::withToken($secret)
            ->acceptJson()
            ->timeout((int) config('pakelagi.frontend.revalidate_timeout'))
            ->post($url, ['tags' => $this->tags])
            ->throw();
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Revalidasi cache frontend gagal.', [
            'tags' => $this->tags,
            'error' => $exception->getMessage(),
        ]);
    }
}
