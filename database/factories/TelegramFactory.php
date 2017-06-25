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
        return array_merge([
            'update_id' => $this->faker->randomNumber(),
            'message' => $this->makeMessage()
        ], $override);
    }

    public function makeMessage(array $override = []): array
    {
        return array_merge([
            'message_id' => $this->faker->randomNumber(),
            'from' => $this->makeUser(),
            'date' => $this->faker->unixTime,
            'chat' => $this->makeChat(),
            'text' => 'income 30'
        ], $override);
    }

    public function makeUser(array $override = []): array
    {
        return array_merge([
            'id' => $this->faker->randomNumber(),
            'first_name' => $this->faker->name
        ], $override);
    }

    public function makeChat(array $override = []): array
    {
        return array_merge([
            'id' => $this->faker->randomNumber(),
            'type' => 'private'
        ], $override);
    }
}
