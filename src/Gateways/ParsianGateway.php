<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\Soap;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه مستقیم بانک پارسیان (PEC).
 */
class ParsianGateway extends AbstractGateway
{
    protected function namespace(): string
    {
        return (string) $this->config('namespace', 'https://pec.shaparak.ir/');
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $response = Soap::call(
            (string) $this->config('sale_url'),
            'SalePaymentRequest',
            [
                'requestData' => [
                    'LoginAccount' => $this->require('login_account'),
                    'Amount' => (int) $transaction->amount,
                    'OrderId' => $this->numericOrderId($transaction),
                    'CallBackUrl' => $this->callbackUrl($transaction),
                    'AdditionalData' => (string) $transaction->order_id,
                    'Originator' => $transaction->mobile ?: '',
                ],
            ],
            $this->namespace(),
            $this->name()
        );

        $status = (int) ($response['Status'] ?? -1);
        $token = (string) ($response['Token'] ?? '');

        if ($status !== 0 || $token === '') {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                'کد وضعیت پارسیان: '.$status,
                $response
            );
        }

        return PaymentResponse::form(
            (string) $this->config('payment_url'),
            ['Token' => $token],
            $token,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $status = (int) $this->value($callback, ['status', 'Status'], -1);

        if ($status !== 0) {
            return VerificationResult::canceled('پرداخت توسط کاربر لغو شد یا ناموفق بود.', $callback);
        }

        $token = (string) $this->value($callback, ['Token', 'token'], $transaction->reference);

        if ($token === '') {
            return VerificationResult::failure('توکن تراکنش در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = Soap::call(
            (string) $this->config('confirm_url'),
            'ConfirmPayment',
            [
                'requestData' => [
                    'LoginAccount' => $this->require('login_account'),
                    'Token' => $token,
                    'status' => $status,
                ],
            ],
            $this->namespace(),
            $this->name()
        );

        $confirmStatus = (int) ($response['Status'] ?? -1);

        if ($confirmStatus !== 0) {
            return VerificationResult::failure(
                'تأیید تراکنش پارسیان انجام نشد. کد: '.$confirmStatus,
                $response,
                $token
            );
        }

        if (! $this->amountMatches($transaction, $response['Amount'] ?? null)) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $token);
        }

        return VerificationResult::success(
            referenceId: (string) ($response['RRN'] ?? $token),
            trackingCode: (string) ($response['RRN'] ?? $token),
            cardNumber: $response['CardNumberMasked'] ?? null,
            amount: $this->fromGatewayAmount($response['Amount'] ?? $transaction->amount),
            raw: $response
        );
    }
}
