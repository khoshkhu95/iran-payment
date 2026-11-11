<?php

namespace Taha20\IranPayment\Tests\Feature;

use Taha20\IranPayment\Enums\TransactionStatus;
use Taha20\IranPayment\Models\Transaction;
use Taha20\IranPayment\Support\PaymentToken;
use Taha20\IranPayment\Tests\TestCase;

class DatabaseTransactionTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // در این تست مسیر دیتابیس را بررسی می‌کنیم
        $app['config']->set('iran-payment.transactions.enabled', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // اجرای مایگریشن واقعی پکیج برای اطمینان از درستی ساختار جدول
        $migration = require __DIR__.'/../../database/migrations/create_iran_payments_table.php.stub';
        $migration->up();
    }

    public function test_transaction_is_persisted_and_verified(): void
    {
        $token = PaymentToken::make(['amount' => 75_000, 'order_id' => 'ORD-DB-1']);

        $response = $this->post(route('iran-payment.pay'), ['token' => $token, 'gateway' => 'fake']);
        $this->get($response->headers->get('Location'));

        $transaction = Transaction::query()->where('order_id', 'ORD-DB-1')->first();

        $this->assertNotNull($transaction);
        $this->assertSame(75_000, (int) $transaction->amount);
        $this->assertSame(TransactionStatus::Paid, $transaction->status);
        $this->assertNotNull($transaction->paid_at);
        $this->assertNotNull($transaction->tracking_code);
    }

    public function test_successful_scope_only_returns_paid_transactions(): void
    {
        Transaction::query()->create([
            'uuid' => 'a-uuid',
            'gateway' => 'fake',
            'order_id' => 'ORD-A',
            'amount' => 1000,
            'status' => TransactionStatus::Paid,
        ]);

        Transaction::query()->create([
            'uuid' => 'b-uuid',
            'gateway' => 'fake',
            'order_id' => 'ORD-B',
            'amount' => 2000,
            'status' => TransactionStatus::Failed,
        ]);

        $this->assertCount(1, Transaction::query()->successful()->get());
        $this->assertCount(0, Transaction::query()->pending()->get());
    }
}
