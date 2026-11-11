<?php

namespace Taha20\IranPayment\Events;

use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * پرداخت با موفقیت تأیید شد.
 *
 * مهم‌ترین رویداد پکیج؛ معمولاً همین‌جا سفارش را paid می‌کنید:
 *
 *   Event::listen(PaymentSucceeded::class, function ($event) {
 *       $event->transaction->payable?->markAsPaid();
 *   });
 */
class PaymentSucceeded
{
    public function __construct(
        public Transaction $transaction,
        public VerificationResult $result,
    ) {}
}
