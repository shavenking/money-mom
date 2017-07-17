<?php

namespace Tests\Feature;

use App\PendingMessage;
use App\PlatformFactory;
use App\Transaction;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Lib\NaturalLanguageProcessor\NaturalLanguageProcessor;
use Lib\NaturalLanguageProcessor\Token;
use Mockery;
use TelegramFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TelegramWebhookTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Telegram webhook API will validate request
     */
    public function testValidateRequest()
    {
        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'))
            ->assertStatus(422);

        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '38443 收入'
        ]);

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
            ->assertStatus(200)
            ->assertExactJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => view('please-input-correct-format')->render()
            ]);

    }

    public function testRespondSendMessageToWebhookUpdate()
    {
        /** @var TelegramFactory $telegramFactory */
        $telegramFactory = app(TelegramFactory::class);

        $user = $telegramFactory->makeUser();

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '38443 收入',
            'message.from' => $user
        ]);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200)
            ->assertJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => 'INCOME: 38443.00' . PHP_EOL . 'BALANCE NOW: 38443.00' . PHP_EOL
            ]);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '79.12 支出',
            'message.from' => $user
        ]);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200)
            ->assertJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => 'EXPENSE: 79.12' . PHP_EOL . 'BALANCE NOW: 38363.88' . PHP_EOL
            ]);
    }

    public function testDelayProcessingWhenNLPIsUnavailable()
    {
        $telegramUpdate = app(TelegramFactory::class)->makeUpdate();

        $mock = Mockery::mock(NaturalLanguageProcessor::class)
            ->shouldReceive('getTokens')
            ->andThrow(Mockery::mock(ClientException::class))
            ->getMock();

        app()->instance(NaturalLanguageProcessor::class, $mock);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200)
            ->assertExactJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => '我們收到您的訊息了，但是目前系統繁忙，我們會盡快處理。' . PHP_EOL
            ]);

        $user = app(PlatformFactory::class)->getTelegram()->usersByPlatformUserId(
            array_get($telegramUpdate, 'message.from.id')
        )->firstOrFail();

        $this->assertDatabaseHas(
            (new PendingMessage)->getTable(),
            [
                'user_id' => $user->id,
                'platform_id' => app(PlatformFactory::class)->getTelegram()->id,
                'content' => json_encode($telegramUpdate)
            ]
        );
    }

    public function testWhenMessageHasNoTransactionTypeTokenWillRespondOkAndLogMessage()
    {
        /** @var TelegramFactory $telegramFactory */
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '38443'
        ]);

        $mock = Mockery::mock(NaturalLanguageProcessor::class)
            ->shouldReceive('getTokens')
            ->andReturn([])
            ->getMock();

        app()->instance(NaturalLanguageProcessor::class, $mock);

        Log::spy();

        $response = $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200)
            ->assertExactJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => '格式錯誤，請至少輸入「收入」或「支出」。' . PHP_EOL
            ]);

        Log::shouldHaveReceived('info')
            ->with(json_encode($telegramUpdate))
            ->once();

        Log::shouldHaveReceived('info')
            ->with($response->baseResponse)
            ->once();

        $user = app(PlatformFactory::class)->getTelegram()->usersByPlatformUserId(
            array_get($telegramUpdate, 'message.from.id')
        )->firstOrFail();

        $this->assertDatabaseMissing(
            (new Transaction)->getTable(),
            [
                'user_id' => $user->id
            ]
        );
    }

    public function testWhenMessageHasNoTransactionAmountTokenWillRespondOkAndLogMessage()
    {
        /** @var TelegramFactory $telegramFactory */
        $telegramFactory = app(TelegramFactory::class);

        $telegramUpdate = $telegramFactory->makeUpdate([
            'message.text' => '收入'
        ]);

        $mock = Mockery::mock(NaturalLanguageProcessor::class)
            ->shouldReceive('getTokens')
            ->andReturn([
                new Token('收入', 'NOUN', '收入')
            ])
            ->getMock();

        app()->instance(NaturalLanguageProcessor::class, $mock);

        Log::spy();

        $response = $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200)
            ->assertExactJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => '格式錯誤，請輸入金額。' . PHP_EOL
            ]);

        Log::shouldHaveReceived('info')
            ->with(json_encode($telegramUpdate))
            ->once();

        Log::shouldHaveReceived('info')
            ->with($response->baseResponse)
            ->once();

        $user = app(PlatformFactory::class)->getTelegram()->usersByPlatformUserId(
            array_get($telegramUpdate, 'message.from.id')
        )->firstOrFail();

        $this->assertDatabaseMissing(
            (new Transaction)->getTable(),
            [
                'user_id' => $user->id
            ]
        );
    }

    public function testTelegramWillRespondToStartCommand()
    {
        $telegramUpdate = app(TelegramFactory::class)->makeUpdateWithCommand([
            'message.text' => '🚀/awesome π哈€哈 🚀/start'
        ]);

        $this
            ->postJson('/api/webhooks/telegram/' . env('TELEGRAM_KEY'), $telegramUpdate)
            ->assertStatus(200)
            ->assertExactJson([
                'method' => 'sendMessage',
                'chat_id' => array_get($telegramUpdate, 'message.chat.id'),
                'reply_to_message_id' => array_get($telegramUpdate, 'message.message_id'),
                'text' => view('introduction')->render()
            ]);

        $this->assertTrue(
            app(PlatformFactory::class)->getTelegram()->hasUser(
                array_get($telegramUpdate, 'message.from.id')
            ),
            'User should be created'
        );
    }
}
