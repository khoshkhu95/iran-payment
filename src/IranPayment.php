<?php

namespace Taha20\IranPayment;

use Taha20\IranPayment\Contracts\Gateway;
use Taha20\IranPayment\Contracts\TransactionStore;
use Taha20\IranPayment\Gateways\FakeGateway;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\Payment;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\RefundResult;

/**
 * نقطهٔ ورود اصلی پکیج (همان چیزی که فاساد IranPayment به آن اشاره می‌کند).
 *
 * این کلاس یک لایهٔ نازک روی PaymentManager و PaymentProcessor است تا هم
 * API کوتاه و خوانا داشته باشیم و هم دسترسی به اجزای داخلی ممکن بماند:
 *
 *   IranPayment::pay(Payment::make(50000, 'ORD-1'));
 *   IranPayment::gateway('zibal')->purchase($transaction);
 *   IranPayment::gateways();   // لیست درگاه‌های فعال برای رابط کاربری
 */
class IranPayment
{
    public function __construct(
        protected PaymentManager $manager,
        protected PaymentProcessor $processor,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | دسترسی به اجزا
    |--------------------------------------------------------------------------
    */

    public function manager(): PaymentManager
    {
        return $this->manager;
    }

    public function processor(): PaymentProcessor
    {
        return $this->processor;
    }

    /**
     * گرفتن یک درگاه مشخص (یا درگاه پیش‌فرض).
     */
    public function gateway(?string $name = null): Gateway
    {
        return $this->manager->gateway($name);
    }

    /**
     * لیست درگاه‌های فعال با متادیتای نمایشی.
     *
     * @return array<string, array<string, mixed>>
     */
    public function gateways(): array
    {
        return $this->manager->availableGateways();
    }

    /**
     * تغییر درگاه پیش‌فرض در زمان اجرا.
     */
    public function use(string $gateway): static
    {
        $this->manager->setDefaultDriver($gateway);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | عملیات پرداخت
    |--------------------------------------------------------------------------
    */

    public function pay(Payment $payment, ?string $gateway = null): PaymentResponse
    {
        return $this->processor->start($payment, $gateway);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function callback(string $gateway, array $payload, ?string $uuid = null): Transaction
    {
        return $this->processor->handleCallback($gateway, $payload, $uuid);
    }

    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        return $this->processor->refund($transaction, $amount);
    }

    public function store(): TransactionStore
    {
        return $this->processor->store();
    }

    /*
    |--------------------------------------------------------------------------
    | ابزار تست
    |--------------------------------------------------------------------------
    */

    /**
     * جایگزینی درگاه با درگاه آزمایشی.
     *
     *   IranPayment::fake();                  // نتیجه: موفق
     *   IranPayment::fake('failed');          // نتیجه: ناموفق
     *
     * بعد از این فراخوانی، درگاه پیش‌فرض روی fake تنظیم می‌شود و کل جریان
     * (فرم → بازگشت → تأیید → رویدادها) بدون تماس شبکه‌ای اجرا می‌شود.
     */
    public function fake(string $outcome = 'success'): FakeGateway
    {
        $config = [
            'name' => 'fake',
            'driver' => FakeGateway::class,
            'enabled' => true,
            'title' => 'درگاه آزمایشی',
            'description' => 'فقط برای تست',
            'color' => '#8B5CF6',
            'outcome' => $outcome,
        ];

        $gateway = new FakeGateway($config);

        $this->manager->extend('fake', fn () => $gateway);
        config()->set('iran-payment.gateways.fake', $config);
        config()->set('iran-payment.ui.only', []);
        config()->set('iran-payment.ui.except', []);

        $this->manager->setDefaultDriver('fake');

        return $gateway;
    }
}
