<?php

namespace Taha20\IranPayment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Taha20\IranPayment\PaymentManager manager()
 * @method static \Taha20\IranPayment\PaymentProcessor processor()
 * @method static \Taha20\IranPayment\Contracts\Gateway gateway(?string $name = null)
 * @method static array<string, array<string, mixed>> gateways()
 * @method static \Taha20\IranPayment\IranPayment use(string $gateway)
 * @method static \Taha20\IranPayment\Support\PaymentResponse pay(\Taha20\IranPayment\Support\Payment $payment, ?string $gateway = null)
 * @method static \Taha20\IranPayment\Models\Transaction callback(string $gateway, array<string, mixed> $payload, ?string $uuid = null)
 * @method static \Taha20\IranPayment\Support\RefundResult refund(\Taha20\IranPayment\Models\Transaction $transaction, ?int $amount = null)
 * @method static \Taha20\IranPayment\Contracts\TransactionStore store()
 * @method static \Taha20\IranPayment\Gateways\FakeGateway fake(string $outcome = 'success')
 *
 * @see \Taha20\IranPayment\IranPayment
 */
class IranPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Taha20\IranPayment\IranPayment::class;
    }
}
