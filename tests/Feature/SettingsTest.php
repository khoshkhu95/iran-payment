<?php

namespace Taha20\IranPayment\Tests\Feature;

use Taha20\IranPayment\Contracts\SettingsStore;
use Taha20\IranPayment\Facades\IranPayment;
use Taha20\IranPayment\Gateways\ZarinpalGateway;
use Taha20\IranPayment\Settings\EnvWriter;
use Taha20\IranPayment\Settings\Stores\EnvSettingsStore;
use Taha20\IranPayment\Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_component_renders_every_gateway_with_its_fields(): void
    {
        $view = $this->blade('<x-iran-payment-settings />');

        $view->assertSee('تنظیمات درگاه‌های پرداخت');
        $view->assertSee('زرین‌پال');
        $view->assertSee('مرچنت آیدی');
        $view->assertSee('name="gateways[zarinpal][merchant_id]"', false);
        $view->assertSee('درگاه پیش‌فرض');
        $view->assertSee('name="default"', false);
    }

    public function test_component_can_be_limited_to_specific_gateways(): void
    {
        $view = $this->blade('<x-iran-payment-settings :only="[\'fake\']" />');

        $view->assertSee('درگاه آزمایشی');
        $view->assertDontSee('زرین‌پال');
    }

    public function test_component_can_post_to_a_custom_action(): void
    {
        $view = $this->blade('<x-iran-payment-settings action="/admin/payments/settings" />');

        $view->assertSee('action="/admin/payments/settings"', false);
    }

    public function test_stored_secrets_are_masked_in_the_html(): void
    {
        $this->app->make(SettingsStore::class)->put('idpay', ['api_key' => 'super-secret-key']);

        $view = $this->blade('<x-iran-payment-settings />');

        $view->assertDontSee('super-secret-key');
        $view->assertSee('ذخیره شده');
    }

    public function test_gateway_settings_are_saved_and_applied(): void
    {
        $response = $this->post(route('iran-payment.settings.update'), [
            'default' => 'zarinpal',
            'gateways' => [
                'zarinpal' => ['enabled' => '1', 'merchant_id' => 'abc-123'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('iran_payment_settings_saved');

        $this->assertSame('abc-123', IranPayment::settings()->for('zarinpal')['merchant_id']);

        // مقدار ذخیره‌شده باید در تنظیمات مؤثری که درگاه می‌بیند هم دیده شود
        $config = IranPayment::manager()->configFor('zarinpal');
        $this->assertSame('abc-123', $config['merchant_id']);
        $this->assertTrue($config['enabled']);
    }

    public function test_required_field_is_validated_only_when_gateway_is_enabled(): void
    {
        $response = $this->from('/settings')->post(route('iran-payment.settings.update'), [
            'gateways' => [
                'zarinpal' => ['enabled' => '1', 'merchant_id' => ''],
            ],
        ]);

        $response->assertSessionHasErrors('gateways.zarinpal.merchant_id');
    }

    public function test_disabled_gateway_does_not_require_credentials(): void
    {
        $response = $this->post(route('iran-payment.settings.update'), [
            'gateways' => [
                'zarinpal' => ['enabled' => '0', 'merchant_id' => ''],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse(IranPayment::settings()->for('zarinpal')['enabled']);
    }

    public function test_secret_is_preserved_when_left_empty(): void
    {
        $this->post(route('iran-payment.settings.update'), [
            'gateways' => ['idpay' => ['enabled' => '1', 'api_key' => 'key-one']],
        ]);

        $this->post(route('iran-payment.settings.update'), [
            'gateways' => ['idpay' => ['enabled' => '1', 'api_key' => '']],
        ]);

        $this->assertSame('key-one', IranPayment::settings()->for('idpay')['api_key']);
    }

    public function test_unknown_keys_cannot_be_injected_into_config(): void
    {
        $this->post(route('iran-payment.settings.update'), [
            'gateways' => [
                'zarinpal' => [
                    'enabled' => '1',
                    'merchant_id' => 'ok',
                    'driver' => 'App\\Evil\\Gateway',
                    'title' => 'هک شد',
                ],
            ],
        ]);

        $config = IranPayment::manager()->configFor('zarinpal');

        $this->assertSame(ZarinpalGateway::class, $config['driver']);
        $this->assertSame('زرین‌پال', $config['title']);
    }

    public function test_default_gateway_is_persisted(): void
    {
        $this->post(route('iran-payment.settings.update'), [
            'default' => 'zibal',
            'gateways' => [
                'zibal' => ['enabled' => '1', 'merchant' => 'zibal'],
            ],
        ]);

        $this->assertSame('zibal', IranPayment::manager()->getDefaultDriver());
    }

    public function test_disabled_gateway_disappears_from_the_payment_component(): void
    {
        config()->set('iran-payment.ui.only', ['zarinpal', 'zibal']);
        config()->set('iran-payment.gateways.zarinpal.enabled', true);
        config()->set('iran-payment.gateways.zibal.enabled', true);

        $this->assertArrayHasKey('zarinpal', IranPayment::gateways());

        $this->post(route('iran-payment.settings.update'), [
            'gateways' => ['zarinpal' => ['enabled' => '0', 'merchant_id' => 'x']],
        ]);

        $this->assertArrayNotHasKey('zarinpal', IranPayment::gateways());
        $this->assertArrayHasKey('zibal', IranPayment::gateways());
    }

    public function test_forget_restores_config_values(): void
    {
        $this->post(route('iran-payment.settings.update'), [
            'gateways' => ['zarinpal' => ['enabled' => '1', 'merchant_id' => 'temp']],
        ]);

        $this->assertSame('temp', IranPayment::manager()->configFor('zarinpal')['merchant_id']);

        IranPayment::settings()->forget('zarinpal');

        $this->assertNull(IranPayment::manager()->configFor('zarinpal')['merchant_id']);
    }

    public function test_warning_is_shown_when_the_store_is_not_ready(): void
    {
        config()->set('iran-payment.settings.store', 'database');
        config()->set('iran-payment.settings.table', 'missing_settings_table');

        $view = $this->blade('<x-iran-payment-settings />');

        $view->assertSee('محل ذخیرهٔ تنظیمات');
    }

    public function test_custom_gateway_without_a_schema_still_gets_a_form(): void
    {
        config()->set('iran-payment.gateways.custom', [
            'driver' => \Taha20\IranPayment\Gateways\FakeGateway::class,
            'enabled' => true,
            'title' => 'درگاه اختصاصی من',
            'api_key' => null,
            'callback_url' => null,
        ]);

        $view = $this->blade('<x-iran-payment-settings :only="[\'custom\']" />');

        $view->assertSee('درگاه اختصاصی من');
        $view->assertSee('name="gateways[custom][api_key]"', false);
        $view->assertSee('name="gateways[custom][callback_url]"', false);
    }

    public function test_env_store_round_trips_values_using_the_existing_naming_convention(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ipst-env-');

        file_put_contents($path, "APP_NAME=Test\nZARINPAL_MERCHANT_ID=old-value\nIRAN_PAYMENT_FAKE_OUTCOME=failed\n");

        $store = new EnvSettingsStore(
            new EnvWriter($path),
            (array) config('iran-payment.settings.env_map')
        );

        $values = $store->all();

        $this->assertSame('old-value', $values['zarinpal']['merchant_id']);
        $this->assertSame('failed', $values['fake']['outcome']);

        $store->put('zibal', ['merchant' => 'my-merchant', 'enabled' => true]);

        $content = (string) file_get_contents($path);

        $this->assertStringContainsString('ZIBAL_MERCHANT=my-merchant', $content);
        $this->assertStringContainsString('ZIBAL_ENABLED=true', $content);
        $this->assertSame('my-merchant', $store->all()['zibal']['merchant']);

        $store->forget('zibal');

        $content = (string) file_get_contents($path);

        $this->assertStringNotContainsString('ZIBAL_MERCHANT', $content);
        $this->assertStringNotContainsString('ZIBAL_ENABLED', $content);

        @unlink($path);
    }

    public function test_saving_fails_gracefully_when_the_store_is_not_ready(): void
    {
        config()->set('iran-payment.settings.store', 'database');
        config()->set('iran-payment.settings.table', 'missing_settings_table');

        $response = $this->from('/admin/settings')->post(route('iran-payment.settings.update'), [
            'gateways' => ['zarinpal' => ['enabled' => '1', 'merchant_id' => 'x']],
        ]);

        $response->assertRedirect('/admin/settings');
        $response->assertSessionHas('iran_payment_settings_error');
    }
}
