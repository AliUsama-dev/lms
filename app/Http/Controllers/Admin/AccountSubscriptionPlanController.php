<?php

namespace App\Http\Controllers\Admin;

use App\AccountSubscriptionPlan;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Http\Request;

class AccountSubscriptionPlanController extends Controller
{
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
            AccountSubscriptionPlan::create([
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

