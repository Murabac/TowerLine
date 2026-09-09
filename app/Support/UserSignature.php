<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserSignature
{
    public const MAX_BYTES = 120000;

    public static function pngFromDataUrl(?string $dataUrl): ?string
    {
        if (! is_string($dataUrl) || $dataUrl === '') {
            return null;
        }

        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/]+={0,2})$#', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[1], true);

        if ($binary === false || strlen($binary) < 32 || strlen($binary) > self::MAX_BYTES) {
            return null;
        }

        if (! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        return $binary;
    }

    public static function binaryFromRequest(User $user, Request $request): ?string
    {
        if ($request->boolean('use_saved_signature') && self::exists($user->signature_path)) {
            return Storage::disk('local')->get($user->signature_path);
        }

        return self::pngFromDataUrl($request->string('signature_data')->toString());
    }

    public static function storeForUser(User $user, string $png): string
    {
        $path = 'signatures/users/'.$user->id.'.png';
        Storage::disk('local')->put($path, $png);
        $user->forceFill(['signature_path' => $path])->save();

        return $path;
    }

    public static function snapshot(string $folder, string $party, string $png): string
    {
        $path = 'signatures/'.$folder.'/'.$party.'.png';
        Storage::disk('local')->put($path, $png);

        return $path;
    }

    public static function capture(User $user, Request $request, string $folder, string $party): ?string
    {
        $png = self::binaryFromRequest($user, $request);

        if ($png === null) {
            return null;
        }

        if ($request->boolean('save_signature') && ! $request->boolean('use_saved_signature')) {
            self::storeForUser($user, $png);
        }

        return self::snapshot($folder, $party, $png);
    }

    public static function exists(?string $path): bool
    {
        return filled($path) && Storage::disk('local')->exists($path);
    }

    public static function dataUri(?string $path): ?string
    {
        if (! self::exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) Storage::disk('local')->get($path));
    }

    public static function stream(?string $path): ?array
    {
        if (! self::exists($path)) {
            return null;
        }

        return [
            'path' => $path,
            'filename' => basename($path),
        ];
    }
}
