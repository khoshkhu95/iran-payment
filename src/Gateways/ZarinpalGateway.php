<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه زرین‌پال - API نسخهٔ ۴
 *
 * @see https://docs.zarinpal.com/paymentGateway/connect.html
 */
class ZarinpalGateway extends AbstractGateway
{
    private const CODE_SUCCESS = 100;

    private const CODE_ALREADY_VERIFIED = 101;

    protected function baseUrl(): string
    {
        return $this->bool('sandbox')
            ? 'https://sandbox.zarinpal.com/pg'
            : 'https://payment.zarinpal.com/pg';
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $payload = [
            'merchant_id' => $this->require('merchant_id'),
            'amount' => (int) $transaction->amount,
            'callback_url' => $this->callbackUrl($transaction),
            'description' => $transaction->description ?: 'پرداخت سفارش '.$transaction->order_id,
            'currency' => 'IRR',
        ];

        if ($transaction->mobile) {
            $payload['metadata']['mobile'] = $transaction->mobile;
        }

        if ($transaction->email) {
            $payload['metadata']['email'] = $transaction->email;
        }

        $response = $this->postJson($this->baseUrl().'/v4/payment/request.json', $payload);

        $code = (int) data_get($response, 'data.code', 0);
        $authority = data_get($response, 'data.authority');

        if ($code !== self::CODE_SUCCESS || ! $authority) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) data_get($response, 'errors.message', 'کد خطا: '.$code),
                $response
            );
        }

        return PaymentResponse::redirect(
            $this->baseUrl().'/StartPay/'.$authority,
            (string) $authority,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $status = strtoupper((string) $this->value($callback, ['Status', 'status'], ''));

        if ($status !== '' && $status !== 'OK') {
            return VerificationResult::canceled('پرداخت توسط کاربر لغو شد.', $callback);
        }

        $authority = (string) $this->value($callback, ['Authority', 'authority'], $transaction->reference);

        if ($authority === '') {
            return VerificationResult::failure('شناسهٔ پرداخت در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = $this->postJson($this->baseUrl().'/v4/payment/verify.json', [
            'merchant_id' => $this->require('merchant_id'),
            'amount' => (int) $transaction->amount,
            'authority' => $authority,
        ]);

        $code = (int) data_get($response, 'data.code', 0);

        if (! in_array($code, [self::CODE_SUCCESS, self::CODE_ALREADY_VERIFIED], true)) {
            return VerificationResult::failure(
                (string) data_get($response, 'errors.message', 'تأیید پرداخت انجام نشد.'),
                $response,
                $authority
            );
        }

        $refId = data_get($response, 'data.ref_id');

        return VerificationResult::success(
            referenceId: $refId !== null ? (string) $refId : $authority,
            trackingCode: $refId !== null ? (string) $refId : null,
            cardNumber: data_get($response, 'data.card_pan'),
            amount: (int) $transaction->amount,
            message: $code === self::CODE_ALREADY_VERIFIED
                ? 'این تراکنش قبلاً تأیید شده بود.'
                : 'پرداخت با موفقیت تأیید شد.',
            raw: $response
        );
    }

    public function supportsRefund(): bool
    {
        return false;
    }
}
