<?php

namespace Taha20\IranPayment\Settings\Stores;

use Taha20\IranPayment\Contracts\SettingsStore;

/**
 * نگهداری تنظیمات در حافظه.
 *
 * برای تست‌های خودکار و سناریوهایی که نمی‌خواهید چیزی روی دیسک نوشته شود.
 * بین درخواست‌ها باقی نمی‌ماند.
 */
class ArraySettingsStore implements SettingsStore
{
    /** @var array<string, array<string, mixed>> */
    protected array $values = [];

    /**
     * @param  array<string, array<string, mixed>>  $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function all(): array
    {
        return $this->values;
    }

    public function put(string $gateway, array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value === null) {
                unset($this->values[$gateway][$key]);

                continue;
            }

            $this->values[$gateway][$key] = $value;
        }

        if (isset($this->values[$gateway]) && $this->values[$gateway] === []) {
            unset($this->values[$gateway]);
        }
    }

    public function forget(string $gateway): void
    {
        unset($this->values[$gateway]);
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
