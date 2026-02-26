<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PublicStorageUrl
{
    public static function fromPath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalizedPath = self::normalizePath($path);

        if (File::exists(public_path($normalizedPath))) {
            return asset(self::encodePath($normalizedPath));
        }

        if (! Storage::disk('public')->exists($normalizedPath)) {
            return asset(self::encodePath($normalizedPath));
        }

        self::mirrorToPublicStorage($normalizedPath);

        return asset('storage/' . self::encodePath($normalizedPath));
    }

    private static function mirrorToPublicStorage(string $relativePath): void
    {
        try {
            $disk = Storage::disk('public');
            $sourcePath = $disk->path($relativePath);
            $targetPath = public_path('storage/' . $relativePath);

            File::ensureDirectoryExists(dirname($targetPath));

            if (
                File::exists($targetPath) &&
                File::lastModified($targetPath) >= File::lastModified($sourcePath)
            ) {
                return;
            }

            File::copy($sourcePath, $targetPath);
        } catch (Throwable $exception) {
            Log::warning('Unable to mirror public disk file into public/storage.', [
                'path' => $relativePath,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private static function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', trim($path)), '/');
    }

    private static function encodePath(string $path): string
    {
        return collect(explode('/', $path))
            ->map(static fn (string $segment): string => rawurlencode($segment))
            ->implode('/');
    }
}
