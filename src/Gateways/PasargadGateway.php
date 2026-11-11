<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\Signer;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه مستقیم بانک پاسارگاد (PEP).
 *
 * داده‌ها با کلید خصوصی RSA پذیرنده امضا می‌شوند و نتیجهٔ تأیید هم با همان
 * کلید اعتبارسنجی می‌شود. فایل کلید را در config با کلید private_key
 * (مسیر فایل یا محتوای PEM) معرفی کنید.
 */
class PasargadGateway extends AbstractGateway
{
    private const ACTION_PURCHASE = 1003;

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $amount = (int) $transaction->amount;
        $invoiceNumber = $this->numericOrderId($transaction);
        $invoiceDate = now()->format('Y/m/d H:i:s');
        $timestamp = now()->format('Y/m/d H:i:s');
        $terminalCode = $this->require('terminal_code');
        $merchantCode = $this->require('merchant_code');
        $redirect = $this->callbackUrl($transaction);

        $data = 'amount='.$amount
            .',invoiceNumber='.$invoiceNumber
            .',invoiceDate='.$invoiceDate
            .',terminalCode='.$terminalCode
            .',merchantCode='.$merchantCode
            .',redirectAddress='.$redirect
            .',timestamp='.$timestamp
            .',action='.self::ACTION_PURCHASE;

        $sign = Signer::sign($data, $this->require('private_key'), (string) $this->config('key_password', ''));

        $response = ['sign' => $sign, 'data' => $data];

        return PaymentResponse::form(
            (string) $this->config('payment_url'),
            [
                'InvoiceNumber' => $invoiceNumber,
                'InvoiceDate' => $invoiceDate,
                'TerminalCode' => $terminalCode,
                'MerchantCode' => $merchantCode,
                'Amount' => $amount,
                'RedirectAddress' => $redirect,
                'Timestamp' => $timestamp,
                'Action' => self::ACTION_PURCHASE,
                'Sign' => $sign,
            ],
            $invoiceNumber,
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        // پاسارگاد در بازگشت، شمارهٔ فاکتور را با iN و تاریخ را با iD می‌فرستد
        $invoiceNumber = (string) $this->value($callback, ['iN', 'InvoiceNumber'], '');
        $invoiceDate = (string) $this->value($callback, ['iD', 'InvoiceDate'], '');

        if ($invoiceNumber === '' || $invoiceDate === '') {
            return VerificationResult::failure('اطلاعات فاکتور در بازگشت از درگاه ناقص است.', $callback);
        }

        $amount = (int) $transaction->amount;
        $merchantCode = $this->require('merchant_code');
        $terminalCode = $this->require('terminal_code');
        $timestamp = now()->format('Y/m/d H:i:s');

        $data = 'invoiceNumber='.$invoiceNumber
            .',invoiceDate='.$invoiceDate
            .',merchantCode='.$merchantCode
            .',terminalCode='.$terminalCode
            .',amount='.$amount
            .',timestamp='.$timestamp;

        $sign = Signer::sign($data, $this->require('private_key'), (string) $this->config('key_password', ''));

        $response = $this->postJson((string) $this->config('verify_url'), [
            'InvoiceNumber' => $invoiceNumber,
            'InvoiceDate' => $invoiceDate,
            'MerchantCode' => $merchantCode,
            'TerminalCode' => $terminalCode,
            'Amount' => $amount,
            'TimeStamp' => $timestamp,
            'Sign' => $sign,
        ]);

        if (! ($response['IsSuccess'] ?? false)) {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                (string) ($response['Message'] ?? 'تأیید تراکنش پاسارگاد انجام نشد.'),
                $response
            );
        }

        $referenceId = (string) $this->value($callback, ['tref', 'Treq', 'TransactionReferenceID'], $invoiceNumber);

        return VerificationResult::success(
            referenceId: $referenceId,
            trackingCode: $referenceId,
            cardNumber: $callback['cardNumberMasked'] ?? null,
            amount: $this->fromGatewayAmount(data_get($response, 'Data.Amount') ?? $transaction->amount),
            raw: $response
        );
    }
}
