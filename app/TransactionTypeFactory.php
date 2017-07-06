<?php

namespace App;

class TransactionTypeFactory
{
    protected $supportedTypes = [
        'INCOME' => 'INCOME',
        'EXPENSE' => 'EXPENSE'
    ];

    protected $model;

    public function __construct(TransactionType $model)
    {
        $this->model = $model;
    }

    public function getIncome(): TransactionType
    {
        return $this
            ->model
            ->newQuery()
            ->whereName($this->supportedTypes['INCOME'])
            ->firstOrFail();
    }

    public function getExpense(): TransactionType
    {
        return $this
            ->model
            ->newQuery()
            ->whereName($this->supportedTypes['EXPENSE'])
            ->firstOrFail();
    }

    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    public function make($type): TransactionType
    {
        if (!isset($this->supportedTypes[$type])) {
            throw new \Exception;
        }

        return $this->model->newInstance([
            'name' => $this->supportedTypes[$type]
        ]);
    }

    public function isIncome(TransactionType $transactionType)
    {
        return $this->supportedTypes['INCOME'] === $transactionType->name;
    }

    public function isExpense(TransactionType $transactionType)
    {
        return $this->supportedTypes['EXPENSE'] === $transactionType->name;
    }
}
