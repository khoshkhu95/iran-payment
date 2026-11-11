<?php

namespace Taha20\IranPayment\Settings;

use Illuminate\Support\Str;

/**
 * شناسنامهٔ فیلدهای تنظیمات هر درگاه.
 *
 * سه لایه دارد:
 *   ۱) تعریف صریح برای درگاه‌های همراه پکیج (برچسب، توضیح، اجباری بودن)
 *   ۲) افزودن درگاه دلخواه در زمان اجرا با extend()
 *   ۳) حدس هوشمند برای درگاه‌هایی که هیچ تعریفی ندارند؛ در این حالت هر
 *      کلید موجود در config آن درگاه به یک فیلد تبدیل می‌شود تا درگاه
 *      اختصاصی پروژه هم بدون نوشتن حتی یک خط کد، فرم تنظیمات بگیرد.
 */
class GatewaySchema
{
    /**
     * کلیدهایی که فیلد تنظیمات نیستند و نباید در فرم نمایش داده شوند.
     *
     * @var array<int, string>
     */
    protected const RESERVED_KEYS = [
        'driver', 'name', 'title', 'description', 'logo', 'color', 'enabled', 'sandbox',
    ];

    /** @var array<string, array{sandbox: bool, fields: array<int, Field>}> */
    protected array $definitions;

    /**
     * @param  array<string, array{sandbox?: bool, fields: array<int, Field>}>|null  $definitions
     */
    public function __construct(?array $definitions = null)
    {
        $this->definitions = $definitions ?? static::definitions();
    }

    /**
     * تعریف پیش‌فرض همهٔ درگاه‌های همراه پکیج.
     *
     * @return array<string, array{sandbox: bool, fields: array<int, Field>}>
     */
    public static function definitions(): array
    {
        return [
            'zarinpal' => [
                'sandbox' => true,
                'fields' => [
                    Field::text('merchant_id', 'مرچنت آیدی', [
                        'required' => true,
                        'help' => 'کد ۳۶ کاراکتری از پنل زرین‌پال → درگاه‌ها → تنظیمات',
                        'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                    ]),
                ],
            ],

            'zibal' => [
                'sandbox' => false,
                'fields' => [
                    Field::text('merchant', 'شناسهٔ مرچنت', [
                        'required' => true,
                        'default' => 'zibal',
                        'help' => 'برای تست، مقدار zibal را بگذارید؛ در حالت واقعی کد مرچنت خودتان را وارد کنید.',
                    ]),
                ],
            ],

            'idpay' => [
                'sandbox' => true,
                'fields' => [
                    Field::secret('api_key', 'کلید API', [
                        'required' => true,
                        'help' => 'از پنل آیدی‌پی → تنظیمات درگاه. طول آن ۳۶ کاراکتر است.',
                    ]),
                ],
            ],

            'payir' => [
                'sandbox' => false,
                'fields' => [
                    Field::secret('api_key', 'توکن API', [
                        'required' => true,
                        'help' => 'از پنل پی‌آی‌آر → درگاه‌ها → کلید API',
                    ]),
                ],
            ],

            'nextpay' => [
                'sandbox' => false,
                'fields' => [
                    Field::secret('api_key', 'کلید API', [
                        'required' => true,
                        'help' => 'از پنل نکست‌پی → تنظیمات → کلید API',
                    ]),
                ],
            ],

            'payping' => [
                'sandbox' => false,
                'fields' => [
                    Field::secret('api_key', 'توکن دسترسی', [
                        'required' => true,
                        'help' => 'از پنل پی‌پینگ → تنظیمات حساب → توکن API',
                    ]),
                ],
            ],

            'aqayepardakht' => [
                'sandbox' => false,
                'fields' => [
                    Field::secret('pin', 'پین درگاه', [
                        'required' => true,
                        'help' => 'از پنل آقای پرداخت → درگاه‌ها → پین',
                    ]),
                    Field::select('amount_unit', 'واحد مبلغ ارسالی', [
                        'rial' => 'ریال',
                        'toman' => 'تومان',
                    ], [
                        'default' => 'toman',
                        'help' => 'مبلغی که این درگاه انتظار دارد. پکیج بقیهٔ تبدیل‌ها را خودکار انجام می‌دهد.',
                    ]),
                ],
            ],

            'paystar' => [
                'sandbox' => false,
                'fields' => [
                    Field::secret('api_key', 'کلید API', [
                        'required' => true,
                        'help' => 'از پنل پی‌استار → درگاه‌ها → کلید API',
                    ]),
                    Field::text('gateway_id', 'شناسهٔ درگاه', [
                        'required' => true,
                        'help' => 'شمارهٔ درگاهی که در پنل پی‌استار ساخته‌اید.',
                    ]),
                    Field::url('payment_url', 'آدرس سرویس پرداخت', [
                        'default' => 'https://core.paystar.ir/api/pardakht/payment',
                    ]),
                ],
            ],

            'mellat' => [
                'sandbox' => false,
                'fields' => [
                    Field::text('terminal_id', 'شناسهٔ ترمینال', ['required' => true]),
                    Field::text('username', 'نام کاربری', ['required' => true]),
                    Field::secret('password', 'رمز عبور', ['required' => true]),
                    Field::url('payment_url', 'آدرس صفحهٔ پرداخت', [
                        'default' => 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
                    ]),
                    Field::url('endpoint', 'آدرس وب‌سرویس', [
                        'default' => 'https://bpm.shaparak.ir/pgwchannel/services/pgw',
                    ]),
                    Field::text('namespace', 'فضای نام SOAP', [
                        'default' => 'http://interfaces.core.sw.bps.com/',
                        'advanced' => true,
                    ]),
                ],
            ],

            'sadad' => [
                'sandbox' => false,
                'fields' => [
                    Field::text('terminal_id', 'شناسهٔ ترمینال', ['required' => true]),
                    Field::text('merchant_id', 'شناسهٔ پذیرنده', ['required' => true]),
                    Field::keyFile('private_key', 'کلید خصوصی', [
                        'required' => true,
                        'help' => 'مسیر فایل کلید خصوصی (مثلاً storage/app/sadad.key) یا محتوای کامل PEM.',
                        'placeholder' => '/var/www/storage/app/sadad.key',
                    ]),
                    Field::secret('key_password', 'رمز کلید خصوصی', [
                        'help' => 'اگر کلید شما رمز ندارد، خالی بگذارید.',
                    ]),
                    Field::url('request_url', 'آدرس درخواست پرداخت', [
                        'default' => 'https://sadad.shaparak.ir/VPG/api/v0/Request/PaymentRequest',
                    ]),
                    Field::url('verify_url', 'آدرس تأیید پرداخت', [
                        'default' => 'https://sadad.shaparak.ir/VPG/api/v0/Advice/Verify',
                    ]),
                    Field::url('payment_url', 'آدرس صفحهٔ پرداخت', [
                        'default' => 'https://sadad.shaparak.ir/VPG/Purchase',
                    ]),
                ],
            ],

            'saman' => [
                'sandbox' => false,
                'fields' => [
                    Field::text('terminal_id', 'شناسهٔ ترمینال', ['required' => true]),
                    Field::secret('password', 'رمز ترمینال', [
                        'help' => 'در برخی ترمینال‌های سامان لازم نیست.',
                    ]),
                    Field::url('token_url', 'آدرس دریافت توکن', [
                        'default' => 'https://sep.shaparak.ir/onlinepg/onlinepg',
                    ]),
                    Field::url('payment_url', 'آدرس صفحهٔ پرداخت', [
                        'default' => 'https://sep.shaparak.ir/OnlinePG/OnlinePG',
                    ]),
                    Field::url('verify_url', 'آدرس تأیید پرداخت', [
                        'default' => 'https://sep.shaparak.ir/payments/referencepayment.asmx',
                    ]),
                    Field::text('namespace', 'فضای نام SOAP', [
                        'default' => 'http://tempuri.org/',
                        'advanced' => true,
                    ]),
                ],
            ],

            'parsian' => [
                'sandbox' => false,
                'fields' => [
                    Field::text('login_account', 'شناسهٔ پذیرنده (LoginAccount)', [
                        'required' => true,
                        'help' => 'کد پذیرندگی که بانک پارسیان به شما داده است.',
                    ]),
                    Field::url('sale_url', 'آدرس درخواست پرداخت', [
                        'default' => 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx',
                    ]),
                    Field::url('confirm_url', 'آدرس تأیید پرداخت', [
                        'default' => 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx',
                    ]),
                    Field::url('payment_url', 'آدرس صفحهٔ پرداخت', [
                        'default' => 'https://pec.shaparak.ir/NewIPG/',
                    ]),
                    Field::text('namespace', 'فضای نام SOAP', [
                        'default' => 'https://pec.shaparak.ir/',
                        'advanced' => true,
                    ]),
                ],
            ],

            'pasargad' => [
                'sandbox' => false,
                'fields' => [
                    Field::text('merchant_code', 'کد پذیرنده', ['required' => true]),
                    Field::text('terminal_code', 'کد ترمینال', ['required' => true]),
                    Field::keyFile('private_key', 'کلید خصوصی RSA', [
                        'required' => true,
                        'help' => 'مسیر فایل کلید یا محتوای PEM. کلید باید از طرف بانک پاسارگاد صادر شده باشد.',
                    ]),
                    Field::secret('key_password', 'رمز کلید خصوصی'),
                    Field::url('payment_url', 'آدرس صفحهٔ پرداخت', [
                        'default' => 'https://pep.shaparak.ir/gateway.aspx',
                    ]),
                    Field::url('verify_url', 'آدرس تأیید پرداخت', [
                        'default' => 'https://pep.shaparak.ir/Api/v1/Payment/Verify',
                    ]),
                ],
            ],

            'fake' => [
                'sandbox' => false,
                'fields' => [
                    Field::select('outcome', 'نتیجهٔ شبیه‌سازی‌شده', [
                        'success' => 'پرداخت موفق',
                        'failed' => 'پرداخت ناموفق',
                        'cancel' => 'لغو توسط کاربر',
                    ], [
                        'default' => 'success',
                        'help' => 'این درگاه هیچ پرداخت واقعی انجام نمی‌دهد؛ فقط برای تست جریان است.',
                    ]),
                ],
            ],
        ];
    }

    /**
     * افزودن یا بازنویسی تعریف یک درگاه در زمان اجرا.
     *
     * @param  array<int, Field>  $fields
     */
    public function extend(string $gateway, array $fields, bool $sandbox = false): static
    {
        $this->definitions[$gateway] = [
            'sandbox' => $sandbox,
            'fields' => $fields,
        ];

        return $this;
    }

    public function has(string $gateway): bool
    {
        return isset($this->definitions[$gateway]);
    }

    /**
     * آیا این درگاه حالت تست (sandbox) دارد؟
     */
    public function supportsSandbox(string $gateway): bool
    {
        return (bool) ($this->definitions[$gateway]['sandbox'] ?? false);
    }

    /**
     * فیلدهای یک درگاه.
     *
     * اگر درگاه تعریف صریح نداشته باشد، از روی کلیدهای config حدس زده می‌شود.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, Field>
     */
    public function fieldsFor(string $gateway, array $config = []): array
    {
        if (isset($this->definitions[$gateway])) {
            return $this->definitions[$gateway]['fields'];
        }

        return $this->guess($config);
    }

    /**
     * ساخت فیلد از روی کلیدهای config برای درگاه‌های ناشناس.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, Field>
     */
    protected function guess(array $config): array
    {
        $fields = [];

        foreach ($config as $key => $value) {
            if (! is_string($key) || in_array($key, self::RESERVED_KEYS, true)) {
                continue;
            }

            // مقدار پیش‌فرض درگاه (مثل merchant=zibal) نباید «مقدار ذخیره‌شده» تلقی شود
            $default = is_scalar($value) ? $value : null;
            $sensitive = (bool) preg_match('/key|password|pass|secret|token|pin|private/i', $key);

            $field = match (true) {
                is_bool($value) => Field::toggle($key, $this->humanize($key)),
                str_ends_with($key, '_url') || $key === 'endpoint' => Field::url($key, $this->humanize($key), [
                    'default' => $default,
                ]),
                $sensitive => Field::secret($key, $this->humanize($key), [
                    'default' => null,
                ]),
                default => Field::text($key, $this->humanize($key), [
                    'default' => $default,
                ]),
            };

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * نام کلید انگلیسی → برچسب خوانا برای درگاه‌های ناشناس.
     */
    protected function humanize(string $key): string
    {
        return Str::of($key)->replace(['_', '-'], ' ')->title()->toString();
    }
}
