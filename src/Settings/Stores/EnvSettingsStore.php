<?php

namespace Taha20\IranPayment\Settings\Stores;

use Taha20\IranPayment\Contracts\SettingsStore;
use Taha20\IranPayment\Settings\EnvWriter;

/**
 * ذخیرهٔ تنظیمات داخل فایل .env.
 *
 * برای پروژه‌هایی که نمی‌خواهند جدول اضافه داشته باشند. نام متغیرها همان
 * قرارداد فعلی config است (ZARINPAL_MERCHANT_ID، MELLAT_PASSWORD و…)، پس
 * بعد از ذخیره، فایل config بدون هیچ تغییری همان مقادیر را می‌خواند.
 *
 * ⚠️ روی محیط‌هایی که فایل .env قابل نوشتن نیست (اکثر هاست‌های ابری و
 * کانتینرهای read-only) از درایور database استفاده کنید.
 */
class EnvSettingsStore implements SettingsStore
{
    /**
     * @param  array<string, string>  $map  نگاشت استثناها: "gateway.key" => ENV_NAME
     */
    public function __construct(
        protected EnvWriter $writer,
        protected array $map = [],
    ) {}

    public function all(): array
    {
        $gateways = array_keys((array) config('iran-payment.gateways', []));

        // نام درگاه‌های طولانی‌تر اول بررسی می‌شوند تا پیشوند اشتباه تطبیق نکند
        usort($gateways, static fn ($a, $b) => strlen((string) $b) <=> strlen((string) $a));

        $reverse = [];

        foreach ($this->map as $path => $env) {
            $reverse[$env] = $path;
        }

        $values = [];

        foreach ($this->writer->all() as $env => $value) {
            if (isset($reverse[$env])) {
                [$gateway, $key] = explode('.', $reverse[$env], 2);
                $values[$gateway][$key] = $value;

                continue;
            }

            foreach ($gateways as $gateway) {
                $prefix = $this->prefix((string) $gateway);

                if (str_starts_with($env, $prefix)) {
                    $values[$gateway][strtolower(substr($env, strlen($prefix)))] = $value;

                    break;
                }
            }
        }

        return $values;
    }

    public function put(string $gateway, array $values): void
    {
        $env = [];

        foreach ($values as $key => $value) {
            $env[$this->envKey($gateway, (string) $key)] = match (true) {
                $value === null => null,
                is_bool($value) => $value ? 'true' : 'false',
                default => (string) $value,
            };
        }

        $this->writer->set($env);
    }

    public function forget(string $gateway): void
    {
        $env = [];

        foreach (array_keys($this->all()[$gateway] ?? []) as $key) {
            $env[$this->envKey($gateway, (string) $key)] = null;
        }

        if ($env !== []) {
            $this->writer->set($env);
        }
    }

    public function isAvailable(): bool
    {
        $path = $this->writer->path();

        if (is_file($path)) {
            return is_writable($path);
        }

        return is_writable(dirname($path));
    }

    protected function envKey(string $gateway, string $key): string
    {
        return $this->map[$gateway.'.'.$key] ?? $this->prefix($gateway).strtoupper($key);
    }

    protected function prefix(string $gateway): string
    {
        return strtoupper(str_replace('-', '_', $gateway)).'_';
    }
}
