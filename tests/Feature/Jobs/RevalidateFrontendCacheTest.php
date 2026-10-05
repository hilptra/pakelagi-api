<?php

namespace Tests\Feature\Jobs;

use App\Jobs\RevalidateFrontendCache;
use Exception;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RevalidateFrontendCacheTest extends TestCase
{
    private const URL = 'https://frontend.test/api/revalidate';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pakelagi.frontend.revalidate_url' => self::URL,
            'pakelagi.frontend.revalidate_secret' => 'rahasia',
        ]);
    }

    public function test_it_posts_the_tags_with_the_secret(): void
    {
        Http::fake();

        (new RevalidateFrontendCache(['catalog', 'product:kemeja']))->handle();

        Http::assertSent(fn (Request $request) => $request->url() === self::URL
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer rahasia')
            && $request['tags'] === ['catalog', 'product:kemeja']);
    }

    public function test_it_does_nothing_when_the_frontend_is_not_configured(): void
    {
        config(['pakelagi.frontend.revalidate_url' => null]);
        Http::fake();

        (new RevalidateFrontendCache(['catalog']))->handle();

        Http::assertNothingSent();
    }

    public function test_it_fails_when_the_frontend_returns_an_error(): void
    {
        Http::fake(['*' => Http::response(['message' => 'salah'], 500)]);

        $this->expectException(RequestException::class);

        (new RevalidateFrontendCache(['catalog']))->handle();
    }

    public function test_it_logs_a_warning_after_retries_are_exhausted(): void
    {
        Log::spy();

        (new RevalidateFrontendCache(['catalog']))->failed(new Exception('timeout'));

        Log::shouldHaveReceived('warning')->once();
    }
}
