<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه نکست‌پی
 *
 * نکته: نکست‌پی برای «ساخت توکن» کد -۱ و برای «تأیید» کد ۰ را موفق می‌داند.
 *
 * @see https://nextpay.org/nx/docs
 */
class NextPayGateway extends AbstractGateway
{
    private const TOKEN_SUCCESS = -1;

    private const VERIFY_SUCCESS = 0;

    protected function baseUrl(): string
    {
        return 'https://nextpay.org/nx/gateway';
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $payload = [
            'api_key' => $this->require('api_key'),
            'order_id' => (string) $transaction->order_id,
            'amount' => (int) $transaction->amount,
            'callback_uri' => $this->callbackUrl($transaction),
            'currency' => 'IRR',
            'affordable' => 1,
        ];

        if ($transaction->mobile) {
            $payload['customer_phone'] = $transaction->mobile;
        }

        $response = $this->postJson($this->baseUrl().'/token', $payload);

        if ((int) ($response['code'] ?? 0) !== self::TOKEN_SUCCESS) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['message'] ?? 'کد نتیجه: '.($response['code'] ?? '?')),
                $response
            );
        }

        $transId = (string) ($response['trans_id'] ?? '');

        return PaymentResponse::redirect(
            $this->baseUrl().'/payment/'.$transId,
            $transId,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $transId = (string) $this->value($callback, ['trans_id', 'transId'], $transaction->reference);

        if ($transId === '') {
            return VerificationResult::failure('شناسهٔ تراکنش در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = $this->postJson($this->baseUrl().'/verify', [
            'api_key' => $this->require('api_key'),
            'trans_id' => $transId,
            'amount' => (int) $transaction->amount,
            'currency' => 'IRR',
        ]);

        $code = (int) ($response['code'] ?? -1);

        if ($code !== self::VERIFY_SUCCESS) {
            return VerificationResult::failure(
                (string) ($response['message'] ?? 'تأیید پرداخت انجام نشد. کد: '.$code),
                $response,
                $transId
            );
        }

        if (! $this->amountMatches($transaction, $response['amount'] ?? null)) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $transId);
        }

        return VerificationResult::success(
            referenceId: isset($response['ref_id']) ? (string) $response['ref_id'] : $transId,
            trackingCode: isset($response['ref_id']) ? (string) $response['ref_id'] : null,
            cardNumber: $response['card_holder'] ?? null,
            amount: $this->fromGatewayAmount($response['amount'] ?? $transaction->amount),
            raw: $response
        );
    }
}
