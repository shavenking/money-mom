<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test app can accept request from Telegram
     */
    public function testAppCanAcceptRequestFromTelegram()
    {
        $this->post('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123123123',
                'from' => ['id' => '129291239', 'first_name' => 'shavenking'],
                'date' => '1497874643',
                'chat' => ['id' => '94879487', 'type' => 'private'],
                'text' => 'income 30'
            ]
        ])->assertStatus(200);
    }
}
