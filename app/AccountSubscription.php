<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountSubscription extends Model
{
    protected $table = 'account_subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'amount_paid',
        'payment_method',
        'starts_at',
        'ends_at',
        'status',
        'cancelled_at',
    ];

    protected $casts = [
        'amount_paid' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(AccountSubscriptionPlan::class, 'plan_id');
    }
}

