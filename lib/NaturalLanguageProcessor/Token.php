<?php

namespace Lib\NaturalLanguageProcessor;

class Token
{
    private $text;

    private $tag;

    private $lemma;

    private $meta;

    public function __construct(string $text, string $tag, string $lemma, array $meta = [])
    {
        $this->text = $text;
        $this->tag = $tag;
        $this->lemma = $lemma;
        $this->meta = $meta;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function isNumber(): bool
    {
        return 0 === strcasecmp('NUM', $this->tag);
    }

    public function isNoun(): bool
    {
        return 0 === strcasecmp('NOUN', $this->tag);
    }

    public function getLemma(): string
    {
        return $this->lemma;
    }

    public function getMeta($meta)
    {
        return $this->meta[$meta];
    }

    public function __toString()
    {
        return $this->getText();
    }
}
