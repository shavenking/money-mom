<?php

namespace App\Http\Controllers;

use App\Platform;
use App\PlatformFactory;
use App\TransactionType;
use App\TransactionTypeGuesser;
use Illuminate\Http\Request;
use Lib\NaturalLanguageProcessor\NaturalLanguageProcessor;

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

        if ($telegram->hasNoUser($platformUserId)) {
            $user = $telegram->users()->create([
                'name' => "TG-$platformUserId",
                'email' => "EMAIL-$platformUserId",
                'password' => ''
            ], ['platform_user_id' => $platformUserId]);
        } else {
            $user = $telegram->usersByPlatformUserId($platformUserId)->firstOrFail();
        }

        $tokens = app(NaturalLanguageProcessor::class)
            ->getTokens($request->input('message.text'));

        $amount = '';

        foreach ($tokens as $token) {
            if ($token->isNumber()) {
                $amount = $token->getText();
                break;
            }
        }

        if (empty($amount)) {
            abort(400);
        }

        /** @var TransactionType $transactionType */
        $transactionType = app(TransactionTypeGuesser::class)->guess($request->input('message.text'));

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
