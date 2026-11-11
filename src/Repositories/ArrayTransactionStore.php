<?php

namespace Taha20\IranPayment\Repositories;

use Taha20\IranPayment\Contracts\TransactionStore;
use Taha20\IranPayment\Models\Transaction;

/**
 * ذخیره‌سازی در حافظه - بدون دیتابیس.
 *
 * کاربردها: تست‌های خودکار، پروژه‌های بدون دیتابیس، و حالت stateless که
 * می‌خواهید هیچ ردی از تراکنش در برنامه نماند (مسئولیت پیگیری با خود درگاه).
 */
class ArrayTransactionStore implements TransactionStore
{
    /** @var array<string, Transaction> */
    protected array $transactions = [];

    public function create(array $attributes): Transaction
    {
        $transaction = new Transaction;
        $transaction->forceFill($attributes);

        $this->transactions[$transaction->uuid] = $transaction;

        return $transaction;
    }

    public function update(Transaction $transaction, array $attributes): Transaction
    {
        $transaction->forceFill($attributes);

        $this->transactions[$transaction->uuid] = $transaction;

        return $transaction;
    }

    public function findByUuid(string $uuid): ?Transaction
    {
        return $this->transactions[$uuid] ?? null;
    }

    public function findByOrderId(string $orderId, ?string $gateway = null): ?Transaction
    {
        $matches = array_filter(
            $this->transactions,
            fn (Transaction $transaction) => $transaction->order_id === $orderId
                && ($gateway === null || $transaction->gateway === $gateway)
        );

        return $matches === [] ? null : end($matches);
    }

    /** @return array<string, Transaction> */
    public function all(): array
    {
        return $this->transactions;
    }
}
