<?php

use Illuminate\Support\Facades\Route;
use Taha20\IranPayment\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| روت‌های پکیج
|--------------------------------------------------------------------------
| پیشوند، دامنه و میدل‌ور از config خوانده می‌شود. اگر روت‌های پکیج را
| غیرفعال کنید، این فایل بارگذاری نمی‌شود و می‌توانید روت اختصاصی خودتان
| را بسازید.
*/

$attributes = [
    'middleware' => (array) config('iran-payment.routes.middleware', ['web']),
    'prefix' => (string) config('iran-payment.routes.prefix', 'iran-payment'),
    'as' => 'iran-payment.',
];

if ($domain = config('iran-payment.routes.domain')) {
    $attributes['domain'] = $domain;
}

Route::group($attributes, function () {
    Route::post('pay', [PaymentController::class, 'pay'])->name('pay');

    Route::match(['get', 'post'], 'callback/{gateway}/{uuid?}', [PaymentController::class, 'callback'])
        ->name('callback');

    Route::get('result/{transaction}', [PaymentController::class, 'result'])->name('result');
});
