<?php

namespace App;

class TransactionTypeGuesser
{
    protected $factory;

    public function __construct(TransactionTypeFactory $factory)
    {
        $this->factory = $factory;
    }

    public function guess(string $text): TransactionType
    {
        if (false !== mb_strpos($text, '收入')) {
            return $this->factory->getIncome();
        }

        if (false !== mb_strpos($text, '支出')) {
            return $this->factory->getExpense();
        }

        throw new \Exception;
    }
}
