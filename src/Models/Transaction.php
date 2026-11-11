<?php

namespace Taha20\IranPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Taha20\IranPayment\Enums\TransactionStatus;
use Taha20\IranPayment\Support\Amount;

/**
 * مدل تراکنش پرداخت.
 *
 * نام جدول و اتصال دیتابیس از config خوانده می‌شود تا در پروژه‌هایی که
 * پیشوند جدول یا اتصال جداگانه دارند هم بدون تغییر کد کار کند.
 *
 * @property int $id
 * @property string $uuid
 * @property string $gateway
 * @property string $order_id
 * @property int $amount
 * @property string $currency
 * @property TransactionStatus $status
 * @property string|null $reference
 * @property string|null $tracking_code
 * @property string|null $card_number
 * @property string|null $message
 * @property string|null $description
 * @property string|null $callback_url
 * @property string|null $return_url
 * @property string|null $mobile
 * @property string|null $email
 * @property array|null $meta
 * @property array|null $gateway_response
 */
class Transaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'status' => TransactionStatus::class,
        'meta' => 'array',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return (string) config('iran-payment.transactions.table', 'iran_payments');
    }

    public function getConnectionName(): ?string
    {
        return config('iran-payment.transactions.connection') ?: parent::getConnectionName();
    }

    /**
     * در آدرس‌ها به‌جای id از uuid استفاده می‌شود تا شمارش تراکنش‌ها لو نرود.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isSuccessful(): bool
    {
        return $this->status instanceof TransactionStatus
            ? $this->status->isSuccessful()
            : in_array($this->status, ['paid', 'refunded'], true);
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending;
    }

    public function statusLabel(): string
    {
        return $this->status instanceof TransactionStatus ? $this->status->label() : (string) $this->status;
    }

    public function amountInToman(): int
    {
        return Amount::toToman((int) $this->amount);
    }

    public function formattedAmount(): string
    {
        return Amount::format((int) $this->amount);
    }

    /**
     * آدرسی که کاربر پس از پرداخت به آن برگردانده می‌شود.
     */
    public function redirectUrl(): string
    {
        if ($this->return_url) {
            return $this->return_url;
        }

        $key = $this->isSuccessful() ? 'success' : 'failed';
        $configured = config('iran-payment.redirect.'.$key);

        if ($configured) {
            return $configured;
        }

        return route('iran-payment.result', ['transaction' => $this->uuid]);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [
            TransactionStatus::Paid->value,
            TransactionStatus::Refunded->value,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', TransactionStatus::Pending->value);
    }
}
