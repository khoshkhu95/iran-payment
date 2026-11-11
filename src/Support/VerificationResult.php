<?php

namespace Taha20\IranPayment\Support;

/**
 * نتیجهٔ تأیید پرداخت پس از بازگشت کاربر از درگاه.
 */
final class VerificationResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $referenceId = null,
        public readonly ?string $trackingCode = null,
        public readonly ?string $cardNumber = null,
        public readonly ?int $amount = null,
        public readonly ?string $message = null,
        public readonly array $raw = [],
        public readonly bool $canceled = false,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function success(
        ?string $referenceId = null,
        ?string $trackingCode = null,
        ?string $cardNumber = null,
        ?int $amount = null,
        ?string $message = null,
        array $raw = [],
    ): self {
        return new self(true, $referenceId, $trackingCode, $cardNumber, $amount, $message ?? 'پرداخت با موفقیت انجام شد.', $raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function failure(?string $message = null, array $raw = [], ?string $referenceId = null, bool $canceled = false): self
    {
        return new self(false, $referenceId, null, null, null, $message ?? 'پرداخت ناموفق بود.', $raw, $canceled);
    }

    /**
     * کاربر خودش پرداخت را لغو کرده است (نه خطای فنی).
     *
     * @param  array<string, mixed>  $raw
     */
    public static function canceled(?string $message = null, array $raw = [], ?string $referenceId = null): self
    {
        return self::failure($message ?? 'پرداخت توسط کاربر لغو شد.', $raw, $referenceId, true);
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }
}
