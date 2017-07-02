<?php

namespace Lib\NaturalLanguageProcessor;

interface NaturalLanguageProcessor
{
    public function getTokens(string $text): array;

    public function process(string $text): NaturalLanguageProcessor;
}
