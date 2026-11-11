<?php

namespace Taha20\IranPayment\Repositories;

use Taha20\IranPayment\Contracts\TransactionStore;
use Taha20\IranPayment\Models\Transaction;

/**
 * ذخیره‌سازی تراکنش‌ها روی دیتابیس (پیش‌فرض).
 */
class DatabaseTransactionStore implements TransactionStore
{
    /** @var class-string<Transaction> */
    protected string $model;

    /**
     * @param  class-string<Transaction>|null  $model
     */
    public function __construct(?string $model = null)
    {
        $this->model = $model ?: Transaction::class;
    }

    public function create(array $attributes): Transaction
    {
        return $this->model::query()->create($attributes);
    }

    public function update(Transaction $transaction, array $attributes): Transaction
    {
        $transaction->forceFill($attributes)->save();

        return $transaction;
    }

    public function findByUuid(string $uuid): ?Transaction
    {
        return $this->model::query()->where('uuid', $uuid)->first();
    }

    public function findByOrderId(string $orderId, ?string $gateway = null): ?Transaction
    {
        return $this->model::query()
            ->where('order_id', $orderId)
            ->when($gateway, fn ($query) => $query->where('gateway', $gateway))
            ->latest('id')
            ->first();
    }
}
