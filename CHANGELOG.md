# تغییرات

همهٔ تغییرات مهم این پکیج در این فایل ثبت می‌شود.
قالب بر اساس [Keep a Changelog](https://keepachangelog.com/fa/1.1.0/) و
نسخه‌گذاری بر اساس [Semantic Versioning](https://semver.org/lang/fa/).

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

[1.0.0]: https://github.com/khoshkhu95/iran-payment/releases/tag/v1.0.0
