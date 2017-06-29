<?php

use Illuminate\Http\Request;

Route::post('webhooks/telegram/' . env('TELEGRAM_KEY'), function (Request $request) {
    $platformUserId = $request->input('message.from.id');

    /** @var \App\Platform $telegram */
    $telegram = app(\App\PlatformFactory::class)->getTelegram();

    if ($telegram->hasNoUser($platformUserId)) {
        $telegram->users()->create([
            'name' => "TG-$platformUserId",
            'email' => "EMAIL-$platformUserId",
            'password' => ''
        ], ['platform_user_id' => $platformUserId]);
    }

    $matches = [];

    if (!mb_ereg('[0-9]+', $request->input('message.text'), $matches)) {
        abort(400);
    }

    /** @var \App\TransactionType $income */
    $income = app(\App\TransactionTypeGuesser::class)->guess($request->input('message.text'));

    $income->transactions()->create([
        'user_id' => $request->input('message.from.id'),
        'amount' => $matches[0],
        'balance' => $matches[0],
        'created_at' => $request->input('message.date'),
        'updated_at' => $request->input('message.date')
    ]);

    return response()->json();
});
