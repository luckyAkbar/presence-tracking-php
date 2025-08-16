<?php
declare(strict_types=1);

namespace App\Config;

final class Config
{
    /** @var array<string, mixed> */
    private static array $settings = [];

    /**
     * @param array<string, mixed> $settings
     */
    public static function init(array $settings): void
    {
        self::$settings = $settings;
    }

    public static function getString(string $key, string $default = ''): string
    {
        $value = self::$settings[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::$settings[$key] ?? $default;
        return is_int($value) ? $value : $default;
    }
}


