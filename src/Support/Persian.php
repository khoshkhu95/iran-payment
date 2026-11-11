<?php

namespace Taha20\IranPayment\Support;

/**
 * تبدیل ارقام لاتین به ارقام فارسی برای نمایش در رابط کاربری.
 */
final class Persian
{
    private const MAP = [
        '0' => '۰',
        '1' => '۱',
        '2' => '۲',
        '3' => '۳',
        '4' => '۴',
        '5' => '۵',
        '6' => '۶',
        '7' => '۷',
        '8' => '۸',
        '9' => '۹',
    ];

    public static function digits(?string $value): string
    {
        return strtr((string) $value, self::MAP);
    }
}
