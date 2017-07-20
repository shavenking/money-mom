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
            'message.text' => '38443.12 收入 薪水 Money Mom 年終'
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

        $transaction = Transaction::where([
            'user_id' => $user->id,
            'transaction_type_id' => $income->id,
            'amount' => '38443.12',
            'balance' => '38443.12',
            'created_at' => $messageDate,
            'updated_at' => $messageDate
        ])->firstOrFail();

        $this->assertSame(
            2,
            $transaction->tags()->whereIn('slug', ['薪水', '年終'])->count()
        );
    }

    /**
     * Test user can create income via Telegram
     */
    public function testUserCanCreateIncomeViaTelegram()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '38443.12 收入'
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

    /**
     * Test balance is calculate correctly
     */
    public function testBalanceIsCalculateCorrectly()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '9487.56 收入'
        ]);

        $platformUserId = array_get($telegramUpdate, 'message.from.id');
        $user = app(PlatformFactory::class)->getTelegram()->users()->create([
            'name' => "TG-$platformUserId",
            'email' => "EMAIL-$platformUserId",
            'password' => ''
        ], ['platform_user_id' => $platformUserId]);

        /** @var TransactionType $income */
        $income = app(TransactionTypeFactory::class)->getIncome();

        /** @var Carbon $messageDate */
        $messageDate = app(Carbon::class)->createFromTimestamp(array_get($telegramUpdate, 'message.date'));

        $yesterday = $messageDate->copy()->subDay();
        $income->transactions()->create([
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
                'transaction_type_id' => $income->id,
                'amount' => '9487.56',
                'balance' => bcadd('9487.56', '17.01', 2),
                'created_at' => $messageDate,
                'updated_at' => $messageDate
            ]
        );
    }
}
