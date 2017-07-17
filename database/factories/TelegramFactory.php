<?php

use Faker\Generator;

class TelegramFactory
{
    protected $faker;

    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    public function makeUpdate(array $override = []): array
    {
        $update = [
            'updated_id' => $this->faker->randomNumber(),
            'message' => $this->makeMessage()
        ];

        return $this->override($update, $override);
    }

    public function makeUpdateWithCommand(array $override = []): array
    {
        $update = $this->makeUpdate($override);

        return $this->addCommandEntities($update);
    }

    public function makeMessage(array $override = []): array
    {
        $message = [
            'message_id' => $this->faker->randomNumber(),
            'from' => $this->makeUser(),
            'date' => $this->faker->unixTime,
            'chat' => $this->makeChat(),
            'text' => 'income 30'
        ];

        return $this->override($message, $override);
    }

    public function makeUser(array $override = []): array
    {
        $user = [
            'id' => $this->faker->randomNumber(),
            'first_name' => $this->faker->name
        ];

        return $this->override($user, $override);
    }

    public function makeChat(array $override = []): array
    {
        $chat = [
            'id' => $this->faker->randomNumber(),
            'type' => 'private'
        ];

        return $this->override($chat, $override);
    }

    protected function override(array $base, array $override = []): array
    {
        foreach ($override as $key => $value) {
            array_set($base, $key, $value);
        }

        return $base;
    }

    protected function addCommandEntities(array $update): array
    {
        $text = array_get($update, 'message.text');

        $originalInternalEncoding = mb_internal_encoding();
        $originalRegexEncoding = mb_regex_encoding();

        mb_internal_encoding('UTF-16');
        mb_regex_encoding('UTF-16');

        mb_ereg_search_init(
            mb_convert_encoding($text, 'UTF-16', 'UTF-8'),
            mb_convert_encoding('\/[A-z]+', 'UTF-16', 'UTF-8')
        );

        while (list($offset, $length) = mb_ereg_search_pos()) {
            $entities[] = [
                'type' => 'bot_command',
                'offset' => $offset / 2,
                'length' => $length / 2
            ];
        }

        mb_internal_encoding($originalInternalEncoding);
        mb_regex_encoding($originalRegexEncoding);

        if (empty($entities)) {
            return $update;
        }

        array_set($update, 'message.entities', $entities);

        return $update;
    }
}
