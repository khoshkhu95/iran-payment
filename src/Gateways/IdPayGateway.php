<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه آیدی‌پی
 *
 * @see https://idpay.ir/web-service/v1.1/
 */
class IdPayGateway extends AbstractGateway
{
    private const CODE_PAID = 100;

    private const CODE_ALREADY_VERIFIED = 101;

    protected function baseUrl(): string
    {
        return 'https://api.idpay.ir/v1.1';
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [
            'X-API-KEY' => $this->require('api_key'),
            'X-SANDBOX' => $this->bool('sandbox') ? '1' : '0',
        ];
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $payload = [
            'order_id' => (string) $transaction->order_id,
            'amount' => (int) $transaction->amount,
            'callback' => $this->callbackUrl($transaction),
            'description' => $transaction->description ?: 'پرداخت سفارش '.$transaction->order_id,
        ];

        if ($transaction->mobile) {
            $payload['phone'] = $transaction->mobile;
        }

        if ($transaction->email) {
            $payload['mail'] = $transaction->email;
        }

        $response = $this->postJson($this->baseUrl().'/payment', $payload, $this->headers());

        $link = $response['link'] ?? null;

        if (! $link) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['error_message'] ?? 'لینک پرداخت دریافت نشد.'),
                $response
            );
        }

        return PaymentResponse::redirect(
            (string) $link,
            isset($response['id']) ? (string) $response['id'] : null,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $status = (int) $this->value($callback, ['status'], 0);

        if ($status !== self::CODE_PAID && $status !== self::CODE_ALREADY_VERIFIED) {
            return VerificationResult::failure(
                (string) $this->value($callback, ['track_id', 'error_message'], 'پرداخت ناموفق بود یا لغو شد.'),
                $callback
            );
        }

        $paymentId = (string) $this->value($callback, ['id'], $transaction->reference);

        if ($paymentId === '') {
            return VerificationResult::failure('شناسهٔ پرداخت در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = $this->postJson($this->baseUrl().'/payment/verify', [
            'id' => $paymentId,
            'order_id' => (string) $transaction->order_id,
        ], $this->headers());

        $verifyStatus = (int) ($response['status'] ?? 0);

        if ($verifyStatus !== self::CODE_PAID && $verifyStatus !== self::CODE_ALREADY_VERIFIED) {
            return VerificationResult::failure(
                (string) ($response['error_message'] ?? 'تأیید پرداخت انجام نشد.'),
                $response,
                $paymentId
            );
        }

        if (! $this->amountMatches($transaction, $response['amount'] ?? null)) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $paymentId);
        }

        return VerificationResult::success(
            referenceId: isset($response['track_id']) ? (string) $response['track_id'] : $paymentId,
            trackingCode: isset($response['track_id']) ? (string) $response['track_id'] : null,
            cardNumber: $response['payment']['card_no'] ?? ($response['card_no'] ?? null),
            amount: $this->fromGatewayAmount($response['amount'] ?? $transaction->amount),
            message: $verifyStatus === self::CODE_ALREADY_VERIFIED
                ? 'این تراکنش قبلاً تأیید شده بود.'
                : 'پرداخت با موفقیت تأیید شد.',
            raw: $response
        );
    }
}
