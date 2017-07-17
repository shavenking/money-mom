<?php

namespace App\Http\Controllers;

use App\Guesser\TransactionAmountNotFound;
use App\Guesser\TransactionTypeNotFound;
use App\PendingMessage;
use App\Platform;
use App\PlatformFactory;
use App\Transaction;
use App\Guesser\TransactionAmountGuesser;
use App\TransactionType;
use App\TransactionTypeFactory;
use App\Guesser\TransactionTypeGuesser;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        try {
            $this->validate($request, [
                'message.chat.id' => 'required',
                'message.message_id' => 'required',
                'message.text' => 'required',
                'message.date' => 'required',
                'message.from.id' => 'required'
            ]);
        } catch (ValidationException $e) {
            if (
                1 === count($e->validator->failed())
                && array_has($e->validator->failed(), 'message.text')
            ) {
                return response()->json([
                    'method' => 'sendMessage',
                    'chat_id' => $request->input('message.chat.id'),
                    'reply_to_message_id' => $request->input('message.message_id'),
                    'text' => view('please-input-correct-format')->render()
                ]);
            }

            throw $e;
        }

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

        // check if there is any command in message
        if ($request->has('message.entities')) {
            $this->validate($request, [
                'message.entities.*.type' => 'required',
                'message.entities.*.offset' => 'required',
                'message.entities.*.length' => 'required'
            ]);

            $startCommand = collect($request->input('message.entities'))
                ->filter(function (array $entity) {
                    return 'bot_command' === $entity['type'];
                })
                ->first(function (array $entity) use ($request) {
                    return '/start' ===
                        mb_convert_encoding(
                            mb_strcut(
                                mb_convert_encoding($request->input('message.text'), 'UTF-16'),
                                $entity['offset'] * 2,
                                $entity['length'] * 2,
                                'UTF-16'
                            ),
                            'UTF-8',
                            'UTF-16'
                        );
                });

            if (!empty($startCommand)) {
                return response()->json([
                    'method' => 'sendMessage',
                    'chat_id' => $request->input('message.chat.id'),
                    'reply_to_message_id' => $request->input('message.message_id'),
                    'text' => view('introduction')->render()
                ]);
            }
        }

        try {
            /** @var TransactionType $transactionType */
            $transactionType = $transactionTypeGuesser->guess($request->input('message.text'));
            $balance = $amount = $transactionAmountGuesser->guess($request->input('message.text'));
        } catch (ClientException $e) {
            PendingMessage::create([
                'user_id' => $user->id,
                'platform_id' => $telegram->id,
                'content' => json_encode($request->all())
            ]);

            return response()->json([
                'method' => 'sendMessage',
                'chat_id' => $request->input('message.chat.id'),
                'reply_to_message_id' => $request->input('message.message_id'),
                'text' => view('message-queued')->render()
            ]);
        } catch (TransactionTypeNotFound $e) {
            Log::info(json_encode($request->all()));

            return response()->json([
                'method' => 'sendMessage',
                'chat_id' => $request->input('message.chat.id'),
                'reply_to_message_id' => $request->input('message.message_id'),
                'text' => view('transaction-type-not-found')->render()
            ]);
        } catch (TransactionAmountNotFound $e) {
            Log::info(json_encode($request->all()));

            return response()->json([
                'method' => 'sendMessage',
                'chat_id' => $request->input('message.chat.id'),
                'reply_to_message_id' => $request->input('message.message_id'),
                'text' => view('transaction-amount-not-found')->render()
            ]);
        }

        $latest = $transaction->lockForUpdate()->whereUserId($user->id)->latest()->first(); // todo: use redis instead?

        if ($latest && $transactionTypeFactory->isIncome($transactionType)) {
            $balance = bcadd($latest->balance, $amount, 2);
        }

        if ($latest && $transactionTypeFactory->isExpense($transactionType)) {
            $balance = bcsub($latest->balance, $amount, 2);
        }

        /** @var Transaction $transaction */
        $transaction = $transactionType->transactions()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'balance' => $balance,
            'created_at' => $request->input('message.date'),
            'updated_at' => $request->input('message.date')
        ]);

        $transaction->refresh();

        return response()->json([
            'method' => 'sendMessage',
            'chat_id' => $request->input('message.chat.id'),
            'reply_to_message_id' => $request->input('message.message_id'),
            'text' => view('transaction', compact('transactionType', 'transaction'))->render()
        ]);
    }
}
