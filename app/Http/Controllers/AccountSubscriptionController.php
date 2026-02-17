<?php

namespace App\Http\Controllers;

use App\AccountSubscription;
use App\AccountSubscriptionPlan;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Omnipay\Omnipay;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook;

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
     * Start Stripe Checkout (subscription mode) for a plan that has stripe_price_id. Redirects to Stripe.
     */
    public function checkout(AccountSubscriptionPlan $plan)
    {
        if (! $plan->stripe_price_id) {
            Toastr::error(__('This plan does not support automatic renewal. Use Pay with Stripe below.'), trans('common.Error'));
            return redirect()->route('accountSubscription');
        }

        $plan->loadMissing([]);
        if (! $plan->status) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
            return redirect()->route('accountSubscription');
        }

        $secret = getPaymentEnv('STRIPE_SECRET');
        if (empty($secret)) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
            return redirect()->route('accountSubscription');
        }

        try {
            Stripe::setApiKey($secret);

            $user = Auth::user();
            $customerId = $user->stripe_customer_id ?? null;

            $params = [
                'mode' => 'subscription',
                'line_items' => [
                    [
                        'price' => $plan->stripe_price_id,
                        'quantity' => 1,
                    ],
                ],
                'success_url' => route('accountSubscriptionSuccess', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('accountSubscription', [], true),
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'plan_id' => (string) $plan->id,
                ],
            ];

            if ($customerId) {
                $params['customer'] = $customerId;
            } else {
                $params['customer_email'] = $user->email;
            }

            $session = \Stripe\Checkout\Session::create($params);

            return redirect()->away($session->url);
        } catch (Exception $e) {
            GettingError($e->getMessage(), url()->current(), request()->ip(), request()->userAgent());
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
            return redirect()->route('accountSubscription');
        }
    }

    /**
     * Handle redirect from Stripe Checkout (subscription). Create local subscription and set validity.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (empty($sessionId)) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
            return redirect()->route('accountSubscription');
        }

        $secret = getPaymentEnv('STRIPE_SECRET');
        if (empty($secret)) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
            return redirect()->route('accountSubscription');
        }

        try {
            Stripe::setApiKey($secret);
            $session = StripeSession::retrieve($sessionId, ['expand' => ['subscription']]);

            if ($session->mode !== 'subscription' || ! $session->subscription) {
                Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
                return redirect()->route('accountSubscription');
            }

            $userId = (int) ($session->metadata->user_id ?? 0);
            $planId = (int) ($session->metadata->plan_id ?? 0);
            if ($userId !== Auth::id() || $planId < 1) {
                Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
                return redirect()->route('accountSubscription');
            }

            $user = Auth::user();
            $subscription = $session->subscription;
            if ($subscription instanceof \Stripe\Subscription) {
                $stripeSub = $subscription;
            } else {
                $stripeSub = StripeSubscription::retrieve($subscription, ['expand' => ['plan']]);
            }

            $plan = AccountSubscriptionPlan::find($planId);
            if (! $plan) {
                Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
                return redirect()->route('accountSubscription');
            }

            $periodEnd = $stripeSub->current_period_end ?? null;
            $periodStart = $stripeSub->current_period_start ?? null;
            if ($periodEnd !== null && $periodStart !== null) {
                $endsAt = \Carbon\Carbon::createFromTimestamp($periodEnd);
                $startsAt = \Carbon\Carbon::createFromTimestamp($periodStart);
            } else {
                $startsAt = \Carbon\Carbon::now();
                $endsAt = $startsAt->copy()->addDays(max(1, $plan->duration_days));
            }

            $user->subscription_validity_date = $endsAt->toDateString();
            if (! empty($session->customer) && empty($user->stripe_customer_id)) {
                $user->stripe_customer_id = $session->customer;
            }
            $user->save();

            $amount = (float) ($stripeSub->plan->amount ?? $plan->price);
            if (isset($stripeSub->plan->currency)) {
                $amount = $amount / 100;
            }

            AccountSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount_paid' => $amount,
                'payment_method' => 'Stripe',
                'stripe_subscription_id' => $stripeSub->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
            ]);

            Toastr::success(trans('frontend.Payment done successfully'), trans('common.Success'));
            return redirect()->route('studentDashboard');
        } catch (Exception $e) {
            GettingError($e->getMessage(), url()->current(), request()->ip(), request()->userAgent());
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
            return redirect()->route('accountSubscription');
        }
    }

    /**
     * Process Stripe one-time payment (for plans without stripe_price_id). Sets subscription_validity_date on success.
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

        $plans = null;
        $currency = Settings('currency_code') ?? 'USD';
        if (! $currentSubscription) {
            $plans = AccountSubscriptionPlan::active()->orderBy('order')->get();
        }

        return view(theme('pages.my_subscription'), compact('currentSubscription', 'user', 'plans', 'currency'));
    }

    /**
     * Allow user to cancel current subscription at period end. If Stripe subscription exists, set cancel_at_period_end.
     */
    public function cancel()
    {
        $user = Auth::user();

        $subscription = AccountSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->orderByDesc('ends_at')
            ->first();

        if (! $subscription) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
            return redirect()->route('mySubscription');
        }

        if (! empty($subscription->stripe_subscription_id)) {
            $secret = getPaymentEnv('STRIPE_SECRET');
            if (! empty($secret)) {
                try {
                    Stripe::setApiKey($secret);
                    $stripeSub = StripeSubscription::retrieve($subscription->stripe_subscription_id);
                    $stripeSub->cancel_at_period_end = true;
                    $stripeSub->save();
                } catch (Exception $e) {
                    GettingError($e->getMessage(), url()->current(), request()->ip(), request()->userAgent());
                }
            }
        }

        $subscription->status = 'cancelled';
        $subscription->cancelled_at = now();
        $subscription->save();

        Toastr::success(trans('frontend.Operation successful'), trans('common.Success'));

        return redirect()->route('mySubscription');
    }

    /**
     * Stripe webhook for subscription lifecycle: invoice.paid (renewal), customer.subscription.updated, customer.subscription.deleted.
     */
    public function webhook(Request $request)
    {
        $secret = getPaymentEnv('STRIPE_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (empty($secret)) {
            \Log::warning('Account subscription: STRIPE_WEBHOOK_SECRET not set');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            Stripe::setApiKey(getPaymentEnv('STRIPE_SECRET'));
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            switch ($event->type) {
                case 'invoice.paid':
                    $invoice = $event->data->object;
                    $subId = $invoice->subscription ?? null;
                    if ($subId) {
                        $this->handleSubscriptionRenewal($subId, $invoice);
                    }
                    break;
                case 'customer.subscription.updated':
                    $stripeSub = $event->data->object;
                    $this->handleSubscriptionUpdated($stripeSub);
                    break;
                case 'customer.subscription.deleted':
                    $stripeSub = $event->data->object;
                    $this->handleSubscriptionDeleted($stripeSub);
                    break;
            }
        } catch (Exception $e) {
            GettingError($e->getMessage(), url()->current(), $request->ip(), $request->userAgent());
            return response()->json(['error' => 'Handler error'], 500);
        }

        return response()->json(['received' => true]);
    }

    protected function handleSubscriptionRenewal($stripeSubscriptionId, $invoice): void
    {
        $local = AccountSubscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        if (! $local) {
            return;
        }
        $user = $local->user;
        if (! $user) {
            return;
        }
        $periodEnd = $invoice->lines->data[0]->period->end ?? null;
        if ($periodEnd) {
            $endsAt = \Carbon\Carbon::createFromTimestamp($periodEnd);
            $user->subscription_validity_date = $endsAt->toDateString();
            $user->save();
            $local->ends_at = $endsAt;
            $local->save();
        }
    }

    protected function handleSubscriptionUpdated($stripeSub): void
    {
        $local = AccountSubscription::where('stripe_subscription_id', $stripeSub->id)->first();
        if (! $local || ! $local->user) {
            return;
        }
        $periodEnd = $stripeSub->current_period_end ?? null;
        if ($periodEnd) {
            $endsAt = \Carbon\Carbon::createFromTimestamp($periodEnd);
            $local->ends_at = $endsAt;
            $local->save();
            $local->user->subscription_validity_date = $endsAt->toDateString();
            $local->user->save();
        }
        if (! empty($stripeSub->cancel_at_period_end) && $local->status === 'active') {
            $local->status = 'cancelled';
            $local->cancelled_at = now();
            $local->save();
        }
    }

    protected function handleSubscriptionDeleted($stripeSub): void
    {
        $local = AccountSubscription::where('stripe_subscription_id', $stripeSub->id)->first();
        if (! $local) {
            return;
        }
        $local->status = 'expired';
        $local->save();

        $user = $local->user;
        if ($user) {
            $user->subscription_validity_date = null;
            $user->save();
        }
    }
}
