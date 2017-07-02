<?php

namespace App\Http\Controllers;

use App\Platform;
use App\PlatformFactory;
use App\TransactionAmountGuesser;
use App\TransactionType;
use App\TransactionTypeGuesser;
use Illuminate\Http\Request;
use Lib\NaturalLanguageProcessor\NaturalLanguageProcessor;
use Lib\NaturalLanguageProcessor\Token;

class WebhookController extends Controller
{
    public function telegram(Request $request)
    {
        $this->validate($request, [
            'message.text' => 'required',
            'message.date' => 'required',
            'message.from.id' => 'required'
        ]);

        $platformUserId = $request->input('message.from.id');

        /** @var Platform $telegram */
        $telegram = app(PlatformFactory::class)->getTelegram();

        $user = $telegram->createIfNotExist(
            $platformUserId,
            [
                'name' => "TG-$platformUserId",
                'email' => "EMAIL-$platformUserId",
                'password' => ''
            ],
            ['platform_user_id' => $platformUserId]
        );

        /** @var TransactionType $transactionType */
        $transactionType = app(TransactionTypeGuesser::class)->guess($request->input('message.text'));
        $amount = app(TransactionAmountGuesser::class)->guess($request->input('message.text'));

        $transactionType->transactions()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'balance' => $amount,
            'created_at' => $request->input('message.date'),
            'updated_at' => $request->input('message.date')
        ]);

        return response()->json();
    }
}
