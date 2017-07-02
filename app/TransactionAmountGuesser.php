<?php

namespace App;

use Lib\NaturalLanguageProcessor\NaturalLanguageProcessor;

class TransactionAmountGuesser
{
    protected $processor;

    public function __construct(NaturalLanguageProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function guess(string $text): string
    {
        $tokens = $this->processor->getTokens($text);

        $amount = array_first($tokens, function ($token) {
            return $token->isNumber();
        });

        if (empty($amount)) {
            throw new \Exception;
        }

        return $amount;
    }
}
