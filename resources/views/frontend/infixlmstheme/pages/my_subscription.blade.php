@extends(theme('layouts.dashboard_master'))
@section('title'){{ Settings('site_title') ? Settings('site_title') : 'LMS' }} | {{ __('My Subscription') }} @endsection
@section('css') @endsection
@section('js') @endsection

@section('mainContent')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="checkout_wrapper payment_area">
                    <div class="section-tittle mb-4">
                        <h3>{{ __('My Subscription') }}</h3>
                    </div>

                    @if($currentSubscription)
                        <div class="biling_address gray-bg p-4 rounded mb-4">
                            <h4 class="mb-2">{{ $currentSubscription->plan->name ?? '' }}</h4>
                            @if($currentSubscription->plan && $currentSubscription->plan->description)
                                <p class="mb-2">{{ $currentSubscription->plan->description }}</p>
                            @endif
                            <p class="mb-1"><strong>{{ __('Price') }}:</strong>
                                {{ getPriceFormat($currentSubscription->amount_paid) }} {{ $user->currency->code ?? Settings('currency_code') }}</p>
                            <p class="mb-1"><strong>{{ __('Status') }}:</strong> {{ ucfirst($currentSubscription->status) }}</p>
                            <p class="mb-1"><strong>{{ __('Starts at') }}:</strong> {{ optional($currentSubscription->starts_at)->format('Y-m-d') }}</p>
                            <p class="mb-1"><strong>{{ __('Ends at') }}:</strong> {{ optional($currentSubscription->ends_at)->format('Y-m-d') }}</p>
                            @if($currentSubscription->status === 'active')
                                <form action="{{ route('mySubscription.cancel') }}" method="post" class="mt-3"
                                      onsubmit="return confirm('{{ __('Are you sure you want to cancel your subscription? You will keep access until the end date.') }}');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">
                                        {{ __('Cancel subscription') }}
                                    </button>
                                </form>
                            @elseif($currentSubscription->status === 'cancelled')
                                <p class="mt-3 text-warning">
                                    {{ __('Your subscription is cancelled and will end on :date.', ['date' => optional($currentSubscription->ends_at)->format('Y-m-d')]) }}
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="biling_address gray-bg p-4 rounded mb-4 text-center">
                            <p>{{ __('You do not have an active subscription.') }}</p>
                            <a href="{{ route('accountSubscription') }}" class="btn btn-primary mt-2">
                                {{ __('Go to subscription plans') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

