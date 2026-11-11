<?php

namespace Taha20\IranPayment\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Canceled = 'canceled';
    case Refunded = 'refunded';

    /**
     * آیا این وضعیت یک پرداخت موفق را نشان می‌دهد؟
     */
    public function isSuccessful(): bool
    {
        return $this === self::Paid || $this === self::Refunded;
    }

    /**
     * آیا تراکنش به پایان رسیده است (دیگر قابل پیگیری نیست)؟
     */
    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پرداخت',
            self::Paid => 'پرداخت شده',
            self::Failed => 'ناموفق',
            self::Canceled => 'لغو شده',
            self::Refunded => 'بازگشت وجه',
        };
    }

    /**
     * رنگ پیشنهادی برای نمایش در رابط کاربری.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => '#F59E0B',
            self::Paid => '#10B981',
            self::Failed => '#EF4444',
            self::Canceled => '#6B7280',
            self::Refunded => '#8B5CF6',
        };
    }
}
