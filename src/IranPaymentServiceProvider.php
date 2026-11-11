<?php

namespace Taha20\IranPayment;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Taha20\IranPayment\Console\InstallCommand;
use Taha20\IranPayment\Contracts\SettingsStore;
use Taha20\IranPayment\Contracts\TransactionStore;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Repositories\ArrayTransactionStore;
use Taha20\IranPayment\Repositories\DatabaseTransactionStore;
use Taha20\IranPayment\Settings\EnvWriter;
use Taha20\IranPayment\Settings\GatewaySchema;
use Taha20\IranPayment\Settings\GatewaySettings;
use Taha20\IranPayment\Settings\Stores\ArraySettingsStore;
use Taha20\IranPayment\Settings\Stores\DatabaseSettingsStore;
use Taha20\IranPayment\Settings\Stores\EnvSettingsStore;
use Taha20\IranPayment\View\Components\IranPayment as IranPaymentComponent;
use Taha20\IranPayment\View\Components\IranPaymentSettings as IranPaymentSettingsComponent;

class IranPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/iran-payment.php', 'iran-payment');

        // مدیر درگاه‌ها - الگوی Manager خود لاراول
        $this->app->singleton(PaymentManager::class, fn ($app) => new PaymentManager($app));
        $this->app->alias(PaymentManager::class, 'iran-payment');

        // لایهٔ ذخیره‌سازی: دیتابیس یا حافظه (بر اساس config)
        $this->app->singleton(TransactionStore::class, function ($app) {
            if (! $app['config']->get('iran-payment.transactions.enabled', true)) {
                return new ArrayTransactionStore;
            }

            $model = $app['config']->get('iran-payment.transactions.model', Transaction::class);

            return new DatabaseTransactionStore($model);
        });

        // محل ذخیرهٔ تنظیمات درگاه‌ها (database | env | array)
        $this->app->singleton(SettingsStore::class, function ($app) {
            $config = $app['config'];
            $driver = (string) $config->get('iran-payment.settings.store', 'database');

            return match ($driver) {
                'env' => new EnvSettingsStore(
                    new EnvWriter($app->environmentFilePath()),
                    (array) $config->get('iran-payment.settings.env_map', [])
                ),
                'array' => new ArraySettingsStore,
                default => new DatabaseSettingsStore(
                    (string) $config->get('iran-payment.settings.table', 'iran_payment_settings'),
                    $config->get('iran-payment.settings.connection')
                ),
            };
        });

        $this->app->singleton(GatewaySchema::class, fn () => new GatewaySchema);

        $this->app->singleton(GatewaySettings::class, fn ($app) => new GatewaySettings(
            $app->make(SettingsStore::class),
            $app->make(GatewaySchema::class),
        ));

        $this->app->singleton(PaymentProcessor::class);

        $this->app->singleton(IranPayment::class, fn ($app) => new IranPayment(
            $app->make(PaymentManager::class),
            $app->make(PaymentProcessor::class),
            $app->make(GatewaySettings::class),
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'iran-payment');

        if (
            $this->app['config']->get('iran-payment.routes.enabled', true)
            || $this->app['config']->get('iran-payment.settings.routes', true)
        ) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        // ثبت تگ‌های Blade
        Blade::component(IranPaymentComponent::class, 'iran-payment');
        Blade::component(IranPaymentSettingsComponent::class, 'iran-payment-settings');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/iran-payment.php' => config_path('iran-payment.php'),
            ], 'iran-payment-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/iran-payment'),
            ], 'iran-payment-views');

            $this->publishes([
                __DIR__.'/../database/migrations/create_iran_payments_table.php.stub' => $this->migrationPath('create_iran_payments_table'),
            ], 'iran-payment-migrations');

            $this->publishes([
                __DIR__.'/../database/migrations/create_iran_payment_settings_table.php.stub' => $this->migrationPath('create_iran_payment_settings_table'),
            ], 'iran-payment-migrations');

            $this->commands([InstallCommand::class]);
        }
    }

    /**
     * مسیر مقصد فایل مایگریشن با تاریخ فعلی (تا با مایگریشن‌های پروژه تداخل نکند).
     */
    protected function migrationPath(string $name): string
    {
        return database_path('migrations/'.date('Y_m_d_His').'_'.$name.'.php');
    }
}
