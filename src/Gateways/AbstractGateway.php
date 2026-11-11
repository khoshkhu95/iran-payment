<?php

namespace Taha20\IranPayment\Gateways;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Taha20\IranPayment\Contracts\Gateway;
use Taha20\IranPayment\Exceptions\ConfigurationException;
use Taha20\IranPayment\Exceptions\GatewayRequestException;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\Amount;
use Taha20\IranPayment\Support\RefundResult;

/**
 * پایهٔ مشترک درگاه‌ها.
 *
 * هر درگاه جدید فقط باید purchase() و verify() را بنویسد؛ بقیهٔ کارهای
 * تکراری (HTTP، لاگ، تبدیل واحد پول، خواندن تنظیمات، بازگشت وجه پیش‌فرض)
 * اینجا انجام می‌شود.
 */
abstract class AbstractGateway implements Gateway
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config = []) {}

    public function name(): string
    {
        return (string) ($this->config['name'] ?? class_basename(static::class));
    }

    public function title(): string
    {
        return (string) ($this->config['title'] ?? $this->name());
    }

    public function metadata(): array
    {
        return [
            'title' => $this->title(),
            'description' => $this->config['description'] ?? null,
            'logo' => $this->config['logo'] ?? null,
            'color' => $this->config['color'] ?? '#6366F1',
        ];
    }

    public function supportsRefund(): bool
    {
        return false;
    }

    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        return RefundResult::failure(sprintf('درگاه %s از بازگشت وجه پشتیبانی نمی‌کند.', $this->title()));
    }

    /*
    |--------------------------------------------------------------------------
    | ابزارهای تنظیمات
    |--------------------------------------------------------------------------
    */

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * خواندن یک تنظیم اجباری؛ در صورت خالی بودن، خطای واضح می‌دهد.
     */
    protected function require(string $key): string
    {
        $value = $this->config[$key] ?? null;

        if ($value === null || $value === '') {
            throw ConfigurationException::missing($this->name(), $key);
        }

        return (string) $value;
    }

    protected function bool(string $key, bool $default = false): bool
    {
        $value = $this->config[$key] ?? $default;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /*
    |--------------------------------------------------------------------------
    | ابزارهای مبلغ
    |--------------------------------------------------------------------------
    */

    /**
     * مبلغ تراکنش به واحدی که این درگاه انتظار دارد (ریال یا تومان).
     */
    protected function amountFor(Transaction $transaction): int
    {
        $unit = strtolower((string) $this->config('amount_unit', 'rial'));
        $amount = (int) $transaction->amount;

        return $unit === 'toman' ? Amount::toToman($amount) : $amount;
    }

    /**
     * تبدیل مبلغی که درگاه برگردانده به ریال (برای مقایسهٔ امنیتی).
     */
    protected function fromGatewayAmount(int|float|string|null $amount): int
    {
        $unit = strtolower((string) $this->config('amount_unit', 'rial'));
        $value = (int) round((float) $amount);

        return $unit === 'toman' ? $value * 10 : $value;
    }

    /**
     * آیا مبلغ تأییدشده با مبلغ تراکنش می‌خواند؟
     * (درگاه‌ها همیشه مبلغ را برنمی‌گردانند؛ اگر null بود true است)
     */
    protected function amountMatches(Transaction $transaction, int|float|string|null $gatewayAmount): bool
    {
        if ($gatewayAmount === null || $gatewayAmount === '') {
            return true;
        }

        return $this->fromGatewayAmount($gatewayAmount) === (int) $transaction->amount;
    }

    /*
    |--------------------------------------------------------------------------
    | ابزارهای تراکنش
    |--------------------------------------------------------------------------
    */

    protected function callbackUrl(Transaction $transaction): string
    {
        return (string) ($transaction->callback_url ?: route('iran-payment.callback', [
            'gateway' => $this->name(),
            'uuid' => $transaction->uuid,
        ]));
    }

    /**
     * بعضی درگاه‌های بانکی فقط شناسهٔ عددی می‌پذیرند؛ از id تراکنش استفاده
     * می‌کنیم و اگر عددی نبود، یک عدد پایدار از uuid می‌سازیم.
     */
    protected function numericOrderId(Transaction $transaction): string
    {
        $id = $transaction->getKey();

        if (is_numeric($id)) {
            return (string) $id;
        }

        return (string) (hexdec(substr(md5((string) $transaction->uuid), 0, 8)) % 2147483647);
    }

    /**
     * استخراج اولین مقدار موجود از میان چند کلید احتمالی در داده‌های بازگشتی.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    protected function value(array $data, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                return $data[$key];
            }
        }

        return $default;
    }

    /*
    |--------------------------------------------------------------------------
    | لایهٔ HTTP
    |--------------------------------------------------------------------------
    */

    protected function client(): PendingRequest
    {
        $client = Http::acceptJson()
            ->timeout((int) config('iran-payment.http.timeout', 25))
            ->connectTimeout((int) config('iran-payment.http.connect_timeout', 10));

        if (! config('iran-payment.http.verify', true)) {
            $client = $client->withoutVerifying();
        }

        $retry = (int) config('iran-payment.http.retry', 0);

        if ($retry > 0) {
            // عمداً throw=false تا خودمان خطای خوانا تولید کنیم
            $client = $client->retry($retry, 200, throw: false);
        }

        return $client;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    protected function postJson(string $url, array $payload, array $headers = []): array
    {
        return $this->decode($this->send('post', $url, $payload, $headers));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    protected function postForm(string $url, array $payload, array $headers = []): array
    {
        return $this->decode($this->send('asForm', $url, $payload, $headers));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function getJson(string $url, array $query = [], array $headers = []): array
    {
        return $this->decode($this->send('get', $url, $query, $headers));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    protected function send(string $method, string $url, array $payload, array $headers = []): Response
    {
        $client = $this->client();

        if ($headers !== []) {
            $client = $client->withHeaders($headers);
        }

        try {
            $response = match ($method) {
                'get' => $client->get($url, $payload),
                'asForm' => $client->asForm()->post($url, $payload),
                default => $client->asJson()->post($url, $payload),
            };
        } catch (ConnectionException $e) {
            $this->log('connection_failed', ['url' => $url, 'error' => $e->getMessage()]);

            throw GatewayRequestException::connection($this->name(), $e->getMessage());
        }

        $this->log('http', [
            'url' => $url,
            'status' => $response->status(),
            'response' => mb_substr($response->body(), 0, 2000),
        ]);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            throw GatewayRequestException::invalidResponse($this->name());
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function log(string $event, array $context = []): void
    {
        if (! config('iran-payment.logging.enabled', false)) {
            return;
        }

        $channel = config('iran-payment.logging.channel');
        $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();

        $logger->log(
            (string) config('iran-payment.logging.level', 'info'),
            sprintf('[iran-payment][%s] %s', $this->name(), $event),
            $context
        );
    }
}
