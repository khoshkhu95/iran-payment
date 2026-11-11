<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه آقای پرداخت - API نسخهٔ ۲
 *
 * توجه: این درگاه مبلغ را «تومان» می‌گیرد؛ در config با
 * 'amount_unit' => 'toman' مشخص شده و تبدیل خودکار انجام می‌شود.
 *
 * @see https://aqayepardakht.ir/api-document
 */
class AqayePardakhtGateway extends AbstractGateway
{
    protected function baseUrl(): string
    {
        return 'https://panel.aqayepardakht.ir/api/v2';
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $payload = [
            'pin' => $this->require('pin'),
            'amount' => $this->amountFor($transaction),
            'callback' => $this->callbackUrl($transaction),
            'invoice_id' => (string) $transaction->order_id,
            'description' => $transaction->description ?: 'پرداخت سفارش '.$transaction->order_id,
        ];

        $response = $this->postJson($this->baseUrl().'/create', $payload);

        $status = strtolower((string) ($response['status'] ?? ''));

        if ($status !== 'success' || empty($response['transid'])) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['message'] ?? 'تراکنش ایجاد نشد.'),
                $response
            );
        }

        $transId = (string) $response['transid'];

        return PaymentResponse::redirect(
            'https://panel.aqayepardakht.ir/startpay/'.$transId,
            $transId,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $transId = (string) $this->value($callback, ['transid', 'trans_id'], $transaction->reference);

        if ($transId === '') {
            return VerificationResult::failure('شناسهٔ تراکنش در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = $this->postJson($this->baseUrl().'/verify', [
            'pin' => $this->require('pin'),
            'amount' => $this->amountFor($transaction),
            'transid' => $transId,
        ]);

        $status = strtolower((string) ($response['status'] ?? ''));

        if ($status !== 'success') {
            return VerificationResult::failure(
                (string) ($response['message'] ?? 'تأیید پرداخت انجام نشد.'),
                $response,
                $transId
            );
        }

        return VerificationResult::success(
            referenceId: $transId,
            trackingCode: isset($response['code']) ? (string) $response['code'] : null,
            cardNumber: $response['card_number'] ?? null,
            amount: (int) $transaction->amount,
            raw: $response
        );
    }
}
