<?php

namespace Taha20\IranPayment\Settings\Stores;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Taha20\IranPayment\Contracts\SettingsStore;
use Taha20\IranPayment\Exceptions\ConfigurationException;
use Throwable;

/**
 * ذخیرهٔ تنظیمات در دیتابیس (پیش‌فرض).
 *
 * چرا دیتابیس و نه .env؟
 *   - با `php artisan config:cache` سازگار است؛ در حالت production که
 *     config کش شده، نوشتن در .env تا پاک‌شدن کش اثری ندارد.
 *   - روی هاست‌هایی که فایل .env قابل نوشتن نیست کار می‌کند.
 *   - مقادیر حساس رمزنگاری‌شده ذخیره می‌شوند (با APP_KEY پروژه).
 *
 * ساختار جدول: یک ردیف برای هر (درگاه، کلید) — مایگریشن آن همراه پکیج
 * منتشر می‌شود.
 */
class DatabaseSettingsStore implements SettingsStore
{
    protected ?bool $available = null;

    public function __construct(
        protected string $table = 'iran_payment_settings',
        protected ?string $connection = null,
    ) {}

    protected function query(): Builder
    {
        return DB::connection($this->connection)->table($this->table);
    }

    public function all(): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $values = [];

        foreach ($this->query()->get(['gateway', 'key', 'value']) as $row) {
            $values[(string) $row->gateway][(string) $row->key] = $this->decode($row->value);
        }

        return $values;
    }

    public function put(string $gateway, array $values): void
    {
        if (! $this->isAvailable()) {
            throw ConfigurationException::settingsTableMissing($this->table);
        }

        foreach ($values as $key => $value) {
            $key = (string) $key;

            if ($value === null) {
                $this->query()->where('gateway', $gateway)->where('key', $key)->delete();

                continue;
            }

            $encoded = $this->encode($value);
            $existing = $this->query()->where('gateway', $gateway)->where('key', $key);

            if ((clone $existing)->exists()) {
                $existing->update(['value' => $encoded, 'updated_at' => now()]);
            } else {
                $this->query()->insert([
                    'gateway' => $gateway,
                    'key' => $key,
                    'value' => $encoded,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function forget(string $gateway): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        $this->query()->where('gateway', $gateway)->delete();
    }

    public function isAvailable(): bool
    {
        if ($this->available === true) {
            return true;
        }

        try {
            // فقط نتیجهٔ مثبت کش می‌شود تا اگر جدول بعداً ساخته شد، دوباره چک شود
            $this->available = Schema::connection($this->connection)->hasTable($this->table);
        } catch (Throwable) {
            // اتصال دیتابیس در دسترس نیست (مثلاً قبل از نصب پروژه) — بی‌صدا رد می‌شویم
            $this->available = false;
        }

        return $this->available;
    }

    /*
    |--------------------------------------------------------------------------
    | رمزنگاری
    |--------------------------------------------------------------------------
    */

    protected function encode(mixed $value): string
    {
        $plain = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        return Crypt::encryptString($plain);
    }

    /**
     * رمزگشایی با تحمل خطا:
     *   - مقداری که با فرمت رمزنگاری لاراول نیست (دادهٔ قدیمی متنی) همان‌طور برگردانده می‌شود.
     *   - اگر رمزگشایی شکست بخورد (مثلاً APP_KEY عوض شده) null برمی‌گردد تا
     *     درگاه با مقدار نامعتبر کار نکند.
     */
    protected function decode(?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! $this->looksEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable $e) {
            Log::warning('[iran-payment] رمزگشایی تنظیمات درگاه ناموفق بود؛ احتمالاً APP_KEY تغییر کرده است.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function looksEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload) && isset($payload['iv'], $payload['value']);
    }
}
