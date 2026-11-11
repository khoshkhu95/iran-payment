<?php

namespace Taha20\IranPayment\View\Components;

use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\View;
use Taha20\IranPayment\PaymentManager;
use Taha20\IranPayment\Settings\GatewaySettings;

/**
 * کامپوننت <x-iran-payment-settings />
 *
 * یک فرم کامل تنظیمات درگاه‌ها که در هر صفحهٔ تنظیماتی قابل قرار گرفتن است:
 *
 *   <x-iran-payment-settings />
 *
 * با محدودکردن به چند درگاه:
 *   <x-iran-payment-settings :only="['zarinpal', 'zibal']" />
 *
 * با ارسال به روت اختصاصی پروژه (اگر نمی‌خواهید از روت آمادهٔ پکیج استفاده کنید):
 *   <x-iran-payment-settings :action="route('admin.payments.settings')" />
 *
 * تمام CSS و JS درون خود کامپوننت است؛ نه Tailwind لازم دارد، نه بیلد.
 */
class IranPaymentSettings extends Component
{
    /** @var array<string, array<string, mixed>> */
    public array $gateways = [];

    /** @var array<string, string> نام => عنوان، برای لیست «درگاه پیش‌فرض» */
    public array $defaultOptions = [];

    public ?string $defaultGateway;

    public string $actionUrl;

    public string $theme;

    public string $title;

    /** آیا محل ذخیره‌سازی آماده است؟ (مثلاً جدول ساخته شده) */
    public bool $storeAvailable;

    /** نام درایور ذخیره‌سازی، برای پیام راهنما */
    public string $storeDriver;

    public bool $showAdvanced;

    /**
     * @param  array<int, string>|null  $only  فقط این درگاه‌ها نمایش داده شوند
     * @param  array<string, array<string, mixed>>|null  $values  مقادیر جایگزین برای پیش‌پر کردن فرم
     */
    public function __construct(
        GatewaySettings $settings,
        PaymentManager $manager,
        ?string $action = null,
        ?array $only = null,
        ?array $values = null,
        ?string $theme = null,
        ?string $title = null,
        bool|string|null $showAdvanced = null,
    ) {
        $this->storeAvailable = $settings->store()->isAvailable();
        $this->storeDriver = (string) config('iran-payment.settings.store', 'database');
        $this->theme = $theme ?: (string) config('iran-payment.ui.theme', 'auto');
        $this->title = $title ?: 'تنظیمات درگاه‌های پرداخت';
        $this->showAdvanced = $showAdvanced === null
            ? true
            : filter_var($showAdvanced, FILTER_VALIDATE_BOOLEAN);

        $schema = $settings->schema();

        foreach ((array) config('iran-payment.gateways', []) as $name => $gatewayConfig) {
            if (! is_array($gatewayConfig)) {
                continue;
            }

            if ($only !== null && $only !== [] && ! in_array($name, $only, true)) {
                continue;
            }

            // مقادیر تزریق‌شده از بیرون (برای پروژه‌هایی که خودشان ذخیره می‌کنند)
            $gatewayConfig = array_replace($gatewayConfig, (array) ($values[$name] ?? []));

            $effective = $settings->effective($name, $gatewayConfig);

            $this->gateways[$name] = [
                'name' => (string) $name,
                'title' => (string) ($effective['title'] ?? $name),
                'description' => $effective['description'] ?? null,
                'color' => (string) ($effective['color'] ?? '#6366f1'),
                'enabled' => filter_var($effective['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'supports_sandbox' => $schema->supportsSandbox((string) $name),
                'sandbox' => filter_var($effective['sandbox'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'configured' => $settings->isConfigured((string) $name, $gatewayConfig),
                'missing' => $settings->missingFields((string) $name, $gatewayConfig),
                'fields' => $this->buildFields((string) $name, $schema->fieldsFor((string) $name, $gatewayConfig), $effective),
            ];
        }

        foreach ($this->gateways as $gateway) {
            if ($gateway['enabled']) {
                $this->defaultOptions[$gateway['name']] = $gateway['title'];
            }
        }

        $this->defaultGateway = $settings->for('_global')['default'] ?? $manager->resolveDefault();

        $this->actionUrl = $action ?: $this->resolveActionUrl();
    }

    /**
     * ساخت آرایهٔ فیلدها برای ویو.
     *
     * @param  array<int, \Taha20\IranPayment\Settings\Field>  $fields
     * @param  array<string, mixed>  $effective
     * @return array<int, array<string, mixed>>
     */
    protected function buildFields(string $gateway, array $fields, array $effective): array
    {
        $rows = [];

        foreach ($fields as $field) {
            $value = $effective[$field->name] ?? $field->default;
            $hasValue = $value !== null && $value !== '';
            $masked = $field->isMasked();

            $rows[] = [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'required' => $field->required,
                'secret' => $masked,
                'advanced' => $field->advanced,
                'help' => $field->help,
                'options' => $field->options,
                'has_value' => $hasValue,
                'wide' => $field->type === 'textarea',
                'value' => $masked ? '' : (is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '')),
                'placeholder' => $masked && $hasValue
                    ? '••••••••••••  (ذخیره شده — برای تغییر مقدار جدید وارد کنید)'
                    : ($field->placeholder ?? ''),
                'key' => 'gateways.'.$gateway.'.'.$field->name,
                'input' => 'gateways['.$gateway.']['.$field->name.']',
                'id' => 'ipst-'.$gateway.'-'.$field->name,
            ];
        }

        // اگر درگاه فقط یک فیلد دارد، تمام عرض را بگیرد تا فرم خالی به نظر نرسد
        if (count($rows) === 1) {
            $rows[0]['wide'] = true;
        }

        return $rows;
    }

    /**
     * آدرس مقصد فرم.
     */
    protected function resolveActionUrl(): string
    {
        if (config('iran-payment.settings.routes', true)) {
            try {
                return route('iran-payment.settings.update');
            } catch (\Throwable) {
                // روت ثبت نشده است
            }
        }

        return url(trim((string) config('iran-payment.settings.prefix', 'iran-payment/settings'), '/'));
    }

    public function render(): View
    {
        // در رندر مستقیم (بدون میدل‌ور ShareErrorsFromSession) متغیر $errors وجود ندارد
        if (view()->shared('errors') === null) {
            view()->share('errors', new ViewErrorBag);
        }

        return view('iran-payment::components.iran-payment-settings');
    }
}
