<?php

namespace Tests\Unit;

use App\Guesser\TransactionAmountGuesser;
use App\Guesser\TransactionAmountNotFound;
use Lib\NaturalLanguageProcessor\Token;
use Mockery;
use Tests\TestCase;

class GuesserTest extends TestCase
{
    public function testAmountNotFound()
    {
        $this->expectException(TransactionAmountNotFound::class);

        $mock = Mockery::mock(NaturalLanguageProcessor::class)
            ->shouldReceive('getTokens')
            ->andReturn(
                new Token('711。', 'NUM', '711。')
            )
            ->getMock();

        app()->instance(NaturalLanguageProcessor::class, $mock);

        app(TransactionAmountGuesser::class)->guess('711。 收入');
    }
}
