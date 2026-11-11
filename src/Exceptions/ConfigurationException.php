<?php

namespace Taha20\IranPayment\Exceptions;

class ConfigurationException extends IranPaymentException
{
    protected string $userMessage = 'تنظیمات درگاه پرداخت کامل نیست.';

    public static function missing(string $gateway, string $key): self
    {
        return new self(sprintf(
            'کلید تنظیمات [%s] برای درگاه [%s] خالی است. مقدار آن را در config/iran-payment.php یا در فایل .env تنظیم کنید.',
            $key,
            $gateway
        ));
    }
}
