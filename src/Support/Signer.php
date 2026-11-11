<?php

namespace Taha20\IranPayment\Support;

use Taha20\IranPayment\Exceptions\ConfigurationException;

/**
 * امضا و تأیید امضای RSA برای درگاه‌های بانکی (سداد، پاسارگاد، ...).
 *
 * کلید می‌تواند مسیر یک فایل .pem باشد یا خودِ محتوای PEM. اگر مسیر باشد
 * نسبت به ریشهٔ پروژه (base_path) هم بررسی می‌شود تا در استقرار روی هاست
 * لازم نباشد مسیر مطلق بنویسید.
 */
final class Signer
{
    /**
     * امضای داده با کلید خصوصی و برگرداندن Base64.
     */
    public static function sign(string $data, string $key, string $password = '', int|string $algorithm = OPENSSL_ALGO_SHA1): string
    {
        $resource = self::loadPrivateKey($key, $password);
        $signature = '';

        if (! openssl_sign($data, $signature, $resource, $algorithm)) {
            throw new ConfigurationException('امضای داده با کلید خصوصی انجام نشد. صحت کلید و رمز آن را بررسی کنید.');
        }

        return base64_encode($signature);
    }

    /**
     * تأیید امضا با کلید عمومی.
     */
    public static function verify(string $data, string $signature, string $publicKey, int|string $algorithm = OPENSSL_ALGO_SHA1): bool
    {
        $resource = self::loadPublicKey($publicKey);
        $binary = base64_decode($signature, true);

        if ($binary === false) {
            return false;
        }

        return openssl_verify($data, $binary, $resource, $algorithm) === 1;
    }

    /**
     * @return \OpenSSLAsymmetricKey
     */
    public static function loadPrivateKey(string $key, string $password = '')
    {
        $pem = self::readKey($key);

        $resource = openssl_pkey_get_private($pem, $password);

        if ($resource === false) {
            throw new ConfigurationException('کلید خصوصی RSA قابل خواندن نیست. مسیر فایل یا رمز کلید را بررسی کنید.');
        }

        return $resource;
    }

    /**
     * @return \OpenSSLAsymmetricKey
     */
    public static function loadPublicKey(string $key)
    {
        $resource = openssl_pkey_get_public(self::readKey($key));

        if ($resource === false) {
            throw new ConfigurationException('کلید عمومی RSA قابل خواندن نیست.');
        }

        return $resource;
    }

    /**
     * اگر مقدار یک مسیر فایل موجود بود، محتوای آن خوانده می‌شود؛ در غیر این صورت
     * فرض می‌کنیم خودِ محتوای PEM است.
     */
    private static function readKey(string $key): string
    {
        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }

        foreach ([$key, base_path($key), storage_path($key)] as $candidate) {
            if ($candidate && is_file($candidate)) {
                return (string) file_get_contents($candidate);
            }
        }

        throw new ConfigurationException(sprintf('فایل کلید در مسیر [%s] پیدا نشد.', $key));
    }
}
