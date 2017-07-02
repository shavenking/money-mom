<?php

Route::post('webhooks/telegram/' . env('TELEGRAM_KEY'), 'WebhookController@telegram');
