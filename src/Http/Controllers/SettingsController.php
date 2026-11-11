<?php

namespace Taha20\IranPayment\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Taha20\IranPayment\Settings\GatewaySettings;

/**
 * ذخیرهٔ تنظیمات درگاه‌ها از روی فرم <x-iran-payment-settings />.
 *
 * نکتهٔ امنیتی مهم: از ورودی کاربر فقط همان کلیدهایی پذیرفته می‌شود که در
 * «شناسنامهٔ فیلدهای درگاه» تعریف شده‌اند. یعنی حتی اگر کسی در DevTools
 * فیلد اضافه کند (مثلاً gateways[zarinpal][driver]=SomeEvilClass)، نادیده
 * گرفته می‌شود و هرگز داخل config نوشته نمی‌شود.
 */
class SettingsController
{
    public function __construct(
        protected GatewaySettings $settings,
    ) {}

    public function update(Request $request): RedirectResponse
    {
        // اگر جدول/فایل ذخیره‌سازی آماده نیست، به‌جای خطای ۵۰۰ پیام واضح بده
        if (! $this->settings->store()->isAvailable()) {
            return back()->with(
                'iran_payment_settings_error',
                'محل ذخیرهٔ تنظیمات آماده نیست. مایگریشن‌های پکیج را با «php artisan iran-payment:install» منتشر و سپس «php artisan migrate» را اجرا کنید.'
            );
        }

        $input = (array) $request->input('gateways', []);
        $gateways = (array) config('iran-payment.gateways', []);

        $rules = [
            'default' => ['nullable', 'string', Rule::in(array_keys($gateways))],
        ];
        $attributes = ['default' => 'درگاه پیش‌فرض'];

        foreach ($input as $name => $values) {
            if (! isset($gateways[$name]) || ! is_array($gateways[$name])) {
                continue;
            }

            $values = (array) $values;
            $enabled = filter_var($values['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $effective = $this->settings->effective((string) $name, $gateways[$name]);

            foreach ($this->settings->schema()->fieldsFor((string) $name, $gateways[$name]) as $field) {
                $key = 'gateways.'.$name.'.'.$field->name;
                $attributes[$key] = $field->label;

                $hasExisting = filled($effective[$field->name] ?? null);

                $rule = [
                    $field->required && $enabled && ! $hasExisting ? 'required' : 'nullable',
                    'string',
                ];

                if ($field->type === 'url') {
                    $rule[] = 'url';
                }

                if ($field->type === 'select') {
                    $rule[] = Rule::in(array_keys($field->options));
                }

                $rules[$key] = $rule;
            }
        }

        $request->validate($rules, [], $attributes);

        foreach ($input as $name => $values) {
            if (! isset($gateways[$name]) || ! is_array($gateways[$name])) {
                continue;
            }

            $this->settings->save((string) $name, $this->payload(
                (string) $name,
                (array) $values,
                $gateways[$name]
            ));
        }

        if (is_string($request->input('default')) && $request->input('default') !== '') {
            $this->settings->save('_global', ['default' => (string) $request->input('default')]);
        }

        return back()->with('iran_payment_settings_saved', true);
    }

    /**
     * پاک‌کردن تنظیمات ذخیره‌شدهٔ یک درگاه (بازگشت به مقادیر config و .env).
     */
    public function destroy(string $gateway): RedirectResponse
    {
        $this->settings->forget($gateway);

        return back()->with('iran_payment_settings_saved', true);
    }

    /**
     * ساخت آرایهٔ تمیز برای ذخیره.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $gatewayConfig
     * @return array<string, mixed>
     */
    protected function payload(string $name, array $values, array $gatewayConfig): array
    {
        $schema = $this->settings->schema();

        $payload = [
            'enabled' => filter_var($values['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];

        if ($schema->supportsSandbox($name)) {
            $payload['sandbox'] = filter_var($values['sandbox'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        foreach ($schema->fieldsFor($name, $gatewayConfig) as $field) {
            $value = $values[$field->name] ?? null;

            if ($field->type === 'toggle') {
                $payload[$field->name] = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            $value = is_string($value) ? trim($value) : $value;

            if ($value === null || $value === '') {
                // فیلد حساس خالی یعنی «مقدار قبلی را نگه دار»
                if ($field->isMasked()) {
                    continue;
                }

                // فیلد غیراجباری خالی یعنی «حذف override و بازگشت به config»
                if (! $field->required) {
                    $payload[$field->name] = null;
                }

                continue;
            }

            $payload[$field->name] = (string) $value;
        }

        return $payload;
    }
}
