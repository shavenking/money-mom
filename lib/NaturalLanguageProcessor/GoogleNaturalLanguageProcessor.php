<?php

namespace Lib\NaturalLanguageProcessor;

use GuzzleHttp\Client;

class GoogleNaturalLanguageProcessor implements NaturalLanguageProcessor
{
    protected $caches;

    private $key;

    private $client;

    public function __construct(Client $client, string $key)
    {
        $this->caches = [];
        $this->key = $key;
        $this->client = $client;
    }

    public function getTokens(string $text): array
    {
        $this->process($text);

        return $this->caches[$text];
    }

    public function process(string $text): NaturalLanguageProcessor
    {
        if ($this->hasCache($text)) {
            return $this;
        }

        $response = $this->client->post(
            "https://language.googleapis.com/v1beta2/documents:analyzeSyntax?key={$this->key}",
            [
                'json' => [
                    'document' => [
                        'type' => 'PLAIN_TEXT',
                        'content' => $text
                    ]
                ]
            ]
        );

        $body = json_decode($response->getBody(), true);
        $tokens = array_get($body, 'tokens', []);

        $this->caches[$text] = array_map(function ($token) {
            return new Token(
                array_get($token, 'text.content', ''),
                array_get($token, 'partOfSpeech.tag'),
                array_get($token, 'lemma')
            );
        }, $tokens);

        return $this;
    }

    protected function hasNoCache(string $text): bool
    {
        return !$this->hasCache($text);
    }

    protected function hasCache(string $text): bool
    {
        return array_key_exists($text, $this->caches);
    }
}
