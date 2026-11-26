<?php

namespace App\Services;

use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use Illuminate\Support\Str;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Throwable;

class CloudinaryFilesystemAdapter implements FilesystemAdapter
{
    private readonly string $folder;

    private readonly FinfoMimeTypeDetector $mimeDetector;

    public function __construct(
        private readonly Cloudinary $cloudinary,
        string $folder = '',
    ) {
        $this->folder = trim($folder, '/');
        $this->mimeDetector = new FinfoMimeTypeDetector;
    }

    public function fileExists(string $path): bool
    {
        try {
            $this->cloudinary->adminApi()->asset($this->publicId($path), [
                'resource_type' => 'image',
            ]);

            return true;
        } catch (NotFound) {
            return false;
        } catch (Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $prefix = $this->publicId(trim($path, '/'));
            $response = $this->cloudinary->adminApi()->assets([
                'type' => 'upload',
                'prefix' => $prefix !== '' ? $prefix.'/' : '',
                'max_results' => 1,
            ]);

            return ! empty($response['resources']);
        } catch (Throwable $e) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'cld_');

        if ($tempPath === false) {
            throw UnableToWriteFile::atLocation($path, 'Unable to create temporary file for Cloudinary upload.');
        }

        try {
            if (file_put_contents($tempPath, $contents) === false) {
                throw UnableToWriteFile::atLocation($path, 'Unable to write contents to temporary file.');
            }

            $this->uploadFile($tempPath, $path);
        } catch (UnableToWriteFile $e) {
            throw $e;
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        } finally {
            @unlink($tempPath);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $data = stream_get_contents($contents);

        if ($data === false) {
            throw UnableToWriteFile::atLocation($path, 'Unable to read stream contents.');
        }

        $this->write($path, $data, $config);
    }

    public function read(string $path): string
    {
        try {
            $url = $this->getUrl($path);
            $contents = @file_get_contents($url);

            if ($contents === false) {
                throw UnableToReadFile::fromLocation($path, 'Failed to fetch asset from Cloudinary URL.');
            }

            return $contents;
        } catch (UnableToReadFile $e) {
            throw $e;
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function readStream(string $path)
    {
        try {
            $url = $this->getUrl($path);
            $stream = @fopen($url, 'rb');

            if ($stream === false) {
                throw UnableToReadFile::fromLocation($path, 'Failed to open stream from Cloudinary URL.');
            }

            return $stream;
        } catch (UnableToReadFile $e) {
            throw $e;
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function delete(string $path): void
    {
        try {
            $response = $this->cloudinary->uploadApi()->destroy($this->publicId($path), [
                'resource_type' => 'image',
                'invalidate' => true,
            ]);

            $result = $response['result'] ?? null;

            if (! in_array($result, ['ok', 'not found'], true)) {
                throw UnableToDeleteFile::atLocation($path, 'Unexpected Cloudinary destroy result: '.json_encode($response));
            }
        } catch (UnableToDeleteFile $e) {
            throw $e;
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $prefix = $this->publicId(trim($path, '/'));

            if ($prefix === '') {
                throw UnableToDeleteDirectory::atLocation($path, 'Refusing to delete root Cloudinary folder.');
            }

            $this->cloudinary->adminApi()->deleteAssetsByPrefix($prefix.'/');
        } catch (UnableToDeleteDirectory $e) {
            throw $e;
        } catch (Throwable $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->cloudinary->adminApi()->createFolder($this->publicId(trim($path, '/')));
        } catch (Throwable $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Cloudinary upload assets are public by default.');
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: Visibility::PUBLIC);
    }

    public function mimeType(string $path): FileAttributes
    {
        $mimeType = $this->mimeDetector->detectMimeTypeFromPath($path);

        if ($mimeType === null) {
            try {
                $asset = $this->cloudinary->adminApi()->asset($this->publicId($path), [
                    'resource_type' => 'image',
                ]);
                $format = $asset['format'] ?? null;
                if (is_string($format) && $format !== '') {
                    $mimeType = $this->mimeDetector->detectMimeTypeFromPath('file.'.$format);
                }
            } catch (Throwable $e) {
                throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
            }
        }

        if ($mimeType === null) {
            throw UnableToRetrieveMetadata::mimeType($path, 'Unable to determine MIME type.');
        }

        return new FileAttributes($path, mimeType: $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $asset = $this->cloudinary->adminApi()->asset($this->publicId($path), [
                'resource_type' => 'image',
            ]);

            $timestamp = isset($asset['created_at']) ? strtotime((string) $asset['created_at']) : false;

            if ($timestamp === false) {
                throw UnableToRetrieveMetadata::lastModified($path, 'Missing created_at metadata.');
            }

            return new FileAttributes($path, lastModified: $timestamp);
        } catch (UnableToRetrieveMetadata $e) {
            throw $e;
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $asset = $this->cloudinary->adminApi()->asset($this->publicId($path), [
                'resource_type' => 'image',
            ]);

            if (! isset($asset['bytes'])) {
                throw UnableToRetrieveMetadata::fileSize($path, 'Missing bytes metadata.');
            }

            return new FileAttributes($path, fileSize: (int) $asset['bytes']);
        } catch (UnableToRetrieveMetadata $e) {
            throw $e;
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = $this->publicId(trim($path, '/'));
        $response = $this->cloudinary->adminApi()->assets([
            'type' => 'upload',
            'prefix' => $prefix !== '' ? $prefix.'/' : '',
            'max_results' => 500,
        ]);

        foreach ($response['resources'] ?? [] as $resource) {
            $publicId = (string) ($resource['public_id'] ?? '');
            $format = (string) ($resource['format'] ?? '');
            $relative = $this->stripFolderPrefix($publicId);
            $fullPath = $format !== '' ? $relative.'.'.$format : $relative;

            yield new FileAttributes(
                path: $fullPath,
                fileSize: isset($resource['bytes']) ? (int) $resource['bytes'] : null,
                visibility: Visibility::PUBLIC,
                lastModified: isset($resource['created_at']) ? (strtotime((string) $resource['created_at']) ?: null) : null,
            );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->cloudinary->uploadApi()->rename(
                $this->publicId($source),
                $this->publicId($destination),
                ['resource_type' => 'image', 'overwrite' => true, 'invalidate' => true],
            );
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $contents = $this->read($source);
            $this->write($destination, $contents, $config);
        } catch (Throwable $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * Dipanggil oleh Laravel FilesystemAdapter::url($path).
     */
    public function getUrl(string $path): string
    {
        $image = $this->cloudinary->image($this->publicId($path));
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension !== '') {
            $image->format($extension);
        }

        return (string) $image->secure()->toUrl();
    }

    /**
     * @throws ApiError
     */
    private function uploadFile(string $localPath, string $targetPath): void
    {
        $options = [
            'public_id' => $this->publicId($targetPath),
            'resource_type' => 'image',
            'overwrite' => true,
            'invalidate' => true,
        ];

        $extension = pathinfo($targetPath, PATHINFO_EXTENSION);
        if ($extension !== '') {
            $options['format'] = strtolower($extension);
        }

        $this->cloudinary->uploadApi()->upload($localPath, $options);
    }

    private function publicId(string $path): string
    {
        $normalized = ltrim($path, '/');
        $extension = pathinfo($normalized, PATHINFO_EXTENSION);

        if ($extension !== '') {
            $normalized = substr($normalized, 0, -(strlen($extension) + 1));
        }

        return $this->folder !== ''
            ? $this->folder.'/'.$normalized
            : $normalized;
    }

    private function stripFolderPrefix(string $publicId): string
    {
        if ($this->folder !== '' && Str::startsWith($publicId, $this->folder.'/')) {
            return substr($publicId, strlen($this->folder) + 1);
        }

        return $publicId;
    }
}
