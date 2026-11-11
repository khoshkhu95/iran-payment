<?php

namespace Taha20\IranPayment\Support;

/**
 * پاسخ درگاه هنگام شروع پرداخت.
 *
 * سه حالت ممکن است:
 *  - redirect: درگاه یک URL می‌دهد و باید کاربر را به آن فرستاد (اکثر درگاه‌ها)
 *  - form:     درگاه‌های بانکی قدیمی نیاز به POST فرم دارند
 *  - html:     درگاه خودش HTML برمی‌گرداند
 */
final class PaymentResponse
{
    public const REDIRECT = 'redirect';

    public const FORM = 'form';

    public const HTML = 'html';

    /**
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $url = null,
        public readonly ?string $formAction = null,
        public readonly array $formData = [],
        public readonly ?string $html = null,
        public readonly ?string $reference = null,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function redirect(string $url, ?string $reference = null, array $raw = []): self
    {
        return new self(self::REDIRECT, url: $url, reference: $reference, raw: $raw);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $raw
     */
    public static function form(string $action, array $data, ?string $reference = null, array $raw = []): self
    {
        return new self(self::FORM, formAction: $action, formData: $data, reference: $reference, raw: $raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function html(string $html, ?string $reference = null, array $raw = []): self
    {
        return new self(self::HTML, html: $html, reference: $reference, raw: $raw);
    }

    public function isRedirect(): bool
    {
        return $this->type === self::REDIRECT;
    }

    public function isForm(): bool
    {
        return $this->type === self::FORM;
    }

    /**
     * آدرسی که کاربر باید به آن هدایت شود (برای حالت فرم، آدرس action).
     */
    public function targetUrl(): ?string
    {
        return $this->type === self::FORM ? $this->formAction : $this->url;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'url' => $this->url,
            'form_action' => $this->formAction,
            'form_data' => $this->formData,
            'reference' => $this->reference,
        ];
    }
}
