<?php

namespace App\Http\Controllers\Admin;

use App\AccountSubscriptionPlan;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class AccountSubscriptionPlanController extends Controller
{
    /**
     * Create Stripe Product and recurring Price for a plan.
     * Returns ['product_id' => ..., 'price_id' => ...] on success, or ['error' => 'message'] on failure.
     */
    protected function createStripeProductAndPrice(string $name, ?string $description, float $price, int $durationDays): array
    {
        $secret = getPaymentEnv('STRIPE_SECRET');
        if (empty($secret)) {
            return ['error' => __('Stripe Secret Key is not configured. Set it in Payment Method settings or in .env as STRIPE_SECRET.')];
        }

        $currency = strtolower(Settings('currency_code') ?? 'usd');
        // Stripe unit_amount is in smallest currency unit (cents for USD). Zero-decimal currencies (e.g. JPY) use whole numbers.
        $zeroDecimal = in_array($currency, ['jpy', 'krw', 'vnd', 'clp', 'pyg'], true);
        $unitAmount = $zeroDecimal ? (int) round($price) : (int) round($price * 100);

        try {
            Stripe::setApiKey($secret);

            $product = Product::create([
                'name' => $name,
                'description' => $description ?? '',
            ]);

            $priceObj = Price::create([
                'product' => $product->id,
                'unit_amount' => $unitAmount,
                'currency' => $currency,
                'recurring' => [
                    'interval' => 'day',
                    'interval_count' => max(1, $durationDays),
                ],
            ]);

            return ['product_id' => $product->id, 'price_id' => $priceObj->id];
        } catch (Exception $e) {
            report($e);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create a recurring Price for an existing Stripe product.
     * Returns price id string or null on failure.
     */
    protected function createStripePriceForProduct(string $productId, float $price, int $durationDays): ?string
    {
        $secret = getPaymentEnv('STRIPE_SECRET');
        if (empty($secret)) {
            return null;
        }

        $currency = strtolower(Settings('currency_code') ?? 'usd');
        $zeroDecimal = in_array($currency, ['jpy', 'krw', 'vnd', 'clp', 'pyg'], true);
        $unitAmount = $zeroDecimal ? (int) round($price) : (int) round($price * 100);

        try {
            Stripe::setApiKey($secret);
            $priceObj = Price::create([
                'product' => $productId,
                'unit_amount' => $unitAmount,
                'currency' => $currency,
                'recurring' => [
                    'interval' => 'day',
                    'interval_count' => max(1, $durationDays),
                ],
            ]);
            return $priceObj->id;
        } catch (Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Update Stripe Product and optionally create a new Price if amount or interval changed.
     * If plan has no Stripe IDs yet, creates Product + Price.
     * Returns error message string if Stripe sync failed, null otherwise.
     */
    protected function updateStripeForPlan(AccountSubscriptionPlan $plan, Request $request): ?string
    {
        $secret = getPaymentEnv('STRIPE_SECRET');
        if (empty($secret)) {
            return __('Stripe Secret Key is not configured. Set it in Payment Method settings or in .env as STRIPE_SECRET.');
        }

        try {
            Stripe::setApiKey($secret);

            if (!$plan->stripe_product_id) {
                $result = $this->createStripeProductAndPrice(
                    $request->name,
                    $request->description,
                    (float) $request->price,
                    (int) $request->duration_days
                );
                if (isset($result['error'])) {
                    return $result['error'];
                }
                $plan->stripe_product_id = $result['product_id'];
                $plan->stripe_price_id = $result['price_id'];
                $plan->save();
                return null;
            }

            Product::update($plan->stripe_product_id, [
                'name' => $request->name,
                'description' => $request->description ?? '',
            ]);

            $priceChanged = (float) $plan->price !== (float) $request->price;
            $durationChanged = (int) $plan->duration_days !== (int) $request->duration_days;
            if ($priceChanged || $durationChanged) {
                $newPriceId = $this->createStripePriceForProduct(
                    $plan->stripe_product_id,
                    (float) $request->price,
                    (int) $request->duration_days
                );
                if ($newPriceId) {
                    $plan->stripe_price_id = $newPriceId;
                    $plan->save();
                }
            }
            return null;
        } catch (Exception $e) {
            report($e);
            return $e->getMessage();
        }
    }
    public function index()
    {
        $plans = AccountSubscriptionPlan::orderBy('order')->get();
        return view('backend.account_subscription.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('backend.account_subscription.plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'status' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        try {
            $stripeProductId = null;
            $stripePriceId = null;
            $stripeResult = $this->createStripeProductAndPrice(
                $request->name,
                $request->description,
                (float) $request->price,
                (int) $request->duration_days
            );
            if (isset($stripeResult['error'])) {
                Toastr::warning(__('Stripe:') . ' ' . $stripeResult['error'], trans('common.Warning'));
            } elseif (isset($stripeResult['product_id'], $stripeResult['price_id'])) {
                $stripeProductId = $stripeResult['product_id'];
                $stripePriceId = $stripeResult['price_id'];
            }

            AccountSubscriptionPlan::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'duration_days' => $request->duration_days,
                'stripe_product_id' => $stripeProductId,
                'stripe_price_id' => $stripePriceId,
                'status' => $request->boolean('status'),
                'order' => $request->input('order', 0),
            ]);
            Toastr::success(trans('common.Operation successful'), trans('common.Success'));
        } catch (Exception $e) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
        }

        return redirect()->route('admin.account-subscription.plans.index');
    }

    public function edit(AccountSubscriptionPlan $plan)
    {
        return view('backend.account_subscription.plans.edit', compact('plan'));
    }

    public function update(Request $request, AccountSubscriptionPlan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'status' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        try {
            $stripeError = $this->updateStripeForPlan($plan, $request);
            if ($stripeError) {
                Toastr::warning(__('Stripe:') . ' ' . $stripeError, trans('common.Warning'));
            }

            $plan->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'duration_days' => $request->duration_days,
                'status' => $request->boolean('status'),
                'order' => $request->input('order', 0),
            ]);
            Toastr::success(trans('common.Operation successful'), trans('common.Success'));
        } catch (Exception $e) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
        }

        return redirect()->route('admin.account-subscription.plans.index');
    }

    public function destroy(AccountSubscriptionPlan $plan)
    {
        try {
            $plan->delete();
            Toastr::success(trans('common.Operation successful'), trans('common.Success'));
        } catch (Exception $e) {
            Toastr::error(trans('frontend.Something Went Wrong'), trans('common.Error'));
        }

        return redirect()->route('admin.account-subscription.plans.index');
    }
}

