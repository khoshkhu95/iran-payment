<?php

namespace Taha20\IranPayment\Settings;

/**
 * تعریف یک فیلد در فرم تنظیمات درگاه.
 *
 * این کلاس فقط «شکل» فیلد را توصیف می‌کند و هیچ اطلاعی از ذخیره‌سازی یا
 * رندر ندارد؛ همین باعث می‌شود بتوانید بدون دست‌زدن به کامپوننت، فیلد
 * جدید به یک درگاه اضافه کنید.
 */
class Field
{
    /**
     * @param  array<string, string>  $options  برای نوع select: value => label
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type = 'text',
        public readonly bool $required = false,
        public readonly bool $secret = false,
        public readonly ?string $help = null,
        public readonly ?string $placeholder = null,
        public readonly mixed $default = null,
        public readonly array $options = [],
        public readonly bool $advanced = false,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public static function make(string $name, string $label, array $options = []): self
    {
        return new self(
            name: $name,
            label: $label,
            type: (string) ($options['type'] ?? 'text'),
            required: (bool) ($options['required'] ?? false),
            secret: (bool) ($options['secret'] ?? false),
            help: $options['help'] ?? null,
            placeholder: $options['placeholder'] ?? null,
            default: $options['default'] ?? null,
            options: (array) ($options['options'] ?? []),
            advanced: (bool) ($options['advanced'] ?? false),
        );
    }

    /**
     * فیلد متنی معمولی.
     */
    public static function text(string $name, string $label, array $options = []): self
    {
        return self::make($name, $label, $options);
    }

    /**
     * مقدار حساس (کلید API، رمز، پین، کلید خصوصی).
     * در فرم ماسک می‌شود و در دیتابیس رمزنگاری‌شده ذخیره می‌گردد.
     */
    public static function secret(string $name, string $label, array $options = []): self
    {
        return self::make($name, $label, array_merge([
            'type' => 'password',
            'secret' => true,
        ], $options));
    }

    /**
     * کلید خصوصی RSA (چندخطی) - مسیر فایل یا محتوای PEM.
     */
    public static function keyFile(string $name, string $label, array $options = []): self
    {
        return self::make($name, $label, array_merge([
            'type' => 'textarea',
            'secret' => true,
        ], $options));
    }

    /**
     * سوییچ بله/خیر.
     */
    public static function toggle(string $name, string $label, array $options = []): self
    {
        return self::make($name, $label, array_merge([
            'type' => 'toggle',
        ], $options));
    }

    /**
     * لیست کشویی.
     *
     * @param  array<string, string>  $choices
     */
    public static function select(string $name, string $label, array $choices, array $options = []): self
    {
        return self::make($name, $label, array_merge([
            'type' => 'select',
            'options' => $choices,
        ], $options));
    }

    /**
     * آدرس اینترنتی (معمولاً endpoint درگاه).
     */
    public static function url(string $name, string $label, array $options = []): self
    {
        return self::make($name, $label, array_merge([
            'type' => 'url',
            'advanced' => true,
        ], $options));
    }

    /**
     * آیا مقدار این فیلد باید از چشم کاربر پنهان بماند؟
     */
    public function isMasked(): bool
    {
        return $this->secret || $this->type === 'password';
    }
}
