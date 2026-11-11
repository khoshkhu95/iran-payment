<?php

namespace Taha20\IranPayment\Events;

use Throwable;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * پرداخت ناموفق، لغوشده یا خطای ارتباط با درگاه.
 */
class PaymentFailed
{
    public function __construct(
        public Transaction $transaction,
        public ?Throwable $exception = null,
        public ?VerificationResult $result = null,
    ) {}

    public function canceled(): bool
    {
        return (bool) $this->result?->canceled;
    }
}
