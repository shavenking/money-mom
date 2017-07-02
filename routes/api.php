<?php

use Illuminate\Http\Request;

Route::post('webhooks/telegram/' . env('TELEGRAM_KEY'), function (Request $request) {
    $validator = Validator::make($request->all(), [
        'message.text' => 'required',
        'message.date' => 'required',
        'message.from.id' => 'required'
    ]);

    if ($validator->fails()) {
        abort(400);
    }

    $platformUserId = $request->input('message.from.id');

    /** @var \App\Platform $telegram */
    $telegram = app(\App\PlatformFactory::class)->getTelegram();

    if ($telegram->hasNoUser($platformUserId)) {
        $user = $telegram->users()->create([
            'name' => "TG-$platformUserId",
            'email' => "EMAIL-$platformUserId",
            'password' => ''
        ], ['platform_user_id' => $platformUserId]);
    } else {
        $user = $telegram->usersByPlatformUserId($platformUserId)->firstOrFail();
    }

    $tokens = app(\Lib\NaturalLanguageProcessor\NaturalLanguageProcessor::class)
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

    /** @var \App\TransactionType $transactionType */
    $transactionType = app(\App\TransactionTypeGuesser::class)->guess($request->input('message.text'));

    $transactionType->transactions()->create([
        'user_id' => $user->id,
        'amount' => $amount,
        'balance' => $amount,
        'created_at' => $request->input('message.date'),
        'updated_at' => $request->input('message.date')
    ]);

    return response()->json();
});
