<?php

namespace Taha20\IranPayment\Gateways;

use Illuminate\Support\Str;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\RefundResult;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه آزمایشی.
 *
 * کاربرد: تست کامل جریان پرداخت (فرم → بازگشت → تأیید → رویدادها) بدون
 * هیچ تماس شبکه‌ای و بدون نیاز به حساب درگاه واقعی. در تست‌های خودکار
 * می‌توانید با IranPayment::fake() همین را به‌صورت مستقیم تزریق کنید.
 *
 * مقدار outcome در config مشخص می‌کند نتیجهٔ شبیه‌سازی‌شده چه باشد:
 * success | failed | cancel
 */
class FakeGateway extends AbstractGateway
{
    public function purchase(Transaction $transaction): PaymentResponse
    {
        $reference = 'FAKE-'.Str::upper(Str::random(12));

        $callback = route('iran-payment.callback', [
            'gateway' => $this->name(),
            'uuid' => $transaction->uuid,
        ]);

        return PaymentResponse::redirect(
            $callback.'?'.http_build_query([
                'status' => 'OK',
                'ref' => $reference,
                'amount' => (int) $transaction->amount,
            ]),
            $reference,
            ['fake' => true]
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $outcome = strtolower((string) $this->config('outcome', 'success'));
        $reference = (string) $this->value($callback, ['ref'], $transaction->reference);

        if ($outcome === 'cancel') {
            return VerificationResult::failure('پرداخت توسط کاربر لغو شد.', $callback, $reference);
        }

        if ($outcome === 'failed') {
            return VerificationResult::failure('پرداخت آزمایشی ناموفق بود.', $callback, $reference);
        }

        return VerificationResult::success(
            referenceId: $reference,
            trackingCode: $reference,
            cardNumber: '6037-****-****-1234',
            amount: (int) $transaction->amount,
            message: 'پرداخت آزمایشی با موفقیت تأیید شد.',
            raw: $callback
        );
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        return RefundResult::success('FAKE-REFUND', 'بازگشت وجه آزمایشی انجام شد.');
    }
}
