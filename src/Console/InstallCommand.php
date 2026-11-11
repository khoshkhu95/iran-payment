<?php

namespace Taha20\IranPayment\Console;

use Illuminate\Console\Command;

/**
 * نصب سریع پکیج در یک پروژهٔ تازه.
 *
 *   php artisan iran-payment:install
 */
class InstallCommand extends Command
{
    protected $signature = 'iran-payment:install
                            {--migrate : مایگریشن را بدون پرسش اجرا کن}
                            {--force : فایل‌های از قبل منتشرشده را بازنویسی کن}';

    protected $description = 'انتشار تنظیمات، ویوها و مایگریشن پکیج درگاه‌های پرداخت ایرانی';

    public function handle(): int
    {
        $this->components->info('نصب پکیج iran-payment');

        $force = (bool) $this->option('force');

        $this->call('vendor:publish', [
            '--tag' => 'iran-payment-config',
            '--force' => $force,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'iran-payment-views',
            '--force' => $force,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'iran-payment-migrations',
            '--force' => $force,
        ]);

        $shouldMigrate = $this->option('migrate')
            || $this->confirm('آیا مایگریشن‌های جدول تراکنش‌ها و تنظیمات درگاه اجرا شوند؟', true);

        if ($shouldMigrate) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->components->info('نصب کامل شد.');

        $this->line('  مراحل بعدی:');
        $this->line('  ۱) در فایل <fg=yellow>.env</> کلید درگاه موردنظر را تنظیم کنید، مثلاً:');
        $this->line('     <fg=gray>ZARINPAL_MERCHANT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</>');
        $this->line('  ۲) درگاه پیش‌فرض را مشخص کنید:');
        $this->line('     <fg=gray>IRAN_PAYMENT_GATEWAY=zarinpal</>');
        $this->line('  ۳) کامپوننت پرداخت را در ویو خود قرار دهید:');
        $this->line('     <fg=gray><x-iran-payment :amount="150000" order-id="ORD-1" /></>');
        $this->line('  ۴) (اختیاری) فرم تنظیمات درگاه‌ها را در پنل مدیریت بگذارید:');
        $this->line('     <fg=gray><x-iran-payment-settings /></>');
        $this->line('     ⚠️ روت ذخیرهٔ فرم را با میدل‌ور احراز هویت محافظت کنید:');
        $this->line('     <fg=gray>config/iran-payment.php → settings.middleware</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
