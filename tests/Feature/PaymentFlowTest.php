<?php

namespace Taha20\IranPayment\Tests\Feature;

use Taha20\IranPayment\Contracts\TransactionStore;
use Taha20\IranPayment\Facades\IranPayment;
use Taha20\IranPayment\Repositories\ArrayTransactionStore;
use Taha20\IranPayment\Support\PaymentToken;
use Taha20\IranPayment\Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    public function test_component_lists_enabled_gateways(): void
    {
        $view = $this->blade('<x-iran-payment :amount="50000" order-id="ORD-1" />');

        $view->assertSee('درگاه پرداخت را انتخاب کنید');
        $view->assertSee('name="gateway"', false);
        $view->assertSee('value="fake"', false);
        $view->assertSee('۵,۰۰۰', false); // مبلغ به تومان و با ارقام فارسی
    }

    public function test_component_only_shows_requested_gateways(): void
    {
        $view = $this->blade('<x-iran-payment :amount="10000" order-id="ORD-2" :exclude="[\'fake\']" />');

        $view->assertSee('هیچ درگاه پرداختی فعال نیست');
    }

    public function test_full_payment_flow_with_fake_gateway(): void
    {
        $token = PaymentToken::make([
            'amount' => 50_000,
            'order_id' => 'ORD-100',
        ]);

        $response = $this->post(route('iran-payment.pay'), [
            'token' => $token,
            'gateway' => 'fake',
        ]);

        $response->assertRedirect();
        $gatewayUrl = $response->headers->get('Location');

        $this->assertStringContainsString('callback/fake', $gatewayUrl);

        $callback = $this->get($gatewayUrl);
        $callback->assertRedirect();

        $transaction = $this->app->make(TransactionStore::class)->findByOrderId('ORD-100');

        $this->assertNotNull($transaction);
        $this->assertTrue($transaction->isSuccessful());
        $this->assertSame(50_000, (int) $transaction->amount);
        $this->assertNotNull($transaction->tracking_code);

        $result = $this->get($callback->headers->get('Location'));
        $result->assertOk();
        $result->assertSee('پرداخت شده');
    }

    public function test_callback_is_idempotent(): void
    {
        $token = PaymentToken::make(['amount' => 20_000, 'order_id' => 'ORD-200']);

        $response = $this->post(route('iran-payment.pay'), ['token' => $token, 'gateway' => 'fake']);
        $gatewayUrl = $response->headers->get('Location');

        $this->get($gatewayUrl)->assertRedirect();
        $this->get($gatewayUrl)->assertRedirect();

        $store = $this->app->make(TransactionStore::class);

        $this->assertInstanceOf(ArrayTransactionStore::class, $store);
        $this->assertCount(1, $store->all());
    }

    public function test_failed_payment_is_recorded(): void
    {
        IranPayment::fake('failed');

        $token = PaymentToken::make(['amount' => 30_000, 'order_id' => 'ORD-300']);

        $response = $this->post(route('iran-payment.pay'), ['token' => $token, 'gateway' => 'fake']);
        $this->get($response->headers->get('Location'));

        $transaction = $this->app->make(TransactionStore::class)->findByOrderId('ORD-300');

        $this->assertNotNull($transaction);
        $this->assertFalse($transaction->isSuccessful());
    }

    public function test_tampered_token_is_rejected(): void
    {
        $response = $this->post(route('iran-payment.pay'), [
            'token' => 'not-a-valid-token',
            'gateway' => 'fake',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('iran_payment_error');
    }
}
