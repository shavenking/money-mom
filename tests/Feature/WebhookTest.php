<?php

namespace Tests\Feature;

use TelegramFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WebhookTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Telegram webhook API will validate request
     */
    public function testTelegramWebhookAPIWillValidateRequest()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '38443 收入'])
        ]);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'))
            ->assertStatus(422);

        $this
            ->postJson(
                '/api/webhooks/telegram/' . env('TELEGRAM_KEY'),
                array_except($telegramUpdate, 'message.chat.id')
            )
            ->assertStatus(422);

        $this
            ->postJson(
                '/api/webhooks/telegram/' . env('TELEGRAM_KEY'),
                array_except($telegramUpdate, 'message.message_id')
            )
            ->assertStatus(422);

        $this
            ->postJson(
                '/api/webhooks/telegram/' . env('TELEGRAM_KEY'),
                array_except($telegramUpdate, 'message.from.id')
            )
            ->assertStatus(422);

        $this
            ->postJson(
                '/api/webhooks/telegram/' . env('TELEGRAM_KEY'),
                array_except($telegramUpdate, 'message.date')
            )
            ->assertStatus(422);

        $this
            ->postJson(
                '/api/webhooks/telegram/' . env('TELEGRAM_KEY'),
                array_except($telegramUpdate, 'message.text')
            )
            ->assertStatus(422);
    }

    public function testTelegramWillRespondSendMessageToWebhookUpdate()
    {
        /** @var TelegramFactory $telegramFactory */
        $telegramFactory = app(TelegramFactory::class);

        $user = $telegramFactory->makeUser();

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '38443 收入'])
        ]);

        array_set($telegramUpdate, 'message.from', $user);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200)
            ->assertJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => 'INCOME 38443.00, BALANCE NOW: 38443.00'
            ]);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '79.12 支出'])
        ]);

        array_set($telegramUpdate, 'message.from', $user);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200)
            ->assertJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => 'EXPENSE 79.12, BALANCE NOW: 38363.88'
            ]);
    }
}
