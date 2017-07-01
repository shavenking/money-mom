<?php

namespace Tests\Feature;

use App\PlatformFactory;
use App\Transaction;
use App\TransactionTypeFactory;
use Carbon\Carbon;
use TelegramFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExpenseTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserCanCreateExpenseViaTelegramWhenFirstTimeReachOurService()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '9487 支出'])
        ]);

        $this
            ->post('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200);

        $user = app(PlatformFactory::class)
            ->getTelegram()
            ->usersByPlatformUserId(array_get($telegramUpdate, 'message.from.id'))
            ->firstOrFail();

        $expense = app(TransactionTypeFactory::class)->getExpense();
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $this->assertDatabaseHas(
            (new Transaction)->getTable(),
            [
                'user_id' => $user->id,
                'transaction_type_id' => $expense->id,
                'amount' => '9487.00',
                'balance' => '9487.00',
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]
        );
    }

    public function testUserCanCreateExpenseViaTelegram()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '9487 支出'])
        ]);

        $platformUserId = array_get($telegramUpdate, 'message.from.id');
        $user = app(PlatformFactory::class)->getTelegram()->users()->create([
            'name' => "TG-$platformUserId",
            'email' => "EMAIL-$platformUserId",
            'password' => ''
        ], ['platform_user_id' => $platformUserId]);

        $this
            ->post('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200);

        $expense = app(TransactionTypeFactory::class)->getExpense();
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $this->assertDatabaseHas(
            (new Transaction)->getTable(),
            [
                'user_id' => $user->id,
                'transaction_type_id' => $expense->id,
                'amount' => '9487.00',
                'balance' => '9487.00',
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]
        );
    }
}
