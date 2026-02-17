@extends(theme('layouts.dashboard_master'))
@section('title'){{ Settings('site_title') ? Settings('site_title') : 'LMS' }} | {{ __('Account Subscription') }} @endsection
@section('css')
    <style>
        /* Override theme checkout grid so title + plans stack in one column (theme uses 58% 42% grid) */
        .account-subscription-page .checkout_wrapper.payment_area {
            display: block;
            overflow: visible;
        }
        .account-subscription-page .section-tittle h3 { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; }
        .account-subscription-page .section-tittle p { color: #6b7280; margin-bottom: 0; }
        .account-subscription-page .col-item { display: flex; margin-bottom: 0; }
        .account-subscription-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
            transition: box-shadow .2s ease, border-color .2s ease;
            overflow: hidden;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .account-subscription-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0,0,0,.12), 0 4px 10px -2px rgba(0,0,0,.06);
            border-color: #d1d5db;
        }
        .account-subscription-card .card-head {
            background: linear-gradient(135deg, var(--system_primery_color, #e6365e) 0%, var(--system_primery_gredient1, #f64153) 100%);
            color: #fff;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 1.125rem;
            flex-shrink: 0;
        }
        .account-subscription-card .card-body-custom {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .account-subscription-card .plan-desc {
            color: #6b7280;
            font-size: 0.9375rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            flex: 1;
            min-height: 0;
        }
        .account-subscription-card .plan-price-block {
            margin-bottom: 0.75rem;
        }
        .account-subscription-card .plan-price-block .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
        }
        .account-subscription-card .plan-validity {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1.25rem;
        }
        .account-subscription-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff !important;
            background: linear-gradient(135deg, #635bff 0%, #5851ea 100%) !important;
            border: none !important;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity .2s ease, box-shadow .2s ease;
            min-height: 48px;
        }
        .account-subscription-submit:hover {
            opacity: 0.95;
            color: #fff !important;
            box-shadow: 0 4px 14px rgba(99, 91, 255, .4);
        }
        .account-subscription-page .empty-state {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 2.5rem;
            text-align: center;
            color: #6b7280;
        }
    </style>
@endsection
@section('js') @endsection

@section('mainContent')
    <div class="main_content_iner main_content_padding account-subscription-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="checkout_wrapper payment_area">
                        <div class="section-tittle mb-4">
                            <h3>{{ __('Account Subscription') }}</h3>
                            <p>{{ __('You need an active subscription to access the platform.') }}</p>
                        </div>

                        <div class="row g-4">
                            @forelse($plans as $plan)
                                <div class="col-12 col-sm-6 col-lg-4 col-item">
                                    <div class="account-subscription-card">
                                        <!-- The plan name should be in black color to ensure high readability and accessibility for users, as black text on a light background provides strong contrast and is a common design convention for important headings. -->
                                        <div class="card-head" style="color: #000;">{{ $plan->name }}</div>
                                        <div class="card-body-custom">
                                            @if($plan->description)
                                                <p class="plan-desc">{{ $plan->description }}</p>
                                            @endif
                                            <div class="plan-price-block">
                                                <span class="price">{{ getPriceFormat($plan->price) }} {{ $currency }}</span>
                                            </div>
                                            <p class="plan-validity">{{ $plan->duration_days }} {{ __('days') }} {{ __('validity') }}</p>
                                            @if($plan->stripe_price_id)
                                                <a href="{{ route('accountSubscriptionCheckout', $plan) }}" class="account-subscription-submit theme-btn text-decoration-none d-inline-flex align-items-center justify-content-center w-100">
                                                    {{ __('Subscribe with Stripe (auto-renew)') }}
                                                </a>
                                            @else
                                                <form class="account-subscription-form" action="{{ route('accountSubscriptionPay') }}" method="post"
                                                      data-amount="{{ (int) round($plan->price * 100) }}"
                                                      data-currency="{{ strtolower($currency) }}"
                                                      data-name="{{ Settings('site_title') ?? 'Account Subscription' }}"
                                                      data-description="{{ $plan->name }}">
                                                    @csrf
                                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                                    <input type="hidden" name="amount" value="{{ $plan->price }}">
                                                    <input type="hidden" name="stripeToken" value="">
                                                    <button type="submit" class="account-subscription-submit theme-btn">
                                                        {{ __('Pay with Stripe') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="empty-state">
                                        <p class="mb-0">{{ __('No subscription plans are available. Please contact the administrator.') }}</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://checkout.stripe.com/checkout.js"></script>
    <script>
        (function() {
            var stripeKey = '{{ getPaymentEnv("STRIPE_KEY") }}';
            var stripeImage = '{{ asset(Settings("favicon")) }}';
            if (!stripeKey) return;

            document.querySelectorAll('.account-subscription-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var amount = parseInt(form.dataset.amount, 10) || 0;
                    var currency = (form.dataset.currency || 'usd').toLowerCase();
                    var name = form.dataset.name || 'Account Subscription';
                    var description = form.dataset.description || '';

                    var handler = StripeCheckout.configure({
                        key: stripeKey,
                        image: stripeImage,
                        locale: 'auto',
                        token: function(token) {
                            form.querySelector('input[name="stripeToken"]').value = token.id;
                            form.submit();
                        }
                    });

                    handler.open({
                        name: name,
                        description: description,
                        amount: amount,
                        currency: currency
                    });
                });
            });
        })();
    </script>
@endsection
