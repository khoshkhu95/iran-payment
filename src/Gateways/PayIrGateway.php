<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه پی‌آی‌آر (pay.ir)
 *
 * @see https://pay.ir/docs/gateway
 */
class PayIrGateway extends AbstractGateway
{
    protected function baseUrl(): string
    {
        return 'https://pay.ir/pg';
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $payload = [
            'api' => $this->require('api_key'),
            'amount' => (int) $transaction->amount,
            'redirect' => $this->callbackUrl($transaction),
            'factorNumber' => (string) $transaction->order_id,
            'description' => $transaction->description ?: 'پرداخت سفارش '.$transaction->order_id,
        ];

        if ($transaction->mobile) {
            $payload['mobile'] = $transaction->mobile;
        }

        $response = $this->postJson($this->baseUrl().'/send', $payload);

        if ((int) ($response['status'] ?? 0) !== 1 || empty($response['token'])) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['errorMessage'] ?? $response['message'] ?? 'توکن پرداخت دریافت نشد.'),
                $response
            );
        }

        $token = (string) $response['token'];

        return PaymentResponse::redirect(
            $response['url'] ?? $this->baseUrl().'/'.$token,
            $token,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $status = (string) $this->value($callback, ['status'], '1');

        if ($status === '0') {
            return VerificationResult::canceled('پرداخت توسط کاربر لغو شد یا ناموفق بود.', $callback);
        }

        $token = (string) $this->value($callback, ['token'], $transaction->reference);

        if ($token === '') {
            return VerificationResult::failure('توکن پرداخت در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = $this->postJson($this->baseUrl().'/verify', [
            'api' => $this->require('api_key'),
            'token' => $token,
        ]);

        if ((int) ($response['status'] ?? 0) !== 1) {
            return VerificationResult::failure(
                (string) ($response['errorMessage'] ?? $response['message'] ?? 'تأیید پرداخت انجام نشد.'),
                $response,
                $token
            );
        }

        if (! $this->amountMatches($transaction, $response['amount'] ?? null)) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $token);
        }

        return VerificationResult::success(
            referenceId: isset($response['transId']) ? (string) $response['transId'] : $token,
            trackingCode: isset($response['transId']) ? (string) $response['transId'] : null,
            cardNumber: $response['cardNumber'] ?? null,
            amount: $this->fromGatewayAmount($response['amount'] ?? $transaction->amount),
            raw: $response
        );
    }
}
