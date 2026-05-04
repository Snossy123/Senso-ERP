<?php

namespace App\Support;

class Locale
{
    /**
     * @return array<string, array{name: string, native: string, dir: string, flag: string}>
     */
    public static function supported(): array
    {
        return config('locales.supported', []);
    }

    /**
     * @return list<string>
     */
    public static function supportedCodes(): array
    {
        return array_keys(static::supported());
    }

    public static function current(): string
    {
        return app()->getLocale();
    }

    public static function dir(): string
    {
        $locale = static::current();

        return static::supported()[$locale]['dir'] ?? 'ltr';
    }

    public static function isRtl(): bool
    {
        return static::dir() === 'rtl';
    }

    public static function cssFolder(): string
    {
        return static::isRtl() ? 'css-rtl' : 'css';
    }
}
