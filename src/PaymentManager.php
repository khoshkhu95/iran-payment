<?php

namespace Taha20\IranPayment;

use Illuminate\Support\Manager;
use Taha20\IranPayment\Contracts\Gateway;
use Taha20\IranPayment\Exceptions\GatewayNotFoundException;

/**
 * مدیر درگاه‌ها.
 *
 * بر پایهٔ الگوی Manager خود لاراول ساخته شده است؛ یعنی همان چیزی که
 * دیتابیس و کش و فایل‌سیستم لاراول استفاده می‌کنند. مزیت‌ها:
 *   - سوئیچ بین درگاه‌ها فقط با تغییر config انجام می‌شود، نه کد
 *   - هر درگاه فقط وقتی واقعاً استفاده شود ساخته می‌شود (lazy)
 *   - با extend() می‌توانید درایور اختصاصی پروژه را در ServiceProvider
 *     خودتان ثبت کنید، بدون دست‌زدن به این پکیج
 *
 * @mixin Gateway
 */
class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->container['config']->get('iran-payment.default', 'zarinpal');
    }

    /**
     * تغییر درگاه پیش‌فرض در زمان اجرا (مثلاً بر اساس تنظیمات کاربر).
     */
    public function setDefaultDriver(string $name): static
    {
        $this->container['config']->set('iran-payment.default', $name);

        return $this;
    }

    /**
     * ساخت یک درایور از روی تعریف config.
     *
     * @param  string  $driver
     */
    protected function createDriver($driver): Gateway
    {
        // درایورهایی که پروژه با extend() ثبت کرده است، اولویت دارند
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $config = $this->container['config']->get('iran-payment.gateways.'.$driver);

        if (! is_array($config)) {
            throw GatewayNotFoundException::make((string) $driver);
        }

        $class = $config['driver'] ?? null;

        if (! is_string($class) || ! class_exists($class)) {
            throw GatewayNotFoundException::make((string) $driver);
        }

        /** @var Gateway */
        return $this->container->make($class, [
            'config' => array_merge($config, ['name' => $driver]),
        ]);
    }

    /**
     * گرفتن یک درگاه مشخص (یا درگاه پیش‌فرض اگر نام داده نشود).
     */
    public function gateway(?string $name = null): Gateway
    {
        /** @var Gateway */
        return $this->driver($name);
    }

    /**
     * آیا درگاهی با این نام تعریف شده است؟
     */
    public function hasGateway(string $name): bool
    {
        return is_array($this->container['config']->get('iran-payment.gateways.'.$name));
    }

    /**
     * نام همهٔ درگاه‌های تعریف‌شده (فعال و غیرفعال).
     *
     * @return array<int, string>
     */
    public function gatewayNames(): array
    {
        return array_keys((array) $this->container['config']->get('iran-payment.gateways', []));
    }

    /**
     * نام درگاه‌هایی که برای نمایش در رابط کاربری فعال هستند.
     *
     * @return array<int, string>
     */
    public function enabledGatewayNames(): array
    {
        return array_keys($this->availableGateways());
    }

    /**
     * لیست آمادهٔ درگاه‌ها برای کامپوننت (نام + متادیتای نمایشی).
     *
     * @return array<string, array<string, mixed>>
     */
    public function availableGateways(): array
    {
        $only = (array) $this->container['config']->get('iran-payment.ui.only', []);
        $except = (array) $this->container['config']->get('iran-payment.ui.except', []);

        $gateways = [];

        foreach ((array) $this->container['config']->get('iran-payment.gateways', []) as $name => $config) {
            if (! ($config['enabled'] ?? false)) {
                continue;
            }

            if ($only !== [] && ! in_array($name, $only, true)) {
                continue;
            }

            if (in_array($name, $except, true)) {
                continue;
            }

            $gateway = $this->gateway($name);

            $gateways[$name] = array_merge($gateway->metadata(), [
                'name' => $name,
                'supports_refund' => $gateway->supportsRefund(),
            ]);
        }

        return $gateways;
    }

    /**
     * درگاه پیش‌فرض اگر فعال نباشد، اولین درگاه فعال را برمی‌گرداند.
     * برای زمانی که config را عوض کرده‌اید ولی درگاه پیش‌فرض قدیمی مانده است.
     */
    public function resolveDefault(): string
    {
        $default = $this->getDefaultDriver();

        if ($this->container['config']->get('iran-payment.gateways.'.$default.'.enabled', false) === true) {
            return $default;
        }

        return $this->enabledGatewayNames()[0] ?? $default;
    }
}
