<?php

namespace Taha20\IranPayment\Settings;

use Taha20\IranPayment\Contracts\SettingsStore;

/**
 * مغز تنظیمات درگاه‌ها.
 *
 * سه کار انجام می‌دهد:
 *   ۱) خواندن مقادیر ذخیره‌شده از هر درایوری که انتخاب کرده‌اید
 *   ۲) ادغام آن‌ها روی config (اولویت: مقدار ذخیره‌شده > .env > config)
 *   ۳) ذخیرهٔ مقادیر جدید با پاک‌سازی و تبدیل نوع
 *
 * نکتهٔ مهم: ادغام «تنبل» (lazy) انجام می‌شود؛ یعنی فقط وقتی درگاهی واقعاً
 * استفاده یا رندر شود، تنظیماتش خوانده می‌شود. پس اگر پکیج را نصب کردید و
 * هنوز جدول تنظیمات را نساخته‌اید، هیچ کوئری‌ای در هر درخواست اجرا نمی‌شود.
 */
class GatewaySettings
{
    /** @var array<string, array<string, mixed>>|null */
    protected ?array $stored = null;

    public function __construct(
        protected SettingsStore $store,
        protected GatewaySchema $schema,
    ) {}

    public function store(): SettingsStore
    {
        return $this->store;
    }

    public function schema(): GatewaySchema
    {
        return $this->schema;
    }

    /**
     * همهٔ مقادیر ذخیره‌شده، نرمال‌شده.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->stored ??= $this->normalize($this->store->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function for(string $gateway): array
    {
        return $this->all()[$gateway] ?? [];
    }

    /**
     * تنظیمات مؤثر یک درگاه: config پروژه + مقادیر ذخیره‌شده از پنل.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function effective(string $gateway, array $config): array
    {
        $stored = $this->for($gateway);

        return $stored === [] ? $config : array_replace($config, $stored);
    }

    /**
     * ذخیرهٔ مقادیر یک درگاه.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(string $gateway, array $values): void
    {
        $this->store->put($gateway, $values);

        $this->flush();
    }

    /**
     * حذف تنظیمات ذخیره‌شدهٔ یک درگاه (بازگشت به مقادیر config و .env).
     */
    public function forget(string $gateway): void
    {
        $this->store->forget($gateway);

        $this->flush();
    }

    /**
     * پاک‌کردن کش داخلی؛ بعد از هر تغییر لازم است.
     */
    public function flush(): void
    {
        $this->stored = null;
    }

    /**
     * آیا همهٔ فیلدهای اجباری این درگاه مقدار دارند؟
     *
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(string $gateway, array $config = []): bool
    {
        return $this->missingFields($gateway, $config) === [];
    }

    /**
     * برچسب فیلدهای اجباری‌ای که خالی مانده‌اند.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    public function missingFields(string $gateway, array $config = []): array
    {
        $effective = $this->effective($gateway, $config);
        $missing = [];

        foreach ($this->schema->fieldsFor($gateway, $config) as $field) {
            if (! $field->required) {
                continue;
            }

            $value = $effective[$field->name] ?? null;

            if ($value === null || $value === '') {
                $missing[] = $field->label;
            }
        }

        return $missing;
    }

    /**
     * تبدیل مقادیر خام (رشته از دیتابیس یا .env) به نوع درست.
     *
     * @param  array<string, array<string, mixed>>  $values
     * @return array<string, array<string, mixed>>
     */
    protected function normalize(array $values): array
    {
        $normalized = [];

        foreach ($values as $gateway => $items) {
            foreach ((array) $items as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                if (in_array($key, ['enabled', 'sandbox'], true)) {
                    $normalized[$gateway][$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                    continue;
                }

                $normalized[$gateway][$key] = $value;
            }
        }

        return $normalized;
    }
}
