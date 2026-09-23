<?php

namespace App\Support;

class SomalilandPhone
{
    public const COUNTRY_CODE = '252';

    public const LOCAL_DIGITS = 9;

    public static function localDigits(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = substr($digits, strlen(self::COUNTRY_CODE));
        }

        return substr($digits, 0, self::LOCAL_DIGITS);
    }

    public static function format(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            $local = substr($digits, strlen(self::COUNTRY_CODE));
        } else {
            $local = $digits;
        }

        if (strlen($local) !== self::LOCAL_DIGITS || ! ctype_digit($local)) {
            return null;
        }

        return '+'.self::COUNTRY_CODE.$local;
    }

    public static function isValid(?string $value): bool
    {
        return self::format($value) !== null;
    }
}
