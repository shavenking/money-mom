<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('webhooks/telegram/' . env('TELEGRAM_KEY'), function (Request $request) {
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
