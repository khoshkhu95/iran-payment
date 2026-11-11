# تغییرات

همهٔ تغییرات مهم این پکیج در این فایل ثبت می‌شود.
قالب بر اساس [Keep a Changelog](https://keepachangelog.com/fa/1.1.0/) و
نسخه‌گذاری بر اساس [Semantic Versioning](https://semver.org/lang/fa/).

## [Unreleased]

## [1.1.0] - 2026-11-11

### افزوده شد

- کامپوننت `<x-iran-payment-settings />`: فرم کامل تنظیمات همهٔ درگاه‌ها با
  تب‌بندی، سوییچ فعال/غیرفعال، حالت تست، انتخاب درگاه پیش‌فرض و هشدار
  فیلدهای ناقص. استایل مستقل از فریم‌ورک، RTL و دارک‌مود.
- لایهٔ تنظیمات (`Settings\GatewaySettings`) با سه درایور ذخیره‌سازی:
  `database`، `env` و `array`. اولویت خواندن: مقدار ذخیره‌شده ← `.env` ← config.
- شناسنامهٔ فیلدهای همهٔ ۱۴ درگاه (`Settings\GatewaySchema`) با برچسب و
  راهنمای فارسی؛ درگاه‌های سفارشی بدون تعریف هم فرم می‌گیرند (حدس از config).
- `Settings\EnvWriter` برای نوشتن اتمیک و ایمن در فایل `.env`.
- روت‌های `iran-payment.settings.update` و `iran-payment.settings.reset`.
- متد `IranPayment::settings()` و `PaymentManager::configFor()`.
- مایگریشن جدول `iran_payment_settings` (مقادیر حساس رمزنگاری‌شده).
- ۲۰ تست جدید (مجموع: ۳۳ تست).
- پوشش لاراول ۱۳ در ماتریس تست CI (testbench `^11.0`).
  سازگاری با لاراول ۱۳.۴۲ و PHP 8.3/8.4 به‌صورت واقعی اجرا و تأیید شد.

### تغییر کرد

- `PaymentManager` تنظیمات ذخیره‌شده از پنل را روی config اعمال می‌کند
  (هم در ساخت درایور و هم در لیست درگاه‌های فعال).
- `php artisan iran-payment:install` هر دو مایگریشن را منتشر می‌کند.
- `routes.enabled` و `settings.routes` اکنون مستقل از هم‌اند.

## [1.0.0] - 2026-11-11

### افزوده شد

- هستهٔ پرداخت بر پایهٔ `PaymentManager` (الگوی Manager لاراول) با قابلیت
  سوئیچ بین درگاه‌ها از طریق config.
- قرارداد `Contracts\Gateway` و کلاس پایهٔ `AbstractGateway`.
- `PaymentProcessor` مستقل از HTTP برای استفاده در کنترلر، Job و Command.
- کامپوننت `<x-iran-payment />` با استایل مستقل (بدون Tailwind/Vite)،
  پشتیبانی از تم روشن/تاریک و RTL.
- توکن امضاشدهٔ پرداخت برای جلوگیری از دست‌کاری مبلغ در سمت کلاینت.
- مدل و مایگریشن تراکنش‌ها با پشتیبانی از اتصال morph به مدل دامنه.
- لایهٔ ذخیره‌سازی قابل تعویض (`DatabaseTransactionStore` / `ArrayTransactionStore`).
- رویدادهای `PaymentInitiated`، `PaymentSucceeded`، `PaymentFailed` و `PaymentRefunded`.
- دستور `php artisan iran-payment:install`.
- درگاه‌ها: زرین‌پال، زیبال، آیدی‌پی، پی‌آی‌آر، نکست‌پی، پی‌پینگ،
  آقای‌پرداخت، پی‌استار، ملت، سداد، سامان، پارسیان، پاسارگاد و درگاه آزمایشی.
- پشتیبانی از بازگشت وجه در درگاه‌های ملت و درگاه آزمایشی.
- مستندات معماری در `docs/ARCHITECTURE.md`.

[1.1.0]: https://github.com/khoshkhu95/iran-payment/releases/tag/v1.1.0
[1.0.0]: https://github.com/khoshkhu95/iran-payment/releases/tag/v1.0.0
