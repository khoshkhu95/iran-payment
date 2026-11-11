<?php

namespace Taha20\IranPayment\Exceptions;

class InvalidPaymentException extends IranPaymentException
{
    protected string $userMessage = 'اطلاعات پرداخت معتبر نیست.';

    public static function nonPositiveAmount(): self
    {
        return new self('مبلغ پرداخت باید بزرگ‌تر از صفر باشد.');
    }

    public static function emptyOrderId(): self
    {
        return new self('شمارهٔ سفارش (order id) نمی‌تواند خالی باشد.');
    }

    public static function invalidToken(): self
    {
        return new self('توکن پرداخت نامعتبر است یا دست‌کاری شده است.');
    }

    public static function expiredToken(): self
    {
        return new self('مدت اعتبار فرم پرداخت به پایان رسیده است. صفحه را دوباره بارگذاری کنید.');
    }

    public static function transactionNotFound(string $identifier): self
    {
        return new self(sprintf('تراکنش [%s] یافت نشد.', $identifier));
    }
}
