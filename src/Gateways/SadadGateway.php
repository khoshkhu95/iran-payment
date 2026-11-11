<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\Signer;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه مستقیم سداد (بانک ملی ایران).
 *
 * درخواست‌ها با کلید خصوصی پذیرنده امضا می‌شوند:
 *   مرحلهٔ درخواست: sign("{TerminalId};{OrderId};{Amount}")
 *   مرحلهٔ تأیید  : sign("{Token};{ResCode}")
 *
 * @see https://sadad.shaparak.ir
 */
class SadadGateway extends AbstractGateway
{
    protected function requestUrl(): string
    {
        return (string) $this->config('request_url', 'https://sadad.shaparak.ir/VPG/api/v0/Request/PaymentRequest');
    }

    protected function verifyUrl(): string
    {
        return (string) $this->config('verify_url', 'https://sadad.shaparak.ir/VPG/api/v0/Advice/Verify');
    }

    protected function sign(string $data): string
    {
        return Signer::sign($data, $this->require('private_key'), (string) $this->config('key_password', ''));
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $terminalId = $this->require('terminal_id');
        $orderId = $this->numericOrderId($transaction);
        $amount = (int) $transaction->amount;

        $response = $this->postJson($this->requestUrl(), [
            'TerminalId' => $terminalId,
            'MerchantId' => $this->require('merchant_id'),
            'Amount' => $amount,
            'LocalDateTime' => now()->format('m/d/Y H:i:s'),
            'OrderId' => $orderId,
            'SignData' => $this->sign($terminalId.';'.$orderId.';'.$amount),
            'AdditionalData' => (string) $transaction->order_id,
        ]);

        $resCode = (int) ($response['ResCode'] ?? -1);

        if ($resCode !== 0 || empty($response['Token'])) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['Description'] ?? 'کد خطا: '.$resCode),
                $response
            );
        }

        $token = (string) $response['Token'];

        return PaymentResponse::redirect(
            (string) $this->config('payment_url', 'https://sadad.shaparak.ir/VPG/Purchase').'?Token='.urlencode($token),
            $token,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $resCode = (int) $this->value($callback, ['ResCode', 'resCode'], -1);

        if ($resCode !== 0) {
            return VerificationResult::failure(
                'پرداخت ناموفق بود یا لغو شد. کد: '.$resCode,
                $callback
            );
        }

        $token = (string) $this->value($callback, ['Token', 'token'], $transaction->reference);

        if ($token === '') {
            return VerificationResult::failure('توکن تراکنش در بازگشت از درگاه یافت نشد.', $callback);
        }

        $response = $this->postJson($this->verifyUrl(), [
            'Token' => $token,
            'SignData' => $this->sign($token.';'.$resCode),
        ]);

        $verifyCode = (int) ($response['ResCode'] ?? -1);

        if ($verifyCode !== 0) {
            return VerificationResult::failure(
                (string) ($response['Description'] ?? 'تأیید پرداخت انجام نشد. کد: '.$verifyCode),
                $response,
                $token
            );
        }

        if (! $this->amountMatches($transaction, $response['Amount'] ?? null)) {
            return VerificationResult::failure('مبلغ تأییدشده با مبلغ سفارش هم‌خوانی ندارد.', $response, $token);
        }

        return VerificationResult::success(
            referenceId: isset($response['RetrivalRefNo']) ? (string) $response['RetrivalRefNo'] : $token,
            trackingCode: isset($response['SystemTraceNo']) ? (string) $response['SystemTraceNo'] : null,
            cardNumber: $callback['CardHolderPan'] ?? null,
            amount: $this->fromGatewayAmount($response['Amount'] ?? $transaction->amount),
            raw: $response
        );
    }
}
