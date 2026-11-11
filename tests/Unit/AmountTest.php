<?php

namespace Taha20\IranPayment\Tests\Unit;

use Taha20\IranPayment\Exceptions\InvalidPaymentException;
use Taha20\IranPayment\Support\Amount;
use Taha20\IranPayment\Support\Payment;
use Taha20\IranPayment\Tests\TestCase;

class AmountTest extends TestCase
{
    public function test_it_normalizes_toman_to_rial(): void
    {
        $this->assertSame(500_000, Amount::toRial(50_000, 'IRT'));
        $this->assertSame(50_000, Amount::toRial(50_000, 'IRR'));
    }

    public function test_payment_uses_configured_unit_by_default(): void
    {
        config()->set('iran-payment.currency', 'IRT');

        $payment = Payment::make(12_000, 'ORD-1');

        $this->assertSame(120_000, $payment->amount());
        $this->assertSame(12_000, $payment->amountInToman());
    }

    public function test_explicit_unit_overrides_config(): void
    {
        config()->set('iran-payment.currency', 'IRT');

        $payment = Payment::make(120_000, 'ORD-1', 'IRR');

        $this->assertSame(120_000, $payment->amount());
    }

    public function test_it_rejects_non_positive_amount(): void
    {
        $this->expectException(InvalidPaymentException::class);

        Payment::make(0, 'ORD-1');
    }

    public function test_it_formats_amount_with_persian_digits(): void
    {
        $this->assertSame('۱۲,۵۰۰ تومان', Amount::format(125_000));
    }
}
