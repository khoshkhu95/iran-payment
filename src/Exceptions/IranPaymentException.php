<?php

namespace Taha20\IranPayment\Exceptions;

use RuntimeException;

/**
 * پایهٔ همهٔ استثناهای پکیج.
 *
 * این کار باعث می‌شود برنامه‌نویس بتواند در سطح پروژه فقط همین یک کلاس را
 * catch کند و هر خطای پرداختی را بگیرد.
 */
class IranPaymentException extends RuntimeException
{
    /**
     * پیام قابل نمایش به کاربر نهایی (بدون جزئیات فنی).
     */
    protected string $userMessage = 'خطایی در فرایند پرداخت رخ داد.';

    public function userMessage(): string
    {
        return $this->userMessage;
    }
}
