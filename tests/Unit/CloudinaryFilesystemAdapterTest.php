<?php

namespace Tests\Unit;

use App\Services\CloudinaryFilesystemAdapter;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use League\Flysystem\Config;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CloudinaryFilesystemAdapterTest extends TestCase
{
    public function test_cloudinary_disk_requires_credentials(): void
    {
        config()->set('filesystems.disks.cloudinary', [
            'driver' => 'cloudinary',
            'cloud_name' => '',
            'api_key' => '',
            'api_secret' => '',
            'folder' => 'pakelagi',
        ]);

        $this->expectException(InvalidArgumentException::class);
        Storage::disk('cloudinary');
    }

    public function test_cloudinary_disk_generates_secure_url_with_folder(): void
    {
        config()->set('filesystems.disks.cloudinary', [
            'driver' => 'cloudinary',
            'cloud_name' => 'demo-cloud',
            'api_key' => 'key-123',
            'api_secret' => 'secret-456',
            'folder' => 'pakelagi',
            'secure' => true,
        ]);

        $url = Storage::disk('cloudinary')->url('products/42/sample.webp');

        $this->assertStringStartsWith('https://res.cloudinary.com/demo-cloud/image/upload/', $url);
        $this->assertStringContainsString('f_webp', $url);
        $this->assertStringContainsString('/pakelagi/products/42/sample', $url);
    }

    public function test_adapter_uploads_and_deletes_image(): void
    {
        $uploadApi = Mockery::mock(UploadApi::class);
        $uploadApi->shouldReceive('upload')
            ->once()
            ->withArgs(function (string $localPath, array $options): bool {
                return file_exists($localPath)
                    && $options['public_id'] === 'pakelagi/products/1/photo'
                    && $options['format'] === 'webp'
                    && $options['resource_type'] === 'image';
            })
            ->andReturn(new ApiResponse(['public_id' => 'pakelagi/products/1/photo'], []));

        $uploadApi->shouldReceive('destroy')
            ->once()
            ->with('pakelagi/products/1/photo', [
                'resource_type' => 'image',
                'invalidate' => true,
            ])
            ->andReturn(new ApiResponse(['result' => 'ok'], []));

        $cloudinary = Mockery::mock(Cloudinary::class);
        $cloudinary->shouldReceive('uploadApi')->twice()->andReturn($uploadApi);

        $adapter = new CloudinaryFilesystemAdapter($cloudinary, 'pakelagi');
        $adapter->write('products/1/photo.webp', 'binary-webp-content', new Config);
        $adapter->delete('products/1/photo.webp');

        $this->assertSame('image/webp', $adapter->mimeType('products/1/photo.webp')->mimeType());
    }

    public function test_adapter_wraps_upload_and_delete_errors(): void
    {
        $uploadApi = Mockery::mock(UploadApi::class);
        $uploadApi->shouldReceive('upload')->once()->andThrow(new RuntimeException('Network error'));
        $uploadApi->shouldReceive('destroy')->once()->andReturn(new ApiResponse(['result' => 'error'], []));

        $cloudinary = Mockery::mock(Cloudinary::class);
        $cloudinary->shouldReceive('uploadApi')->twice()->andReturn($uploadApi);

        $adapter = new CloudinaryFilesystemAdapter($cloudinary, 'pakelagi');

        try {
            $adapter->write('products/1/fail.webp', 'bytes', new Config);
            $this->fail('Expected UnableToWriteFile exception.');
        } catch (UnableToWriteFile $e) {
            $this->assertStringContainsString('products/1/fail.webp', $e->getMessage());
        }

        $this->expectException(UnableToDeleteFile::class);
        $adapter->delete('products/1/fail.webp');
    }

    public function test_adapter_checks_file_existence_and_wraps_errors(): void
    {
        $adminApi = Mockery::mock(AdminApi::class);
        $adminApi->shouldReceive('asset')
            ->once()
            ->with('pakelagi/products/1/exists', ['resource_type' => 'image'])
            ->andReturn(new ApiResponse(['public_id' => 'pakelagi/products/1/exists'], []));
        $adminApi->shouldReceive('asset')
            ->once()
            ->with('pakelagi/products/1/missing', ['resource_type' => 'image'])
            ->andThrow(new NotFound('Resource not found'));
        $adminApi->shouldReceive('asset')
            ->once()
            ->with('pakelagi/products/1/error', ['resource_type' => 'image'])
            ->andThrow(new RuntimeException('Timeout'));

        $cloudinary = Mockery::mock(Cloudinary::class);
        $cloudinary->shouldReceive('adminApi')->times(3)->andReturn($adminApi);

        $adapter = new CloudinaryFilesystemAdapter($cloudinary, 'pakelagi');

        $this->assertTrue($adapter->fileExists('products/1/exists.webp'));
        $this->assertFalse($adapter->fileExists('products/1/missing.webp'));

        $this->expectException(UnableToCheckExistence::class);
        $adapter->fileExists('products/1/error.webp');
    }
}
