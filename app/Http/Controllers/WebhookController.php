<?php

namespace App\Http\Controllers;

use App\Platform;
use App\PlatformFactory;
use App\Transaction;
use App\TransactionAmountGuesser;
use App\TransactionType;
use App\TransactionTypeFactory;
use App\TransactionTypeGuesser;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function telegram(
        Request $request,
        PlatformFactory $platformFactory,
        TransactionTypeGuesser $transactionTypeGuesser,
        TransactionAmountGuesser $transactionAmountGuesser,
        Transaction $transaction,
        TransactionTypeFactory $transactionTypeFactory
    ) {
        $this->validate($request, [
            'message.text' => 'required',
            'message.date' => 'required',
            'message.from.id' => 'required'
        ]);

        $platformUserId = $request->input('message.from.id');

        /** @var Platform $telegram */
        $telegram = $platformFactory->getTelegram();

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
        $transactionType = $transactionTypeGuesser->guess($request->input('message.text'));
        $latest = $transaction->lockForUpdate()->whereUserId($user->id)->latest()->first(); // todo: use redis instead?
        $balance = $amount = $transactionAmountGuesser->guess($request->input('message.text'));

        if ($latest && $transactionTypeFactory->isIncome($transactionType)) {
            $balance = bcadd($latest->balance, $amount, 2);
        }

        if ($latest && $transactionTypeFactory->isExpense($transactionType)) {
            $balance = bcsub($latest->balance, $amount, 2);
        }

        $transactionType->transactions()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'balance' => $balance,
            'created_at' => $request->input('message.date'),
            'updated_at' => $request->input('message.date')
        ]);

        return response()->json();
    }
}
