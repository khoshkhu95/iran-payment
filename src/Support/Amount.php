<?php

namespace Taha20\IranPayment\Support;

/**
 * ابزار کار با مبلغ.
 *
 * قاعدهٔ پکیج: «همه‌چیز درون پکیج با ریال کار می‌کند». واحد ورودی کاربر
 * (ریال یا تومان) فقط در مرز ورود نرمال می‌شود و تبدیل به واحد موردنیاز
 * هر درگاه در همان درایور انجام می‌گیرد. این کار جلوی خطاهای رایج
 * «یک صفر کم/زیاد» را می‌گیرد.
 */
final class Amount
{
    public const RIAL = 'IRR';

    public const TOMAN = 'IRT';

    /**
     * تبدیل هر ورودی به ریال.
     */
    public static function toRial(int|float|string $amount, ?string $unit = null): int
    {
        $value = (int) round((float) $amount);
        $unit = self::normalizeUnit($unit);

        return $unit === self::TOMAN ? $value * 10 : $value;
    }

    /**
     * تبدیل ریال به تومان (بدون اعشار).
     */
    public static function toToman(int $rial): int
    {
        return intdiv($rial, 10);
    }

    /**
     * واحد پیش‌فرض ورودی پروژه.
     */
    public static function defaultUnit(): string
    {
        return self::normalizeUnit((string) config('iran-payment.currency', self::RIAL));
    }

    public static function normalizeUnit(?string $unit): string
    {
        $unit = strtoupper(trim((string) ($unit ?? self::defaultUnit())));

        return match ($unit) {
            'TOMAN', 'IRT' => self::TOMAN,
            default => self::RIAL,
        };
    }

    /**
     * قالب‌بندی خوانا برای نمایش: ۱۲,۵۰۰ تومان
     */
    public static function format(int $rial, bool $inToman = true, bool $withUnit = true): string
    {
        $value = $inToman ? self::toToman($rial) : $rial;
        $text = Persian::digits(number_format($value));

        if (! $withUnit) {
            return $text;
        }

        return $text.' '.($inToman ? 'تومان' : 'ریال');
    }
}
