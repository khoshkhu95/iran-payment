<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\Soap;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه مستقیم سامان (سپ).
 *
 * گرفتن توکن با وب‌سرویس REST و تأیید با وب‌سرویس SOAP قدیمی انجام می‌شود؛
 * همان روشی که مستندات رسمی سپ اعلام کرده است.
 */
class SamanGateway extends AbstractGateway
{
    public function purchase(Transaction $transaction): PaymentResponse
    {
        $payload = [
            'Action' => 'token',
            'TerminalId' => $this->require('terminal_id'),
            'Amount' => (int) $transaction->amount,
            'ResNum' => (string) $transaction->order_id,
            'RedirectUrl' => $this->callbackUrl($transaction),
        ];

        if ($transaction->mobile) {
            $payload['CellNumber'] = $transaction->mobile;
        }

        if ($this->config('password')) {
            $payload['Password'] = $this->config('password');
        }

        $response = $this->postForm((string) $this->config('token_url'), $payload);

        $token = $response['token'] ?? null;

        if ((int) ($response['status'] ?? 0) !== 1 || ! $token) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['errorDesc'] ?? $response['errorCode'] ?? 'توکن پرداخت دریافت نشد.'),
                $response
            );
        }

        $paymentUrl = (string) $this->config('payment_url');

        return PaymentResponse::redirect(
            $paymentUrl.'?token='.urlencode((string) $token).'&getMethod=true',
            (string) $token,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $state = strtoupper((string) $this->value($callback, ['State', 'state'], ''));

        if ($state !== '' && $state !== 'OK') {
            return VerificationResult::canceled('پرداخت توسط کاربر لغو شد یا ناموفق بود.', $callback);
        }

        $refNum = (string) $this->value($callback, ['RefNum', 'refNum'], '');

        if ($refNum === '') {
            return VerificationResult::failure('شمارهٔ مرجع تراکنش در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = Soap::call(
            (string) $this->config('verify_url'),
            'verifyTransaction',
            [
                'stringToVerify' => $refNum,
                'terminalNumber' => $this->require('terminal_id'),
            ],
            (string) $this->config('namespace', 'http://tempuri.org/'),
            $this->name()
        );

        $amount = (float) ($response['_value'] ?? -1);

        // سپ مبلغ را برمی‌گرداند؛ مقدار منفی یعنی خطا
        if ($amount <= 0) {
            return VerificationResult::failure(
                'تأیید تراکنش سامان انجام نشد. کد: '.$amount,
                $response,
                $refNum
            );
        }

        if (! $this->amountMatches($transaction, $amount)) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $refNum);
        }

        return VerificationResult::success(
            referenceId: $refNum,
            trackingCode: (string) $this->value($callback, ['TraceNo', 'traceNo'], $refNum),
            cardNumber: $this->value($callback, ['SecurePan', 'HashedCardNumber']),
            amount: $this->fromGatewayAmount($amount),
            raw: $response + $callback
        );
    }
}
