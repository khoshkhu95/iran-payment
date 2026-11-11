<?php

namespace Taha20\IranPayment\Gateways;

use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\RefundResult;
use Taha20\IranPayment\Support\Soap;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * درگاه مستقیم به‌پرداخت ملت (SOAP).
 *
 * جریان کار: bpPayRequest → ریدایرکت با فرم به startpay → bpVerifyRequest
 * → bpSettleRequest. اگر settle انجام نشود، مبلغ به‌صورت خودکار (پس از
 * مهلت مشخص) به حساب مشتری برمی‌گردد؛ به همین دلیل بعد از verify موفق
 * حتماً settle هم صدا زده می‌شود.
 */
class MellatGateway extends AbstractGateway
{
    protected function endpoint(): string
    {
        return (string) $this->config('endpoint', 'https://bpm.shaparak.ir/pgwchannel/services/pgw');
    }

    protected function namespace(): string
    {
        return (string) $this->config('namespace', 'http://interfaces.core.sw.bps.com/');
    }

    public function purchase(Transaction $transaction): PaymentResponse
    {
        $response = Soap::call($this->endpoint(), 'bpPayRequest', [
            'terminalId' => $this->require('terminal_id'),
            'userName' => $this->require('username'),
            'userPassword' => $this->require('password'),
            'orderId' => $this->numericOrderId($transaction),
            'amount' => (int) $transaction->amount,
            'localDate' => now()->format('Ymd'),
            'localTime' => now()->format('His'),
            'additionalData' => (string) $transaction->order_id,
            'callBackUrl' => $this->callbackUrl($transaction),
            'payerId' => '0',
        ], $this->namespace(), $this->name());

        $result = (string) ($response['_value'] ?? '');
        [$code, $refId] = array_pad(explode(',', $result), 2, '');

        if ((int) trim($code) !== 0 || trim($refId) === '') {
            throw GatewayRequestException::fromResponse(
                $this->name(),
                'کد خطای bpPayRequest: '.trim($code),
                $response
            );
        }

        return PaymentResponse::form(
            (string) $this->config('payment_url', 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat'),
            array_filter([
                'RefId' => trim($refId),
                'MobileNo' => $transaction->mobile,
            ]),
            trim($refId),
            $response
        );
    }

    public function verify(Transaction $transaction, array $callback): VerificationResult
    {
        $resCode = (int) $this->value($callback, ['ResCode'], -1);

        if ($resCode !== 0) {
            $message = $this->resCodeMessage($resCode);

            return $resCode === 17
                ? VerificationResult::canceled($message, $callback)
                : VerificationResult::failure($message, $callback);
        }

        $saleOrderId = (string) $this->value($callback, ['SaleOrderId', 'saleOrderId'], '');
        $saleReferenceId = (string) $this->value($callback, ['SaleReferenceId', 'saleReferenceId'], '');

        if ($saleOrderId === '' || $saleReferenceId === '') {
            return VerificationResult::failure('اطلاعات مرجع تراکنش در بازگشت از درگاه ناقص است.', $callback);
        }

        $verifyCode = $this->request('bpVerifyRequest', $saleOrderId, $saleReferenceId, $transaction);

        if ($verifyCode !== 0) {
            // اگر verify شکست خورد، تراکنش را معکوس می‌کنیم تا پول بلوکه نماند
            $this->request('bpReversalRequest', $saleOrderId, $saleReferenceId, $transaction);

            return VerificationResult::failure(
                'تأیید تراکنش ملت انجام نشد. کد: '.$verifyCode,
                ['verify_code' => $verifyCode],
                $saleReferenceId
            );
        }

        $settleCode = $this->request('bpSettleRequest', $saleOrderId, $saleReferenceId, $transaction);

        return VerificationResult::success(
            referenceId: $saleReferenceId,
            trackingCode: $saleReferenceId,
            cardNumber: $this->value($callback, ['CardHolderPan', 'CardHolderInfo']),
            amount: (int) $transaction->amount,
            message: $settleCode === 0
                ? 'پرداخت با موفقیت تأیید و تسویه شد.'
                : 'پرداخت تأیید شد ولی تسویه انجام نشد (کد '.$settleCode.').',
            raw: ['verify' => $verifyCode, 'settle' => $settleCode] + $callback
        );
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        $saleOrderId = (string) ($transaction->meta['sale_order_id'] ?? $this->numericOrderId($transaction));
        $saleReferenceId = (string) $transaction->reference;

        if ($saleReferenceId === '') {
            return RefundResult::failure('شناسهٔ مرجع تراکنش برای بازگشت وجه موجود نیست.');
        }

        $code = $this->request('bpReversalRequest', $saleOrderId, $saleReferenceId, $transaction);

        return $code === 0
            ? RefundResult::success($saleReferenceId, 'بازگشت وجه انجام شد.', ['code' => $code])
            : RefundResult::failure('بازگشت وجه انجام نشد. کد: '.$code, ['code' => $code]);
    }

    /**
     * فراخوانی متدهای SOAP که امضای یکسانی دارند.
     */
    protected function request(string $operation, string $saleOrderId, string $saleReferenceId, Transaction $transaction): int
    {
        $response = Soap::call($this->endpoint(), $operation, [
            'terminalId' => $this->require('terminal_id'),
            'userName' => $this->require('username'),
            'userPassword' => $this->require('password'),
            'orderId' => $this->numericOrderId($transaction),
            'saleOrderId' => $saleOrderId,
            'saleReferenceId' => $saleReferenceId,
        ], $this->namespace(), $this->name());

        return (int) trim((string) ($response['_value'] ?? '-1'));
    }

    protected function resCodeMessage(int $code): string
    {
        return match ($code) {
            11 => 'کارت نامعتبر است.',
            12 => 'موجودی کافی نیست.',
            13 => 'رمز کارت اشتباه است.',
            14 => 'تعداد دفعات ورود رمز بیش از حد مجاز است.',
            15 => 'کارت منقضی شده است.',
            16 => 'مبلغ برداشت بیش از حد مجاز است.',
            17 => 'کارت پذیرنده نامعتبر است.',
            18 => 'تراکنش ناموفق بود.',
            21 => 'پذیرنده نامعتبر است.',
            23 => 'خطای امنیتی رخ داده است.',
            24 => 'اطلاعات کاربری پذیرنده نامعتبر است.',
            25 => 'مبلغ نامعتبر است.',
            31 => 'پاسخ نامعتبر است.',
            32 => 'اطلاعات نامعتبر است.',
            33 => 'حساب نامعتبر است.',
            34 => 'خطای سیستمی رخ داده است.',
            35 => 'تاریخ نامعتبر است.',
            41 => 'شمارهٔ درخواست تکراری است.',
            42 => 'تراکنش فروش یافت نشد.',
            43 => 'درخواست verify تکراری است.',
            44 => 'تراکنش verify یافت نشد.',
            45 => 'تراکنش settle شده است.',
            46 => 'تراکنش settle نشده است.',
            47 => 'تراکنش settle یافت نشد.',
            48 => 'تراکنش reversal شده است.',
            49 => 'تراکنش reversal یافت نشد.',
            412 => 'شناسهٔ قبض نامعتبر است.',
            413 => 'مبلغ پرداخت قبض نامعتبر است.',
            414 => 'قبض پرداخت شده است.',
            415 => 'زمان پرداخت قبض به پایان رسیده است.',
            416 => 'خطای سیستمی در پرداخت قبض.',
            417 => 'شناسهٔ پرداخت قبض نامعتبر است.',
            418 => 'خطای سیستمی در پرداخت قبض.',
            419 => 'خطای سیستمی در پرداخت قبض.',
            421 => 'درخواست تکراری است.',
            0 => 'تراکنش موفق.',
            default => 'پرداخت انجام نشد. کد خطا: '.$code,
        };
    }
}
