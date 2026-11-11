<?php

namespace Taha20\IranPayment\Support;

use Illuminate\Database\Eloquent\Model;
use Taha20\IranPayment\Enums\TransactionStatus;
use Taha20\IranPayment\Exceptions\InvalidPaymentException;

/**
 * شیء ارزشی «درخواست پرداخت» - چیزی که برنامه‌نویس می‌سازد و به پکیج می‌دهد.
 *
 * مبلغ همیشه درون این شیء به ریال نگه داشته می‌شود.
 */
final class Payment
{
    private int $amount;

    private string $orderId;

    private ?string $description = null;

    private ?string $callbackUrl = null;

    private ?string $returnUrl = null;

    private ?string $mobile = null;

    private ?string $email = null;

    /** @var array<string, mixed> */
    private array $meta = [];

    private ?Model $payable = null;

    /**
     * @param  int|float|string  $amount  مبلغ به واحد پیش‌فرض پروژه (config: currency)
     * @param  string|null  $unit  برای رد کردن واحد پیش‌فرض: 'IRR' یا 'IRT'
     */
    public function __construct(int|float|string $amount, string $orderId, ?string $unit = null)
    {
        $this->amount = Amount::toRial($amount, $unit);

        if ($this->amount <= 0) {
            throw InvalidPaymentException::nonPositiveAmount();
        }

        if (trim($orderId) === '') {
            throw InvalidPaymentException::emptyOrderId();
        }

        $this->orderId = $orderId;
    }

    public static function make(int|float|string $amount, string $orderId, ?string $unit = null): self
    {
        return new self($amount, $orderId, $unit);
    }

    public static function fromToman(int|float|string $amount, string $orderId): self
    {
        return new self($amount, $orderId, Amount::TOMAN);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function amountInToman(): int
    {
        return Amount::toToman($this->amount);
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function callbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    public function returnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function mobile(): ?string
    {
        return $this->mobile;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    /** @return array<string, mixed> */
    public function meta(): array
    {
        return $this->meta;
    }

    public function payable(): ?Model
    {
        return $this->payable;
    }

    public function withDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * آدرس بازگشت از درگاه. اگر تعیین نشود، روت داخلی پکیج استفاده می‌شود.
     */
    public function withCallbackUrl(?string $url): self
    {
        $this->callbackUrl = $url;

        return $this;
    }

    /**
     * آدرسی که کاربر پس از تأیید پرداخت به آن برگردانده می‌شود.
     */
    public function withReturnUrl(?string $url): self
    {
        $this->returnUrl = $url;

        return $this;
    }

    public function withMobile(?string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function withEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /** @param array<string, mixed> $meta */
    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * اتصال پرداخت به یک مدل دامنه (سفارش، اشتراک، ...) به‌صورت morph.
     */
    public function for(Model $payable): self
    {
        $this->payable = $payable;

        return $this;
    }

    /**
     * آرایهٔ قابل ذخیره در جدول تراکنش‌ها.
     *
     * @return array<string, mixed>
     */
    public function toTransactionAttributes(string $gateway, string $uuid, string $callbackUrl): array
    {
        return [
            'uuid' => $uuid,
            'gateway' => $gateway,
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'currency' => Amount::RIAL,
            'status' => TransactionStatus::Pending,
            'description' => $this->description,
            'callback_url' => $callbackUrl,
            'return_url' => $this->returnUrl,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'meta' => $this->meta ?: null,
            'payable_type' => $this->payable?->getMorphClass(),
            'payable_id' => $this->payable?->getKey(),
        ];
    }
}
