<?php

use Illuminate\Support\Facades\Route;
use Taha20\IranPayment\Http\Controllers\PaymentController;
use Taha20\IranPayment\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| روت‌های پکیج
|--------------------------------------------------------------------------
| پیشوند، دامنه و میدل‌ور از config خوانده می‌شود. اگر روت‌های پکیج را
| غیرفعال کنید، این فایل بارگذاری نمی‌شود و می‌توانید روت اختصاصی خودتان
| را بسازید.
|
| دو گروه مستقل داریم:
|   - routes.enabled   => روت‌های پرداخت (pay / callback / result)
|   - settings.routes  => روت ذخیرهٔ فرم تنظیمات درگاه‌ها
*/

$domain = config('iran-payment.routes.domain');

if (config('iran-payment.routes.enabled', true)) {
    $attributes = [
        'middleware' => (array) config('iran-payment.routes.middleware', ['web']),
        'prefix' => (string) config('iran-payment.routes.prefix', 'iran-payment'),
        'as' => 'iran-payment.',
    ];

    if ($domain) {
        $attributes['domain'] = $domain;
    }

    Route::group($attributes, function () {
        Route::post('pay', [PaymentController::class, 'pay'])->name('pay');

        Route::match(['get', 'post'], 'callback/{gateway}/{uuid?}', [PaymentController::class, 'callback'])
            ->name('callback');

        Route::get('result/{transaction}', [PaymentController::class, 'result'])->name('result');
    });
}

if (config('iran-payment.settings.routes', true)) {
    $settingsAttributes = [
        'middleware' => (array) config('iran-payment.settings.middleware', ['web']),
        'prefix' => (string) config('iran-payment.settings.prefix', 'iran-payment/settings'),
        'as' => 'iran-payment.settings.',
    ];

    if ($domain) {
        $settingsAttributes['domain'] = $domain;
    }

    Route::group($settingsAttributes, function () {
        Route::post('/', [SettingsController::class, 'update'])->name('update');
        Route::delete('{gateway}', [SettingsController::class, 'destroy'])->name('reset');
    });
}
