<?php

namespace Taha20\IranPayment\Contracts;

use Taha20\IranPayment\Models\Transaction;

/**
 * قرارداد لایهٔ ذخیره‌سازی تراکنش‌ها.
 *
 * این انتزاع باعث می‌شود پکیج به دیتابیس گره نخورد: می‌توانید از پیاده‌سازی
 * پیش‌فرض دیتابیس، از حافظه (برای تست) یا از پیاده‌سازی اختصاصی خودتان
 * (مثلاً Redis یا یک سرویس خارجی) استفاده کنید.
 */
interface TransactionStore
{
    /**
     * ساخت و ذخیرهٔ یک تراکنش جدید.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Transaction;

    /**
     * به‌روزرسانی تراکنش موجود.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Transaction $transaction, array $attributes): Transaction;

    /**
     * یافتن تراکنش بر اساس شناسهٔ یکتا (uuid) که در آدرس بازگشت قرار می‌گیرد.
     */
    public function findByUuid(string $uuid): ?Transaction;

    /**
     * یافتن آخرین تراکنش بر اساس شمارهٔ سفارش (و اختیاراً نام درگاه).
     */
    public function findByOrderId(string $orderId, ?string $gateway = null): ?Transaction;
}
