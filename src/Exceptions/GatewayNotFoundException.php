<?php

namespace Taha20\IranPayment\Exceptions;

class GatewayNotFoundException extends IranPaymentException
{
    protected string $userMessage = 'درگاه پرداخت انتخاب‌شده در دسترس نیست.';

    public static function make(string $name): self
    {
        return new self(sprintf(
            'درگاه [%s] پیدا نشد. مطمئن شوید در config/iran-payment.php در بخش gateways تعریف شده و کلید driver آن معتبر است.',
            $name
        ));
    }
}
