<?php

namespace Taha20\IranPayment\Support;

/**
 * نتیجهٔ عملیات بازگشت وجه.
 */
final class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $referenceId = null,
        public readonly ?string $message = null,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function success(?string $referenceId = null, ?string $message = null, array $raw = []): self
    {
        return new self(true, $referenceId, $message ?? 'بازگشت وجه با موفقیت انجام شد.', $raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function failure(?string $message = null, array $raw = []): self
    {
        return new self(false, null, $message ?? 'بازگشت وجه انجام نشد.', $raw);
    }
}
