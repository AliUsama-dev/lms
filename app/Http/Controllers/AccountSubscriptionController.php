<?php

namespace App\Http\Controllers;

use App\AccountSubscription;
use App\AccountSubscriptionPlan;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Omnipay\Omnipay;

class AccountSubscriptionController extends Controller
{
    /**
     * Show the account subscription page (compulsory for users without valid subscription).
     */
    public function index()
    {
        if (isAccountSubscribed()) {
            return redirect()->route('studentDashboard');
        }
        $currency = Settings('currency_code') ?? 'USD';
        $plans = AccountSubscriptionPlan::active()->orderBy('order')->get();

        return view(theme('pages.account_subscription'), compact('plans', 'currency'));
    }

    /**
     * Process Stripe payment for account subscription and set subscription_validity_date on success.
     */
    public function pay(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:account_subscription_plans,id',
            'stripeToken' => 'required|string',
        ]);

        $plan = AccountSubscriptionPlan::active()->findOrFail($request->plan_id);

        $amount = (float) $plan->price;
        $durationDays = (int) $plan->duration_days;
        $currency = Settings('currency_code') ?? 'USD';

        if (Settings('hide_multicurrency') == 1 && Auth::check()) {
            $amount = (float) number_format(
                convertCurrency(
                    Auth::user()->currency->code ?? $currency,
                    $currency,
                    $amount
                ),
                2
            );
        }

        try {
            $gateway = Omnipay::create('Stripe');
            $gateway->setApiKey(getPaymentEnv('STRIPE_SECRET'));

            $response = $gateway->purchase([
                'amount'   => $amount,
                'currency' => $currency,
                'token'    => $request->stripeToken,
            ])->send();

            if ($response->isSuccessful()) {
                $user = Auth::user();
                $current = $user->subscription_validity_date && \Carbon\Carbon::parse($user->subscription_validity_date)->isFuture()
                    ? \Carbon\Carbon::parse($user->subscription_validity_date)
                    : now();
                $startsAt = $current->copy();
                $endsAt = $current->addDays($durationDays);
                $user->subscription_validity_date = $endsAt->toDateString();
                $user->save();

                AccountSubscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount_paid' => $amount,
                    'payment_method' => 'Stripe',
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'active',
                ]);

                Toastr::success(trans('frontend.Payment done successfully'), trans('common.Success'));
                return redirect()->route('studentDashboard');
            }

            if ($response->getCode() === 'amount_too_small') {
                $minAmount = round(convertCurrency($currency, strtoupper($currency), 0.5));
                $message = __('Amount must be at least :symbol :amount', [
                    'symbol' => Settings('currency_symbol') ?? '$',
                    'amount' => $minAmount,
                ]);
                Toastr::error($message, trans('common.Error'));
            } else {
                Toastr::error($response->getMessage() ?: trans('frontend.Something Went Wrong'), trans('common.Error'));
            }
        } catch (Exception $e) {
            GettingError($e->getMessage(), url()->current(), request()->ip(), request()->userAgent());
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
        }

        return redirect()->route('accountSubscription');
    }

    /**
     * Show current subscription details for the logged-in user.
     */
    public function mySubscription()
    {
        $user = Auth::user();

        $currentSubscription = AccountSubscription::with('plan')
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'cancelled'])
            ->where('ends_at', '>=', now())
            ->orderByDesc('ends_at')
            ->first();

        return view(theme('pages.my_subscription'), compact('currentSubscription', 'user'));
    }

    /**
     * Allow user to cancel current subscription (at period end).
     */
    public function cancel()
    {
        $user = Auth::user();

        $subscription = AccountSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->orderByDesc('ends_at')
            ->first();

        if (!$subscription) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
            return redirect()->route('mySubscription');
        }

        $subscription->status = 'cancelled';
        $subscription->cancelled_at = now();
        $subscription->save();

        Toastr::success(trans('frontend.Operation successful'), trans('common.Success'));

        return redirect()->route('mySubscription');
    }
}
