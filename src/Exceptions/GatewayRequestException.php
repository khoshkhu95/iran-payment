<?php

namespace Taha20\IranPayment\Exceptions;

/**
 * خطای ارتباط با درگاه یا پاسخ نامعتبر از سمت درگاه.
 */
class GatewayRequestException extends IranPaymentException
{
    protected string $userMessage = 'ارتباط با درگاه پرداخت برقرار نشد. لطفاً دوباره تلاش کنید.';

    /** @var array<string, mixed> */
    protected array $response = [];

    public function __construct(string $message, array $response = [])
    {
        parent::__construct($message);
        $this->response = $response;
    }

    /** @return array<string, mixed> */
    public function response(): array
    {
        return $this->response;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(string $gateway, ?string $message, array $response = []): self
    {
        return new self(
            sprintf('درگاه [%s] درخواست را نپذیرفت: %s', $gateway, $message ?: 'بدون پیام'),
            $response
        );
    }

    public static function connection(string $gateway, string $reason): self
    {
        return new self(sprintf('اتصال به درگاه [%s] برقرار نشد: %s', $gateway, $reason));
    }

    public static function http(string $gateway, int $status, string $body = ''): self
    {
        return new self(
            sprintf('درگاه [%s] کد وضعیت %d برگرداند. %s', $gateway, $status, mb_substr($body, 0, 500)),
            ['http_status' => $status]
        );
    }

    public static function invalidResponse(string $gateway): self
    {
        return new self(sprintf('پاسخ درگاه [%s] قابل خواندن نبود.', $gateway));
    }
}
