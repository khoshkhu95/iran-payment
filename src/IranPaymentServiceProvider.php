<?php

namespace Taha20\IranPayment;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Taha20\IranPayment\Console\InstallCommand;
use Taha20\IranPayment\Contracts\TransactionStore;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Repositories\ArrayTransactionStore;
use Taha20\IranPayment\Repositories\DatabaseTransactionStore;
use Taha20\IranPayment\View\Components\IranPayment as IranPaymentComponent;

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

        $this->app->singleton(PaymentProcessor::class);

        $this->app->singleton(IranPayment::class, fn ($app) => new IranPayment(
            $app->make(PaymentManager::class),
            $app->make(PaymentProcessor::class),
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'iran-payment');

        if ($this->app['config']->get('iran-payment.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        // ثبت تگ <x-iran-payment />
        Blade::component(IranPaymentComponent::class, 'iran-payment');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/iran-payment.php' => config_path('iran-payment.php'),
            ], 'iran-payment-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/iran-payment'),
            ], 'iran-payment-views');

            $this->publishes([
                __DIR__.'/../database/migrations/create_iran_payments_table.php.stub' => $this->migrationPath(),
            ], 'iran-payment-migrations');

            $this->commands([InstallCommand::class]);
        }
    }

    /**
     * مسیر مقصد فایل مایگریشن با تاریخ فعلی (تا با مایگریشن‌های پروژه تداخل نکند).
     */
    protected function migrationPath(): string
    {
        return database_path('migrations/'.date('Y_m_d_His').'_create_iran_payments_table.php');
    }
}
