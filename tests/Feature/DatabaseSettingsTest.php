<?php

namespace Taha20\IranPayment\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Taha20\IranPayment\Contracts\SettingsStore;
use Taha20\IranPayment\Facades\IranPayment;
use Taha20\IranPayment\Tests\TestCase;

class DatabaseSettingsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('iran-payment.settings.store', 'database');
    }

    protected function setUp(): void
    {
        parent::setUp();

        (require __DIR__.'/../../database/migrations/create_iran_payment_settings_table.php.stub')->up();
    }

    public function test_values_are_encrypted_at_rest_and_readable_through_the_store(): void
    {
        $store = $this->app->make(SettingsStore::class);

        $store->put('zarinpal', [
            'merchant_id' => 'plain-merchant-value',
            'enabled' => true,
        ]);

        $raw = (string) DB::table('iran_payment_settings')
            ->where('gateway', 'zarinpal')
            ->where('key', 'merchant_id')
            ->value('value');

        $this->assertNotSame('plain-merchant-value', $raw);
        $this->assertStringNotContainsString('plain-merchant-value', $raw);

        $all = $store->all();

        $this->assertSame('plain-merchant-value', $all['zarinpal']['merchant_id']);
        $this->assertTrue(IranPayment::settings()->for('zarinpal')['enabled']);
    }

    public function test_settings_saved_through_the_component_persist_in_database(): void
    {
        $this->post(route('iran-payment.settings.update'), [
            'default' => 'zarinpal',
            'gateways' => [
                'zarinpal' => ['enabled' => '1', 'merchant_id' => 'db-value'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame('db-value', IranPayment::manager()->configFor('zarinpal')['merchant_id']);
        $this->assertSame('zarinpal', IranPayment::manager()->getDefaultDriver());
    }

    public function test_clearing_an_optional_field_removes_the_override(): void
    {
        $this->post(route('iran-payment.settings.update'), [
            'gateways' => [
                'mellat' => ['enabled' => '0', 'terminal_id' => '111', 'namespace' => 'http://x.test/'],
            ],
        ]);

        $this->assertSame('http://x.test/', IranPayment::settings()->for('mellat')['namespace']);

        $this->post(route('iran-payment.settings.update'), [
            'gateways' => [
                'mellat' => ['enabled' => '0', 'terminal_id' => '111', 'namespace' => ''],
            ],
        ]);

        $this->assertArrayNotHasKey('namespace', IranPayment::settings()->for('mellat'));
        $this->assertArrayNotHasKey('namespace', IranPayment::manager()->configFor('mellat'));
    }
}
