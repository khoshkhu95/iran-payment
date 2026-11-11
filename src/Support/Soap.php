<?php

namespace Taha20\IranPayment\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Taha20\IranPayment\Exceptions\GatewayRequestException;

/**
 * کلاینت SOAP سبک.
 *
 * درگاه‌های بانکی قدیمی (ملت، پارسیان، سامان، ...) فقط SOAP دارند. برای اینکه
 * نصب پکیج به اکستنشن ext-soap وابسته نشود، پاکت XML را خودمان می‌سازیم و
 * پاسخ را با SimpleXML می‌خوانیم. نتیجه: پکیج روی هر هاستی بدون تنظیم اضافه
 * کار می‌کند.
 */
final class Soap
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws GatewayRequestException
     */
    public static function call(string $url, string $operation, array $params, string $namespace = '', ?string $gateway = null): array
    {
        $envelope = self::envelope($operation, $params, $namespace);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '"'.($namespace ? rtrim($namespace, '/').'/' : '').$operation.'"',
            ])
                ->timeout((int) config('iran-payment.http.timeout', 25))
                ->withBody($envelope, 'text/xml; charset=utf-8')
                ->post($url);
        } catch (ConnectionException $e) {
            throw GatewayRequestException::connection($gateway ?? $operation, $e->getMessage());
        }

        $body = $response->body();

        if ($response->failed() && ! str_contains($body, 'Envelope')) {
            throw GatewayRequestException::http($gateway ?? $operation, $response->status(), $body);
        }

        return self::parse($body);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function envelope(string $operation, array $params, string $namespace = ''): string
    {
        $inner = self::buildParams($params);

        $xmlns = $namespace !== '' ? ' xmlns="'.e($namespace).'"' : '';

        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            .'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            .'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            .'<soap:Body>'
            .'<'.$operation.$xmlns.'>'.$inner.'</'.$operation.'>'
            .'</soap:Body>'
            .'</soap:Envelope>';
    }

    /**
     * ساخت تگ‌های داخلی؛ از آرایه‌های تودرتو (مثل requestData در پارسیان) پشتیبانی می‌کند.
     *
     * @param  array<string, mixed>  $params
     */
    private static function buildParams(array $params): string
    {
        $xml = '';

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $xml .= '<'.$key.'>'.self::buildParams($value).'</'.$key.'>';
            } else {
                $xml .= '<'.$key.'>'.self::escape($value).'</'.$key.'>';
            }
        }

        return $xml;
    }

    private static function escape(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * پاسخ SOAP را به آرایهٔ ساده تبدیل می‌کند.
     *
     * @return array<string, mixed>
     */
    public static function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            return ['_raw' => $xml];
        }

        $document->registerXPathNamespace('soap11', 'http://schemas.xmlsoap.org/soap/envelope/');
        $document->registerXPathNamespace('soap12', 'http://www.w3.org/2003/05/soap-envelope');

        $bodies = $document->xpath('//soap11:Body') ?: $document->xpath('//soap12:Body');

        if (empty($bodies)) {
            return ['_raw' => $xml];
        }

        $children = $bodies[0]->xpath('./*');

        if (empty($children)) {
            return ['_raw' => $xml];
        }

        $root = $children[0];

        // خطای SOAP
        if (str_contains($root->getName(), 'Fault')) {
            $fault = [];

            foreach ($root->xpath('./*') as $child) {
                $fault[$child->getName()] = trim((string) $child);
            }

            return ['_fault' => $fault, '_raw' => $xml];
        }

        $result = [];
        $inner = $root->xpath('./*');

        // حالت اول: پاسخ از چند فیلد تشکیل شده (مثل SalePaymentRequestResult)
        foreach ($inner as $child) {
            $result[$child->getName()] = trim((string) $child);
        }

        // حالت دوم: پاسخ یک رشتهٔ ساده است (مثل bpPayRequestResult)
        if ($result === []) {
            $result['_value'] = trim((string) $root);
        }

        $result['_raw'] = $xml;

        return $result;
    }
}
