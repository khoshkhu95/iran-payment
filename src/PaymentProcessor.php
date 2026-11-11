<?php

namespace Taha20\IranPayment;

use Illuminate\Support\Str;
use Throwable;
use Taha20\IranPayment\Contracts\TransactionStore;
use Taha20\IranPayment\Enums\TransactionStatus;
use Taha20\IranPayment\Events\PaymentFailed;
use Taha20\IranPayment\Events\PaymentInitiated;
use Taha20\IranPayment\Events\PaymentRefunded;
use Taha20\IranPayment\Events\PaymentSucceeded;
use Taha20\IranPayment\Exceptions\ConfigurationException;
use Taha20\IranPayment\Exceptions\InvalidPaymentException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\Payment;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\RefundResult;

/**
 * مغز عملیات پرداخت.
 *
 * کنترلر داخلی پکیج و فاساد IranPayment هر دو از همین کلاس استفاده می‌کنند؛
 * بنابراین اگر کنترلر خودتان را بنویسید، دقیقاً همان رفتار و همان رویدادها
 * را دریافت می‌کنید. این کلاس مستقل از HTTP است و در Job/Command/تست هم
 * قابل استفاده است.
 */
class PaymentProcessor
{
    public function __construct(
        protected PaymentManager $manager,
        protected TransactionStore $store,
    ) {}

    /**
     * لایهٔ ذخیره‌سازی فعال (برای کوئری‌های اختصاصی پروژه).
     */
    public function store(): TransactionStore
    {
        return $this->store;
    }

    /**
     * شروع یک پرداخت: ساخت تراکنش، فراخوانی درگاه و برگرداندن پاسخ.
     *
     * @throws Throwable
     */
    public function start(Payment $payment, ?string $gateway = null): PaymentResponse
    {
        $name = $gateway ?: $this->manager->resolveDefault();
        $driver = $this->manager->gateway($name);

        $uuid = (string) Str::uuid();
        $callbackUrl = $payment->callbackUrl() ?: $this->defaultCallbackUrl($name, $uuid);

        $transaction = $this->store->create(
            $payment->toTransactionAttributes($name, $uuid, $callbackUrl)
        );

        event(new PaymentInitiated($transaction));

        try {
            $response = $driver->purchase($transaction);
        } catch (Throwable $e) {
            $this->store->update($transaction, [
                'status' => TransactionStatus::Failed,
                'message' => mb_substr($e->getMessage(), 0, 500),
            ]);

            event(new PaymentFailed($transaction, $e));

            throw $e;
        }

        $this->store->update($transaction, [
            'reference' => $response->reference ?: $transaction->reference,
            'gateway_response' => ['purchase' => $response->toArray()],
        ]);

        return $response;
    }

    /**
     * پردازش بازگشت کاربر از درگاه و تأیید تراکنش.
     *
     * این متد idempotent است: اگر تراکنش قبلاً تأیید شده باشد، دوباره با درگاه
     * تماس گرفته نمی‌شود. این موضوع برای وقتی مهم است که کاربر صفحه را refresh
     * کند یا درگاه callback را دو بار بفرستد.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws Throwable
     */
    public function handleCallback(string $gateway, array $payload, ?string $uuid = null): Transaction
    {
        $driver = $this->manager->gateway($gateway);
        $transaction = $this->resolveTransaction($gateway, $payload, $uuid);

        if ($transaction->isSuccessful()) {
            return $transaction;
        }

        try {
            $result = $driver->verify($transaction, $payload);
        } catch (Throwable $e) {
            $this->store->update($transaction, [
                'status' => TransactionStatus::Failed,
                'message' => mb_substr($e->getMessage(), 0, 500),
                'gateway_response' => ['verify_error' => mb_substr($e->getMessage(), 0, 1000)],
            ]);

            event(new PaymentFailed($transaction, $e));

            throw $e;
        }

        $attributes = [
            'status' => $result->success
                ? TransactionStatus::Paid
                : ($result->canceled ? TransactionStatus::Canceled : TransactionStatus::Failed),
            'reference' => $result->referenceId ?: $transaction->reference,
            'tracking_code' => $result->trackingCode ?: $transaction->tracking_code,
            'card_number' => $result->cardNumber ?: $transaction->card_number,
            'message' => $result->message,
            'gateway_response' => ['verify' => $result->raw],
        ];

        if ($result->success) {
            $attributes['paid_at'] = now();
            $attributes['verified_at'] = now();
        }

        $transaction = $this->store->update($transaction, $attributes);

        if ($result->success) {
            event(new PaymentSucceeded($transaction, $result));
        } else {
            event(new PaymentFailed($transaction, null, $result));
        }

        return $transaction;
    }

    /**
     * بازگشت وجه یک تراکنش موفق.
     */
    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        $driver = $this->manager->gateway($transaction->gateway);

        if (! $driver->supportsRefund()) {
            return RefundResult::failure('این درگاه از بازگشت وجه پشتیبانی نمی‌کند.');
        }

        if (! $transaction->isSuccessful()) {
            return RefundResult::failure('فقط تراکنش موفق قابل بازگشت است.');
        }

        $result = $driver->refund($transaction, $amount);

        if ($result->success) {
            $this->store->update($transaction, [
                'status' => TransactionStatus::Refunded,
                'refunded_at' => now(),
            ]);

            event(new PaymentRefunded($transaction, $result));
        }

        return $result;
    }

    /**
     * پیدا کردن تراکنش مربوط به این بازگشت.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function resolveTransaction(string $gateway, array $payload, ?string $uuid): Transaction
    {
        if ($uuid) {
            $transaction = $this->store->findByUuid($uuid);

            if ($transaction) {
                return $transaction;
            }
        }

        // اگر آدرس بازگشت سفارشی باشد و uuid نداشته باشد، از شمارهٔ سفارش
        // که درگاه‌ها با نام‌های مختلف برمی‌گردانند استفاده می‌کنیم.
        $orderId = $this->extractOrderId($payload);

        if ($orderId !== null) {
            $transaction = $this->store->findByOrderId($orderId, $gateway);

            if ($transaction) {
                return $transaction;
            }
        }

        throw InvalidPaymentException::transactionNotFound($uuid ?: (string) $orderId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractOrderId(array $payload): ?string
    {
        foreach (['order_id', 'orderId', 'OrderId', 'ResNum', 'resNum', 'factorNumber', 'clientrefid', 'iN', 'invoice_id'] as $key) {
            if (! empty($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    /**
     * ساخت آدرس بازگشت پیش‌فرض پکیج.
     */
    protected function defaultCallbackUrl(string $gateway, string $uuid): string
    {
        if (! config('iran-payment.routes.enabled', true)) {
            throw new ConfigurationException(
                'روت‌های پکیج غیرفعال است؛ باید آدرس بازگشت را با Payment::withCallbackUrl() تعیین کنید.'
            );
        }

        return route('iran-payment.callback', ['gateway' => $gateway, 'uuid' => $uuid]);
    }
}
