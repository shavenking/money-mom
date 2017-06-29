<?php

namespace Tests\Feature;

use App\Transaction;
use App\TransactionTypeFactory;
use Carbon\Carbon;
use TelegramFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExpenseTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserCanCreateExpenseViaTelegram()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '9487 支出'])
        ]);

        $this
            ->post('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200);

        $expense = app(TransactionTypeFactory::class)->getExpense();
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $this->assertDatabaseHas(
            (new Transaction)->getTable(),
            [
                'user_id' => array_get($telegramUpdate, 'message.from.id'),
                'transaction_type_id' => $expense->id,
                'amount' => '9487.00',
                'balance' => '9487.00',
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]
        );
    }
}
