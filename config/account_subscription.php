<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Account subscription amount (in system currency)
    |--------------------------------------------------------------------------
    */
    'amount' => env('ACCOUNT_SUBSCRIPTION_AMOUNT', 10),

    /*
    |--------------------------------------------------------------------------
    | Subscription validity in days after successful payment
    |--------------------------------------------------------------------------
    */
    'duration_days' => env('ACCOUNT_SUBSCRIPTION_DURATION_DAYS', 30),
];
