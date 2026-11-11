<?php

namespace Taha20\IranPayment\Contracts;

use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\RefundResult;
use Taha20\IranPayment\Support\VerificationResult;

/**
 * قرارداد مشترک همهٔ درگاه‌ها.
 *
 * کل هستهٔ پکیج فقط این اینترفیس را می‌شناسد؛ بنابراین هر درگاه جدیدی
 * (حتی درگاه اختصاصی خودتان یا یک درگاه خارجی) با پیاده‌سازی همین چند
 * متد، بدون هیچ تغییری در هسته قابل استفاده می‌شود.
 */
interface Gateway
{
    /**
     * نام یکتای درگاه؛ همان کلیدی که در config/iran-payment.php تعریف شده است.
     * این نام در فیلد gateway تراکنش ذخیره و برای سوئیچ استفاده می‌شود.
     */
    public function name(): string;

    /**
     * نام نمایشی درگاه برای رابط کاربری (مثلاً «زرین‌پال»).
     */
    public function title(): string;

    /**
     * شروع پرداخت: ساخت تراکنش در درگاه و برگرداندن پاسخ مناسب
     * (ریدایرکت به درگاه یا فرم auto-submit برای درگاه‌های بانکی قدیمی).
     */
    public function purchase(Transaction $transaction): PaymentResponse;

    /**
     * تأیید پرداخت پس از بازگشت کاربر از درگاه.
     *
     * @param  array  $callback  تمام داده‌هایی که درگاه به آدرس بازگشت فرستاده است
     */
    public function verify(Transaction $transaction, array $callback): VerificationResult;

    /**
     * آیا این درگاه از بازگشت وجه (refund) پشتیبانی می‌کند؟
     */
    public function supportsRefund(): bool;

    /**
     * بازگشت وجه (در صورت پشتیبانی درگاه).
     */
    public function refund(Transaction $transaction, ?int $amount = null): RefundResult;

    /**
     * متادیتای نمایشی درگاه برای کامپوننت: title, description, logo, color
     *
     * @return array{title:string,description:?string,logo:?string,color:?string}
     */
    public function metadata(): array;
}
