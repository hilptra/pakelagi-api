<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Di luar lingkungan lokal, halaman dokumentasi hanya terbuka bila gate mengizinkan.
        Gate::define('viewApiDocs', fn (?User $user = null) => true);
    }

    public function test_the_openapi_document_is_generated(): void
    {
        $response = $this->getJson('/docs/api.json')->assertOk();

        $this->assertStringStartsWith('3.', $response->json('openapi'));

        $paths = array_keys($response->json('paths'));

        foreach (['v1/categories', 'v1/products', 'v1/auth/login', 'v1/admin/products'] as $expected) {
            $this->assertTrue(
                collect($paths)->contains(fn (string $path) => str_contains($path, $expected)),
                "Endpoint {$expected} tidak ada di dokumentasi.",
            );
        }
    }
}
