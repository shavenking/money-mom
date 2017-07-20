<?php

namespace App\Guesser;

use Lib\NaturalLanguageProcessor\NaturalLanguageProcessor;

class TagGuesser
{
    protected $processor;

    public function __construct(NaturalLanguageProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function guess(string $text): array
    {
        $tokens = $this->processor->getTokens($text);

        $tags = [];

        foreach ($tokens as $token) {
            if ($token->isNoun() && !in_array($token->getText(), ['收入', '支出'], true)) {
                $tags[] = $token->getText();
            }
        }

        return $tags;
    }
}
