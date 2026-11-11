<?php

namespace Taha20\IranPayment\Events;

use Taha20\IranPayment\Models\Transaction;

/**
 * هنگام ساخت تراکنش در درگاه (قبل از رفتن کاربر به صفحهٔ بانک).
 */
class PaymentInitiated
{
    public function __construct(public Transaction $transaction) {}
}
