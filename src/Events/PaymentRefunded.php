<?php

namespace Taha20\IranPayment\Events;

use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\RefundResult;

/**
 * بازگشت وجه انجام شد.
 */
class PaymentRefunded
{
    public function __construct(
        public Transaction $transaction,
        public RefundResult $result,
    ) {}
}
