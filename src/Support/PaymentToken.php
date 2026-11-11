<?php

namespace Taha20\IranPayment\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Taha20\IranPayment\Exceptions\InvalidPaymentException;

/**
 * توکن امضاشدهٔ پرداخت.
 *
 * مشکل رایج کامپوننت‌های آمادهٔ پرداخت این است که مبلغ و شمارهٔ سفارش در
 * فرم HTML قرار می‌گیرند و کاربر می‌تواند آن‌ها را در DevTools عوض کند.
 * راه‌حل: به‌جای مقادیر خام، یک توکن رمزنگاری‌شده با APP_KEY پروژه
 * می‌فرستیم؛ اگر کاربر حتی یک بایت تغییر دهد، رمزگشایی شکست می‌خورد و
 * درخواست رد می‌شود.
 */
final class PaymentToken
{
    /**
     * ساخت توکن از داده‌های پرداخت.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): string
    {
        $payload['exp'] = time() + (int) config('iran-payment.security.token_ttl', 3600);

        return Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * بازخوانی و اعتبارسنجی توکن.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidPaymentException
     */
    public static function parse(string $token): array
    {
        try {
            $json = Crypt::decryptString($token);
        } catch (DecryptException) {
            throw InvalidPaymentException::invalidToken();
        }

        $payload = json_decode($json, true);

        if (! is_array($payload)) {
            throw InvalidPaymentException::invalidToken();
        }

        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            throw InvalidPaymentException::expiredToken();
        }

        return $payload;
    }
}
