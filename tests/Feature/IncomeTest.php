<?php

namespace Tests\Feature;

use App\PlatformFactory;
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
     * when it's first time reach our service
     */
    public function testUserCanCreateIncomeViaTelegramWhenFirstTimeReachOurService()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '38443.12 收入'])
        ]);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200);

        $user = app(PlatformFactory::class)
            ->getTelegram()
            ->usersByPlatformUserId(array_get($telegramUpdate, 'message.from.id'))
            ->firstOrFail();

        $income = app(TransactionTypeFactory::class)->getIncome();
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $this->assertDatabaseHas(
            (new Transaction)->getTable(),
            [
                'user_id' => $user->id,
                'transaction_type_id' => $income->id,
                'amount' => '38443.12',
                'balance' => '38443.12',
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]
        );
    }

    /**
     * Test user can create income via Telegram
     */
    public function testUserCanCreateIncomeViaTelegram()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message' => $telegramFactory->makeMessage(['text' => '38443.12 收入'])
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

        $income = app(TransactionTypeFactory::class)->getIncome();
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $this->assertDatabaseHas(
            (new Transaction)->getTable(),
            [
                'user_id' => $user->id,
                'transaction_type_id' => $income->id,
                'amount' => '38443.12',
                'balance' => '38443.12',
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]
        );
    }
}
