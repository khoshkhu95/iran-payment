<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه پی‌پینگ - API نسخهٔ ۲
 *
 * @see https://docs.payping.ir/
 */
class PayPingGateway extends AbstractGateway
{
    protected function baseUrl(): string
    {
        return 'https://api.payping.ir/v2';
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return ['Authorization' => 'Bearer '.$this->require('api_key')];
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $payload = [
            'amount' => (int) $transaction->amount,
            'returnUrl' => $this->callbackUrl($transaction),
            'clientRefId' => (string) $transaction->order_id,
            'description' => $transaction->description ?: 'پرداخت سفارش '.$transaction->order_id,
        ];

        if ($transaction->mobile) {
            $payload['payerIdentity'] = $transaction->mobile;
        }

        if ($transaction->email) {
            $payload['payerName'] = $transaction->email;
        }

        $response = $this->postJson($this->baseUrl().'/pay', $payload, $this->headers());

        $refId = $response['refId'] ?? null;

        if (! $refId) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['message'] ?? 'شناسهٔ پرداخت دریافت نشد.'),
                $response
            );
        }

        return PaymentResponse::redirect(
            $this->baseUrl().'/pay/goto/'.$refId,
            (string) $refId,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $refId = (string) $this->value($callback, ['refid', 'refId'], $transaction->reference);

        if ($refId === '') {
            return VerificationResult::failure('شناسهٔ پرداخت در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = $this->postJson($this->baseUrl().'/pay/verify', [
            'refId' => $refId,
            'amount' => (int) $transaction->amount,
        ], $this->headers());

        // پی‌پینگ در موفقیت بدنهٔ حاوی amount برمی‌گرداند و در خطا فیلد message
        if (isset($response['message']) && ! isset($response['amount'])) {
            return VerificationResult::failure((string) $response['message'], $response, $refId);
        }

        if (! $this->amountMatches($transaction, $response['amount'] ?? null)) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $refId);
        }

        return VerificationResult::success(
            referenceId: $refId,
            trackingCode: isset($response['cardNumber']) ? (string) $response['cardNumber'] : null,
            cardNumber: $response['cardNumber'] ?? null,
            amount: $this->fromGatewayAmount($response['amount'] ?? $transaction->amount),
            raw: $response
        );
    }
}
