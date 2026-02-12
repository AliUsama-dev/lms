<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountSubscriptionPlan extends Model
{
    protected $table = 'account_subscription_plans';

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'status',
        'order',
    ];

    protected $casts = [
        'status' => 'boolean',
        'price' => 'float',
        'duration_days' => 'integer',
        'order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function subscriptions()
    {
        return $this->hasMany(AccountSubscription::class, 'plan_id');
    }
}

