<?php

namespace Taha20\IranPayment\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Taha20\IranPayment\IranPaymentServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [IranPaymentServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'IranPayment' => \Taha20\IranPayment\Facades\IranPayment::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.debug', false);

        // در تست‌ها بدون دیتابیس کار می‌کنیم
        $app['config']->set('iran-payment.transactions.enabled', false);
        $app['config']->set('iran-payment.default', 'fake');
        $app['config']->set('iran-payment.gateways.fake.enabled', true);
        $app['config']->set('iran-payment.gateways.fake.outcome', 'success');

        // تنظیمات درگاه‌ها در حافظه ذخیره شود تا تست به جدول نیاز نداشته باشد
        $app['config']->set('iran-payment.settings.store', 'array');

        // فقط درگاه آزمایشی در لیست نمایش داده شود تا تست به تنظیمات واقعی وابسته نباشد
        $app['config']->set('iran-payment.ui.only', ['fake']);
    }
}
