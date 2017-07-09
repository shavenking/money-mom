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
}
