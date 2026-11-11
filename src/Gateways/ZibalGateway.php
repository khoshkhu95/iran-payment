<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه زیبال
 *
 * @see https://docs.zibal.ir/
 */
class ZibalGateway extends AbstractGateway
{
    private const CODE_SUCCESS = 100;

    private const CODE_ALREADY_VERIFIED = 201;

    protected function baseUrl(): string
    {
        return 'https://gateway.zibal.ir';
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $payload = [
            'merchant' => $this->require('merchant'),
            'amount' => (int) $transaction->amount,
            'callbackUrl' => $this->callbackUrl($transaction),
            'description' => $transaction->description ?: 'پرداخت سفارش '.$transaction->order_id,
            'orderId' => (string) $transaction->order_id,
        ];

        if ($transaction->mobile) {
            $payload['mobile'] = $transaction->mobile;
        }

        if ($transaction->email) {
            $payload['email'] = $transaction->email;
        }

        $response = $this->postJson($this->baseUrl().'/v1/request', $payload);

        if ((int) ($response['result'] ?? -1) !== self::CODE_SUCCESS) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['message'] ?? 'کد نتیجه: '.($response['result'] ?? '?')),
                $response
            );
        }

        $trackId = (string) ($response['trackId'] ?? '');

        return PaymentResponse::redirect(
            $this->baseUrl().'/start/'.$trackId,
            $trackId,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        // زیبال در حالت ناموفق success=0 و در حالت موفق success=1 برمی‌گرداند
        $success = (string) $this->value($callback, ['success'], '1');

        if ($success === '0') {
            return VerificationResult::canceled('پرداخت توسط کاربر لغو شد یا ناموفق بود.', $callback);
        }

        $trackId = (string) $this->value($callback, ['trackId', 'track_id'], $transaction->reference);

        if ($trackId === '') {
            return VerificationResult::failure('شناسهٔ پیگیری در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = $this->postJson($this->baseUrl().'/v1/verify', [
            'merchant' => $this->require('merchant'),
            'trackId' => is_numeric($trackId) ? (int) $trackId : $trackId,
        ]);

        $result = (int) ($response['result'] ?? -1);

        if (! in_array($result, [self::CODE_SUCCESS, self::CODE_ALREADY_VERIFIED], true)) {
            return VerificationResult::failure(
                (string) ($response['message'] ?? 'تأیید پرداخت انجام نشد.'),
                $response,
                $trackId
            );
        }

        if (! $this->amountMatches($transaction, $response['amount'] ?? null)) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $trackId);
        }

        return VerificationResult::success(
            referenceId: isset($response['refNumber']) ? (string) $response['refNumber'] : $trackId,
            trackingCode: isset($response['refNumber']) ? (string) $response['refNumber'] : null,
            cardNumber: $response['cardNumber'] ?? null,
            amount: $this->fromGatewayAmount($response['amount'] ?? $transaction->amount),
            message: $result === self::CODE_ALREADY_VERIFIED
                ? 'این تراکنش قبلاً تأیید شده بود.'
                : 'پرداخت با موفقیت تأیید شد.',
            raw: $response
        );
    }
}
