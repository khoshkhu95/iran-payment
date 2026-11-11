<?php

namespace Taha20\IranPayment\Contracts;

/**
 * محل نگهداری تنظیمات درگاه‌ها.
 *
 * هر درایور فقط باید یک نگاشت «نام درگاه → کلید → مقدار» برگرداند. منطق
 * اعتبارسنجی، رمزنگاری فیلدهای حساس و ادغام با config بیرون از این لایه
 * انجام می‌شود تا پیاده‌سازی درایور جدید (Redis، فایل JSON، سرویس ابری،
 * تنظیمات چندمستأجری) ساده بماند.
 */
interface SettingsStore
{
    /**
     * همهٔ مقادیر ذخیره‌شده.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array;

    /**
     * ذخیرهٔ مقادیر یک درگاه. کلیدهایی که در آرایه نیستند دست‌نخورده می‌مانند
     * و مقدار null باعث حذف آن کلید می‌شود.
     *
     * @param  array<string, mixed>  $values
     */
    public function put(string $gateway, array $values): void;

    /**
     * حذف همهٔ تنظیمات یک درگاه (بازگشت به مقادیر config).
     */
    public function forget(string $gateway): void;

    /**
     * آیا این محل ذخیره‌سازی آمادهٔ استفاده است؟
     * (مثلاً جدول دیتابیس ساخته شده باشد)
     */
    public function isAvailable(): bool;
}
