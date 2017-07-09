<?php

namespace Tests\Feature;

use App\Platform;
use App\PlatformFactory;
use App\User;
use TelegramFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test it will create user when it is the first time
     * user reach our service.
     */
    public function testItWillAutoCreateUserAtFirstTime()
    {
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '38443 收入'
        ]);

        /** @var Platform $telegram */
        $telegram = app(PlatformFactory::class)->getTelegram();
        $this->assertInstanceOf(Platform::class, $telegram);

        $platformUserId = array_get($telegramUpdate, 'message.from.id');

        $this->assertTrue($telegram->hasNoUser($platformUserId));

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200);

        $this->assertTrue($telegram->hasUser($platformUserId));
    }
}
