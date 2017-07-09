<?php

namespace Tests\Feature;

use App\PlatformFactory;
use App\Transaction;
use App\TransactionType;
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
            'message.text' => '9487.56 支出'
        ]);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
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
                'amount' => '9487.56',
                'balance' => '9487.56',
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]
        );
    }

    public function testUserCanCreateExpenseViaTelegram()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '9487.56 支出'
        ]);

        $platformUserId = array_get($telegramUpdate, 'message.from.id');
        $user = app(PlatformFactory::class)->getTelegram()->users()->create([
            'name' => "TG-$platformUserId",
            'email' => "EMAIL-$platformUserId",
            'password' => ''
        ], ['platform_user_id' => $platformUserId]);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200);

        $expense = app(TransactionTypeFactory::class)->getExpense();
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $this->assertDatabaseHas(
            (new Transaction)->getTable(),
            [
                'user_id' => $user->id,
                'transaction_type_id' => $expense->id,
                'amount' => '9487.56',
                'balance' => '9487.56',
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]
        );
    }

    /**
     * Test balance is calculate correctly
     */
    public function testBalanceIsCalculateCorrectly()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '9487.56 支出'
        ]);

        $platformUserId = array_get($telegramUpdate, 'message.from.id');
        $user = app(PlatformFactory::class)->getTelegram()->users()->create([
            'name' => "TG-$platformUserId",
            'email' => "EMAIL-$platformUserId",
            'password' => ''
        ], ['platform_user_id' => $platformUserId]);

        /** @var TransactionType $expense */
        $expense = app(TransactionTypeFactory::class)->getExpense();

        /** @var Carbon $messageDate */
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $yesterday = $messageDate->copy()->subDay();
        $expense->transactions()->create([
            'user_id' => $user->id,
            'amount' => '17.01',
            'balance' => '17.01',
            'created_at' => $yesterday,
            'updated_at' => $yesterday
        ]);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200);

        $this->assertDatabaseHas(
            (new Transaction)->getTable(),
            [
                'user_id' => $user->id,
                'transaction_type_id' => $expense->id,
                'amount' => '9487.56',
                'balance' => bcsub('17.01', '9487.56', 2),
                'created_at' => $messageDate,
                'updated_at' => $messageDate
            ]
        );
    }
}
