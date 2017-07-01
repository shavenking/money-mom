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
            ->post('/api/webhooks/telegram/' . env('TELEGRAM_KEY'))
            ->assertStatus(400);

        $this
            ->post(
                '/api/webhooks/telegram/' . env('TELEGRAM_KEY'),
                array_except($telegramUpdate, 'message.from.id')
            )
            ->assertStatus(400);

        $this
            ->post(
                '/api/webhooks/telegram/' . env('TELEGRAM_KEY'),
                array_except($telegramUpdate, 'message.date')
            )
            ->assertStatus(400);

        $this
            ->post(
                '/api/webhooks/telegram/' . env('TELEGRAM_KEY'),
                array_except($telegramUpdate, 'message.text')
            )
            ->assertStatus(400);
    }
}
