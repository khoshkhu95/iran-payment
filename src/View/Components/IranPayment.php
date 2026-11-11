<?php

namespace Taha20\IranPayment\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;
use Taha20\IranPayment\PaymentManager;
use Taha20\IranPayment\Support\Amount;
use Taha20\IranPayment\Support\PaymentToken;

/**
 * کامپوننت <x-iran-payment />
 *
 * استفادهٔ ساده:
 *   <x-iran-payment :amount="150000" order-id="ORD-1042" description="خرید اشتراک" />
 *
 * استفاده با مدل دامنه (برای اینکه بعداً بدانید پرداخت مربوط به کدام سفارش است):
 *   <x-iran-payment :amount="$order->total" :payable="$order" order-id="{{ $order->code }}" />
 *
 * این کامپوننت کاملاً مستقل است: نه به Tailwind وابسته است، نه به بیلد فرانت‌اند.
 * تمام CSS آن inline و scoped است تا در هر پروژه‌ای با هر استکی همان‌طور
 * که هست کار کند.
 */
class IranPayment extends Component
{
    /** مبلغ نرمال‌شده به ریال */
    public int $amountInRial;

    public string $orderId;

    /** @var array<string, array<string, mixed>> */
    public array $gateways;

    /** توکن امضاشدهٔ پرداخت (اگر امضا فعال باشد) */
    public string $token = '';

    public string $layout;

    public string $theme;

    public string $buttonLabel;

    public ?string $note;

    public bool $showAmount;

    public ?string $selectedGateway;

    /** آدرس مقصد فرم */
    public string $actionUrl;

    /** مبلغ آمادهٔ نمایش (ارقام فارسی) */
    public string $amountText;

    /**
     * @param  PaymentManager  $manager  تزریق خودکار از کانتینر
     * @param  int|float|string  $amount  مبلغ به واحد پیش‌فرض پروژه (یا unit)
     * @param  array<int, string>|null  $only  محدود کردن لیست درگاه‌ها
     * @param  array<int, string>|null  $exclude  حذف درگاه‌های خاص از لیست
     */
    public function __construct(
        PaymentManager $manager,
        int|float|string $amount,
        ?string $orderId = null,
        public ?string $description = null,
        public ?string $returnUrl = null,
        public ?string $gateway = null,
        public ?string $mobile = null,
        public ?string $email = null,
        public ?Model $payable = null,
        public ?string $unit = null,
        public ?array $only = null,
        public ?array $exclude = null,
        ?string $layout = null,
        ?string $theme = null,
        ?string $buttonLabel = null,
        ?bool $showAmount = null,
        ?string $note = null,
    ) {
        $this->amountInRial = Amount::toRial($amount, $unit);

        $this->orderId = $orderId
            ?: ($payable?->getKey() !== null ? (string) $payable->getKey() : 'ORD-'.Str::upper(Str::random(8)));

        $this->gateways = $this->filterGateways($manager->availableGateways());

        $this->selectedGateway = $gateway && isset($this->gateways[$gateway])
            ? $gateway
            : (array_key_first($this->gateways) ?: null);

        $this->layout = $layout ?: (string) config('iran-payment.ui.layout', 'grid');
        $this->theme = $theme ?: (string) config('iran-payment.ui.theme', 'auto');
        $this->buttonLabel = $buttonLabel ?: (string) config('iran-payment.ui.button_label', 'پرداخت');
        $this->showAmount = $showAmount ?? (bool) config('iran-payment.ui.show_amount', true);
        $this->note = $note ?? config('iran-payment.ui.note');

        $this->token = $this->makeToken();

        $this->actionUrl = $this->resolveActionUrl();
        $this->amountText = Amount::format($this->amountInRial);
    }

    /**
     * آدرس فرم؛ اگر روت‌های پکیج خاموش باشند، به مسیر پیش‌فرض برمی‌گردیم.
     */
    protected function resolveActionUrl(): string
    {
        if (config('iran-payment.routes.enabled', true)) {
            try {
                return route('iran-payment.pay');
            } catch (\Throwable) {
                // روت ثبت نشده است؛ از مسیر پیش‌فرض استفاده می‌کنیم
            }
        }

        $prefix = trim((string) config('iran-payment.routes.prefix', 'iran-payment'), '/');

        return url($prefix.'/pay');
    }

    /**
     * @param  array<string, array<string, mixed>>  $gateways
     * @return array<string, array<string, mixed>>
     */
    protected function filterGateways(array $gateways): array
    {
        if (! empty($this->only)) {
            $gateways = array_intersect_key($gateways, array_flip($this->only));
        }

        if (! empty($this->exclude)) {
            $gateways = array_diff_key($gateways, array_flip($this->exclude));
        }

        return $gateways;
    }

    /**
     * ساخت توکن امضاشده تا کاربر نتواند مبلغ یا شمارهٔ سفارش را دست‌کاری کند.
     */
    protected function makeToken(): string
    {
        if (! config('iran-payment.security.sign_payments', true)) {
            return '';
        }

        return PaymentToken::make([
            'amount' => $this->amountInRial,
            'order_id' => $this->orderId,
            'description' => $this->description,
            'return_url' => $this->returnUrl,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'payable_type' => $this->payable?->getMorphClass(),
            'payable_id' => $this->payable?->getKey(),
            'gateway' => $this->gateway,
        ]);
    }

    public function render(): View
    {
        return view('iran-payment::components.iran-payment');
    }
}
