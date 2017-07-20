{{ $transactionType->name }}: {{ $transaction->amount }}
BALANCE NOW: {{ $transaction->balance }}
@if (!empty($tags))
    {{ $tags }}
@endif
