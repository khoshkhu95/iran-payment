<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه پی‌استار
 *
 * امضای درخواست‌ها با md5 بر اساس الگوی مستندات پی‌استار ساخته می‌شود:
 *   create: md5(amount#order_id#callback#gateway_id)
 *   verify: md5(amount#ref_num#gateway_id)
 *
 * @see https://paystar.ir
 */
class PayStarGateway extends AbstractGateway
{
    protected function baseUrl(): string
    {
        return 'https://core.paystar.ir/api/pardakht';
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
        $amount = (int) $transaction->amount;
        $orderId = (string) $transaction->order_id;
        $callback = $this->callbackUrl($transaction);
        $gatewayId = $this->require('gateway_id');

        $payload = [
            'amount' => $amount,
            'order_id' => $orderId,
            'callback' => $callback,
            'sign' => md5($amount.'#'.$orderId.'#'.$callback.'#'.$gatewayId),
        ];

        $response = $this->postJson($this->baseUrl().'/create', $payload, $this->headers());

        $token = data_get($response, 'data.token');
        $refNum = data_get($response, 'data.ref_num');

        if ((int) ($response['status'] ?? 0) !== 1 || ! $token) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['message'] ?? 'توکن پرداخت دریافت نشد.'),
                $response
            );
        }

        return PaymentResponse::form(
            (string) $this->config('payment_url', 'https://core.paystar.ir/api/pardakht/payment'),
            ['token' => (string) $token],
            $refNum !== null ? (string) $refNum : (string) $token,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $refNum = (string) $this->value($callback, ['ref_num', 'refNum'], $transaction->reference);

        if ($refNum === '') {
            return VerificationResult::failure('شناسهٔ پرداخت در بازگشت از درگاه یافت نشد.', $callback);
        }

        $amount = (int) $transaction->amount;

        $response = $this->postJson($this->baseUrl().'/verify', [
            'ref_num' => $refNum,
            'amount' => $amount,
            'sign' => md5($amount.'#'.$refNum.'#'.$this->require('gateway_id')),
        ], $this->headers());

        if ((int) ($response['status'] ?? 0) !== 1) {
            return VerificationResult::failure(
                (string) ($response['message'] ?? 'تأیید پرداخت انجام نشد.'),
                $response,
                $refNum
            );
        }

        if (! $this->amountMatches($transaction, data_get($response, 'data.amount'))) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $refNum);
        }

        return VerificationResult::success(
            referenceId: $refNum,
            trackingCode: (string) (data_get($response, 'data.tracking_code') ?? $refNum),
            cardNumber: data_get($response, 'data.card_number'),
            amount: $this->fromGatewayAmount(data_get($response, 'data.amount') ?? $transaction->amount),
            raw: $response
        );
    }
}
