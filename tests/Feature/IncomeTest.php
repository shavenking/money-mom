<?php

namespace Tests\Feature;

use App\Transaction;
use App\TransactionTypeFactory;
use Carbon\Carbon;
use TelegramFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IncomeTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test user can create income via Telegram
     */
    public function testUserCanCreateIncomeViaTelegram()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '38443 收入'])
        ]);

        $this
            ->post('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200);

        $income = app(TransactionTypeFactory::class)->getIncome();
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $this->assertDatabaseHas(
            (new Transaction)->getTable(),
            [
                'user_id' => array_get($telegramUpdate, 'message.from.id'),
                'transaction_type_id' => $income->id,
                'amount' => '38443.00',
                'balance' => '38443.00',
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]
        );
    }
}
