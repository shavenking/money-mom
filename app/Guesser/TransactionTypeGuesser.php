<?php

namespace App\Guesser;

use App\TransactionType;
use App\TransactionTypeFactory;
use Lib\NaturalLanguageProcessor\NaturalLanguageProcessor;

class TransactionTypeGuesser
{
    protected $factory;

    protected $processor;

    public function __construct(TransactionTypeFactory $factory, NaturalLanguageProcessor $processor)
    {
        $this->factory = $factory;
        $this->processor = $processor;
    }

    public function guess(string $text): TransactionType
    {
        $tokens = $this->processor->getTokens($text);

        foreach ($tokens as $token) {
            if (
                $token->isNoun()
                && 0 === strcasecmp('收入', $token->getText())
            ) {
                return $this->factory->getIncome();
            }

            if (
                $token->isNoun()
                && 0 === strcasecmp('支出', $token->getText())
            ) {
                return $this->factory->getExpense();
            }
        }

        throw new TransactionTypeNotFound;
    }
}
