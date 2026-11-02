<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateStorage
{
    public static function disk(): Filesystem
    {
        return Storage::disk('local');
    }

    public static function legacyDisk(): Filesystem
    {
        return Storage::disk('public');
    }

    public static function exists(string $path): bool
    {
        return self::disk()->exists($path) || self::legacyDisk()->exists($path);
    }

    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'local');
    }

    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (self::disk()->exists($path)) {
            self::disk()->delete($path);
        }

        if (self::legacyDisk()->exists($path)) {
            self::legacyDisk()->delete($path);
        }
    }

    public static function move(string $from, string $to): string
    {
        $source = self::disk()->exists($from)
            ? self::disk()
            : (self::legacyDisk()->exists($from) ? self::legacyDisk() : null);

        if (! $source) {
            return $from;
        }

        $destination = self::disk();
        $destination->delete($to);

        if ($source === $destination) {
            $destination->move($from, $to);

            return $to;
        }

        $destination->put($to, $source->get($from));
        $source->delete($from);

        return $to;
    }

    public static function response(string $path, ?string $name = null, array $headers = []): StreamedResponse
    {
        abort_unless(self::exists($path), 404);

        if (self::disk()->exists($path)) {
            return self::disk()->response($path, $name, $headers);
        }

        return self::legacyDisk()->response($path, $name, $headers);
    }

    public static function download(string $path, ?string $name = null, array $headers = []): StreamedResponse
    {
        abort_unless(self::exists($path), 404);

        if (self::disk()->exists($path)) {
            return self::disk()->download($path, $name, $headers);
        }

        return self::legacyDisk()->download($path, $name, $headers);
    }
}
