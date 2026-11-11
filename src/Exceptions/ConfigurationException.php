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

    public static function settingsTableMissing(string $table): self
    {
        return new self(sprintf(
            'جدول [%s] برای ذخیرهٔ تنظیمات درگاه‌ها وجود ندارد. مایگریشن‌های پکیج را با «php artisan iran-payment:install» منتشر و سپس «php artisan migrate» را اجرا کنید.',
            $table
        ));
    }
}
