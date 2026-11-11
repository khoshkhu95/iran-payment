<?php

use Taha20\IranPayment\Gateways\AqayePardakhtGateway;
use Taha20\IranPayment\Gateways\IdPayGateway;
use Taha20\IranPayment\Gateways\MellatGateway;
use Taha20\IranPayment\Gateways\NextPayGateway;
use Taha20\IranPayment\Gateways\ParsianGateway;
use Taha20\IranPayment\Gateways\PasargadGateway;
use Taha20\IranPayment\Gateways\PayIrGateway;
use Taha20\IranPayment\Gateways\PayPingGateway;
use Taha20\IranPayment\Gateways\PayStarGateway;
use Taha20\IranPayment\Gateways\SadadGateway;
use Taha20\IranPayment\Gateways\SamanGateway;
use Taha20\IranPayment\Gateways\ZarinpalGateway;
use Taha20\IranPayment\Gateways\ZibalGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | درگاه پیش‌فرض
    |--------------------------------------------------------------------------
    | نام کلیدی که در آرایهٔ gateways پایین‌تر تعریف شده است. هر جا در کد
    | درگاهی مشخص نکنید، از این درگاه استفاده می‌شود.
    */
    'default' => env('IRAN_PAYMENT_GATEWAY', 'zarinpal'),

    /*
    |--------------------------------------------------------------------------
    | واحد پول ورودی
    |--------------------------------------------------------------------------
    | مبالغی که به پکیج می‌دهید با این واحد تفسیر می‌شوند:
    |   IRR => ریال   |   IRT => تومان
    | داخل پکیج همه‌چیز به «ریال» نرمال می‌شود و هر درگاه خودش تبدیل لازم
    | (مثلاً تومان) را انجام می‌دهد. این‌طوری هیچ‌وقت در پروژه‌تان دچار
    | اشتباه صفرهای اضافه نمی‌شوید.
    */
    'currency' => env('IRAN_PAYMENT_CURRENCY', 'IRR'),

    /*
    |--------------------------------------------------------------------------
    | روت‌های آماده
    |--------------------------------------------------------------------------
    | اگر false باشد، هیچ روتی ثبت نمی‌شود و باید خودتان کنترلر و روت
    | اختصاصی پروژه را بسازید (برای پروژه‌هایی که مسیر callback خاص دارند).
    */
    'routes' => [
        'enabled' => true,
        'prefix' => env('IRAN_PAYMENT_ROUTE_PREFIX', 'iran-payment'),
        'domain' => env('IRAN_PAYMENT_ROUTE_DOMAIN'),
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | ذخیره‌سازی تراکنش‌ها
    |--------------------------------------------------------------------------
    | enabled=true  => روی دیتابیس ذخیره می‌شود (پیشنهاد می‌شود)
    | enabled=false => حالت بی‌حالت (stateless)؛ تراکنش‌ها در حافظه‌ی همان
    |                 درخواست نگه داشته می‌شوند. فقط برای تست/سناریوهای خاص.
    */
    'transactions' => [
        'enabled' => env('IRAN_PAYMENT_DB_ENABLED', true),
        'table' => env('IRAN_PAYMENT_DB_TABLE', 'iran_payments'),
        'connection' => env('IRAN_PAYMENT_DB_CONNECTION'),
        // در صورت نیاز به مدل اختصاصی، کلاس خودتان را اینجا معرفی کنید
        'model' => \Taha20\IranPayment\Models\Transaction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | تنظیمات رابط کاربری کامپوننت <x-iran-payment />
    |--------------------------------------------------------------------------
    */
    'ui' => [
        // auto | light | dark
        'theme' => env('IRAN_PAYMENT_UI_THEME', 'auto'),

        // grid | list
        'layout' => env('IRAN_PAYMENT_UI_LAYOUT', 'grid'),

        // اگر مقدار داشته باشد فقط همین درگاه‌ها در کامپوننت نمایش داده می‌شوند
        // مثال: ['zarinpal', 'zibal']
        'only' => [],

        // درگاه‌هایی که هرگز در کامپوننت نمایش داده نشوند
        'except' => [],

        // نمایش مبلغ در بالای فرم
        'show_amount' => true,

        // متن دکمهٔ پرداخت
        'button_label' => 'پرداخت',

        // پیام پایین فرم (null = نمایش پیام پیش‌فرض)
        'note' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | امنیت کامپوننت
    |--------------------------------------------------------------------------
    | کامپوننت مبلغ و شناسهٔ سفارش را در قالب یک توکن رمزنگاری‌شده (با APP_KEY)
    | به سرور می‌فرستد تا کاربر نتواند مبلغ را در مرورگر دست‌کاری کند.
    | اگر خودتان کنترلر اختصاصی دارید و مبلغ را سمت سرور تعیین می‌کنید،
    | می‌توانید این را خاموش کنید.
    */
    'security' => [
        'sign_payments' => env('IRAN_PAYMENT_SIGN_PAYMENTS', true),
        'token_ttl' => env('IRAN_PAYMENT_TOKEN_TTL', 3600), // ثانیه
    ],

    /*
    |--------------------------------------------------------------------------
    | تنظیمات HTTP
    |--------------------------------------------------------------------------
    | retry را عمداً صفر گذاشته‌ایم؛ تکرار خودکار یک درخواست پرداخت می‌تواند
    | باعث ایجاد تراکنش تکراری در درگاه شود. اگر لازم شد، با احتیاط بالا ببرید.
    */
    'http' => [
        'timeout' => env('IRAN_PAYMENT_HTTP_TIMEOUT', 25),
        'connect_timeout' => env('IRAN_PAYMENT_HTTP_CONNECT_TIMEOUT', 10),
        'retry' => env('IRAN_PAYMENT_HTTP_RETRY', 0),
        'verify' => env('IRAN_PAYMENT_HTTP_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | لاگ
    |--------------------------------------------------------------------------
    | هر درخواست/پاسخ درگاه (بدون داده‌های حساس) در کانال مشخص‌شده ثبت می‌شود.
    */
    'logging' => [
        'enabled' => env('IRAN_PAYMENT_LOG', false),
        'channel' => env('IRAN_PAYMENT_LOG_CHANNEL'), // null => کانال پیش‌فرض
        'level' => env('IRAN_PAYMENT_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | آدرس بازگشت پس از پرداخت
    |--------------------------------------------------------------------------
    | اگر در خود کامپوننت یا در Payment مقدار return_url تعیین نشود، کاربر
    | به این آدرس‌ها هدایت می‌شود. null یعنی صفحهٔ نتیجهٔ داخلی پکیج.
    */
    'redirect' => [
        'success' => env('IRAN_PAYMENT_REDIRECT_SUCCESS'),
        'failed' => env('IRAN_PAYMENT_REDIRECT_FAILED'),
    ],

    /*
    |--------------------------------------------------------------------------
    | درگاه‌ها
    |--------------------------------------------------------------------------
    | کلید هر آیتم، همان «نام درگاه» است که در کد و در فیلد gateway استفاده
    | می‌شود. برای افزودن درگاه جدید کافی است یک کلاس بنویسید و اینجا یک
    | آیتم اضافه کنید - هیچ تغییری در هستهٔ پکیج لازم نیست.
    |
    | کلیدهای مشترک:
    |   driver      => کلاس درایور (اجباری)
    |   enabled     => فعال/غیرفعال بودن برای نمایش در کامپوننت
    |   title       => نام نمایشی
    |   description => توضیح کوتاه زیر نام در کامپوننت
    |   logo        => آدرس تصویر (اختیاری) - خالی باشد، حرف اول نام نمایش داده می‌شود
    |   color       => رنگ برند برای نشان پیش‌فرض
    |   sandbox     => حالت تست درگاه (در صورت پشتیبانی)
    */
    'gateways' => [

        'zarinpal' => [
            'driver' => ZarinpalGateway::class,
            'enabled' => env('ZARINPAL_ENABLED', true),
            'title' => 'زرین‌پال',
            'description' => 'پرداخت آنی با تمام کارت‌های عضو شتاب',
            'logo' => null,
            'color' => '#FFD400',
            'sandbox' => env('ZARINPAL_SANDBOX', false),
            'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        ],

        'zibal' => [
            'driver' => ZibalGateway::class,
            'enabled' => env('ZIBAL_ENABLED', true),
            'title' => 'زیبال',
            'description' => 'درگاه واسط با تسویهٔ سریع',
            'logo' => null,
            'color' => '#0055FF',
            'sandbox' => env('ZIBAL_SANDBOX', false),
            'merchant' => env('ZIBAL_MERCHANT', 'zibal'),
        ],

        'idpay' => [
            'driver' => IdPayGateway::class,
            'enabled' => env('IDPAY_ENABLED', true),
            'title' => 'آیدی‌پی',
            'description' => 'پرداخت امن با کارت‌های شتاب',
            'logo' => null,
            'color' => '#00B37E',
            'sandbox' => env('IDPAY_SANDBOX', false),
            'api_key' => env('IDPAY_API_KEY'),
        ],

        'payir' => [
            'driver' => PayIrGateway::class,
            'enabled' => env('PAYIR_ENABLED', true),
            'title' => 'پی‌آی‌آر',
            'description' => 'درگاه پرداخت پی',
            'logo' => null,
            'color' => '#6C3BF4',
            'api_key' => env('PAYIR_API_KEY'),
        ],

        'nextpay' => [
            'driver' => NextPayGateway::class,
            'enabled' => env('NEXTPAY_ENABLED', true),
            'title' => 'نکست‌پی',
            'description' => 'درگاه واسط نکست‌پی',
            'logo' => null,
            'color' => '#00C2A8',
            'api_key' => env('NEXTPAY_API_KEY'),
        ],

        'payping' => [
            'driver' => PayPingGateway::class,
            'enabled' => env('PAYPING_ENABLED', true),
            'title' => 'پی‌پینگ',
            'description' => 'پرداخت سریع با پی‌پینگ',
            'logo' => null,
            'color' => '#1A73E8',
            'api_key' => env('PAYPING_API_KEY'),
        ],

        'aqayepardakht' => [
            'driver' => AqayePardakhtGateway::class,
            'enabled' => env('AQAYEPARDAKHT_ENABLED', true),
            'title' => 'آقای پرداخت',
            'description' => 'درگاه واسط آقای پرداخت',
            'logo' => null,
            'color' => '#F5A623',
            // این درگاه مبلغ را به تومان می‌گیرد؛ پکیج تبدیل را خودکار انجام می‌دهد
            'amount_unit' => 'toman',
            'pin' => env('AQAYEPARDAKHT_PIN'),
        ],

        'paystar' => [
            'driver' => PayStarGateway::class,
            'enabled' => env('PAYSTAR_ENABLED', true),
            'title' => 'پی‌استار',
            'description' => 'درگاه پرداخت پی‌استار',
            'logo' => null,
            'color' => '#00A0E3',
            'api_key' => env('PAYSTAR_API_KEY'),
            'gateway_id' => env('PAYSTAR_GATEWAY_ID'),
            'payment_url' => env('PAYSTAR_PAYMENT_URL', 'https://core.paystar.ir/api/pardakht/payment'),
        ],

        'mellat' => [
            'driver' => MellatGateway::class,
            'enabled' => env('MELLAT_ENABLED', false),
            'title' => 'به‌پرداخت ملت',
            'description' => 'درگاه مستقیم بانک ملت',
            'logo' => null,
            'color' => '#E4002B',
            'terminal_id' => env('MELLAT_TERMINAL_ID'),
            'username' => env('MELLAT_USERNAME'),
            'password' => env('MELLAT_PASSWORD'),
        ],

        'sadad' => [
            'driver' => SadadGateway::class,
            'enabled' => env('SADAD_ENABLED', false),
            'title' => 'سداد (بانک ملی)',
            'description' => 'درگاه مستقیم بانک ملی ایران',
            'logo' => null,
            'color' => '#00539F',
            'terminal_id' => env('SADAD_TERMINAL_ID'),
            'merchant_id' => env('SADAD_MERCHANT_ID'),
            'private_key' => env('SADAD_PRIVATE_KEY'),   // مسیر فایل کلید خصوصی
            'key_password' => env('SADAD_KEY_PASSWORD', ''),
        ],

        'saman' => [
            'driver' => SamanGateway::class,
            'enabled' => env('SAMAN_ENABLED', false),
            'title' => 'سامان (سپ)',
            'description' => 'درگاه مستقیم بانک سامان',
            'logo' => null,
            'color' => '#0A4EA2',
            'terminal_id' => env('SAMAN_TERMINAL_ID'),
            'password' => env('SAMAN_PASSWORD', ''),
            'token_url' => env('SAMAN_TOKEN_URL', 'https://sep.shaparak.ir/onlinepg/onlinepg'),
            'payment_url' => env('SAMAN_PAYMENT_URL', 'https://sep.shaparak.ir/OnlinePG/OnlinePG'),
            'verify_url' => env('SAMAN_VERIFY_URL', 'https://sep.shaparak.ir/payments/referencepayment.asmx'),
        ],

        'parsian' => [
            'driver' => ParsianGateway::class,
            'enabled' => env('PARSIAN_ENABLED', false),
            'title' => 'پارسیان',
            'description' => 'درگاه مستقیم بانک پارسیان',
            'logo' => null,
            'color' => '#B5121B',
            'login_account' => env('PARSIAN_LOGIN_ACCOUNT'),
            'sale_url' => env('PARSIAN_SALE_URL', 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx'),
            'confirm_url' => env('PARSIAN_CONFIRM_URL', 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx'),
            'payment_url' => env('PARSIAN_PAYMENT_URL', 'https://pec.shaparak.ir/NewIPG/'),
            'namespace' => env('PARSIAN_SOAP_NAMESPACE', 'https://pec.shaparak.ir/'),
        ],

        'pasargad' => [
            'driver' => PasargadGateway::class,
            'enabled' => env('PASARGAD_ENABLED', false),
            'title' => 'پاسارگاد',
            'description' => 'درگاه مستقیم بانک پاسارگاد',
            'logo' => null,
            'color' => '#FFC20E',
            'merchant_code' => env('PASARGAD_MERCHANT_CODE'),
            'terminal_code' => env('PASARGAD_TERMINAL_CODE'),
            'private_key' => env('PASARGAD_PRIVATE_KEY'),  // مسیر فایل کلید خصوصی RSA
            'key_password' => env('PASARGAD_KEY_PASSWORD', ''),
            'payment_url' => env('PASARGAD_PAYMENT_URL', 'https://pep.shaparak.ir/gateway.aspx'),
            'verify_url' => env('PASARGAD_VERIFY_URL', 'https://pep.shaparak.ir/Api/v1/Payment/Verify'),
        ],

        /*
        | درگاه آزمایشی - برای تست جریان پرداخت در development و تست‌های خودکار.
        | در محیط production حتماً enabled را false کنید.
        */
        'fake' => [
            'driver' => \Taha20\IranPayment\Gateways\FakeGateway::class,
            'enabled' => env('IRAN_PAYMENT_FAKE_ENABLED', false),
            'title' => 'درگاه آزمایشی',
            'description' => 'فقط برای تست - بدون پرداخت واقعی',
            'logo' => null,
            'color' => '#8B5CF6',
            'outcome' => env('IRAN_PAYMENT_FAKE_OUTCOME', 'success'), // success|failed|cancel
        ],

    ],
];
