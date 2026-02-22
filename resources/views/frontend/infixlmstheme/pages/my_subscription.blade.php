@extends(theme('layouts.dashboard_master'))
@section('title'){{ Settings('site_title') ? Settings('site_title') : 'LMS' }} | {{ __('My Subscription') }} @endsection
@section('css')
    <style>
        .my-subscription-page .checkout_wrapper.payment_area { display: block; overflow: visible; }
        .my-subscription-page .page-header {
            margin-bottom: 2rem;
        }
        .my-subscription-page .page-header h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }
        .my-subscription-page .page-header .subtitle {
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 0;
        }
        .my-subscription-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,.06), 0 2px 4px -2px rgba(0,0,0,.04);
            overflow: hidden;
            transition: box-shadow .25s ease, border-color .25s ease;
        }
        .my-subscription-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0,0,0,.08), 0 8px 10px -6px rgba(0,0,0,.04);
            border-color: #d1d5db;
        }
        .my-subscription-card .card-head {
            background: linear-gradient(135deg, var(--system_primery_color, #e6365e) 0%, var(--system_primery_gredient1, #f64153) 100%);
            color: #fff;
            padding: 1.5rem 1.75rem;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.01em;
        }
        .my-subscription-card .card-body-custom {
            padding: 1.75rem;
            background: linear-gradient(180deg, #fafafa 0%, #fff 12%);
        }
        .my-subscription-card .plan-desc {
            color: #6b7280;
            font-size: 0.9375rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .my-subscription-card .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0 2rem;
        }
        @media (max-width: 575px) {
            .my-subscription-card .details-grid { grid-template-columns: 1fr; }
        }
        .my-subscription-card .detail-item {
            display: flex;
            flex-direction: column;
            padding: 1rem 0;
            border-bottom: 1px solid #f3f4f6;
            gap: 0.25rem;
        }
        .my-subscription-card .detail-item:nth-last-child(-n+2) { border-bottom: none; }
        @media (max-width: 575px) {
            .my-subscription-card .detail-item:nth-last-child(-n+2) { border-bottom: 1px solid #f3f4f6; }
            .my-subscription-card .detail-item:last-child { border-bottom: none; }
        }
        .my-subscription-card .detail-label {
            color: #9ca3af;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .my-subscription-card .detail-value {
            color: #111827;
            font-size: 1rem;
            font-weight: 600;
        }
        .my-subscription-card .detail-value.price { font-size: 1.125rem; color: #059669; }
        .my-subscription-card .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.35rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 600;
            width: fit-content;
        }
        .my-subscription-card .status-badge::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }
        .my-subscription-card .status-badge.active {
            background: #ecfdf5;
            color: #047857;
            box-shadow: 0 0 0 1px rgba(5, 150, 105, 0.2);
        }
        .my-subscription-card .status-badge.cancelled {
            background: #fef2f2;
            color: #b91c1c;
            box-shadow: 0 0 0 1px rgba(185, 28, 28, 0.15);
        }
        .my-subscription-card .status-badge.expired {
            background: #f9fafb;
            color: #6b7280;
            box-shadow: 0 0 0 1px rgba(107, 114, 128, 0.2);
        }
        .my-subscription-card .days-remaining {
            margin-top: 1.25rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 10px;
            border: 1px solid #bfdbfe;
            font-size: 0.9375rem;
            color: #1e40af;
            font-weight: 600;
        }
        .my-subscription-card .cancel-section {
            margin-top: 1.75rem;
            padding: 1.5rem;
            background: #fef2f2;
            border-radius: 12px;
            border: 1px solid #fecaca;
        }
        .my-subscription-card .cancel-section .cancel-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 0.5rem;
        }
        .my-subscription-card .cancel-section .cancel-desc {
            font-size: 0.8125rem;
            color: #b91c1c;
            opacity: 0.9;
            margin-bottom: 1rem;
        }
        .my-subscription-card .btn-cancel-subscription {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 12px 24px;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(180deg, #dc2626 0%, #b91c1c 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,.05), 0 0 0 1px rgba(185, 28, 28, 0.2);
            transition: transform .15s ease, box-shadow .2s ease;
        }
        .my-subscription-card .btn-cancel-subscription:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35);
        }
        .my-subscription-card .cancelled-notice {
            margin-top: 1.25rem;
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1px solid #fcd34d;
            border-radius: 12px;
            color: #92400e;
            font-size: 0.9375rem;
            font-weight: 500;
        }
        .my-subscription-page .empty-state {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,.06);
        }
        .my-subscription-page .empty-state .empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #9ca3af;
        }
        .my-subscription-page .empty-state .empty-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .my-subscription-page .empty-state p {
            color: #6b7280;
            font-size: 0.9375rem;
            margin-bottom: 1.5rem;
        }
        .my-subscription-page .btn-view-plans {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 16px 32px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--system_primery_color, #e6365e) 0%, var(--system_primery_gredient1, #f64153) 100%);
            border: none;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(230, 54, 94, 0.35);
            transition: transform .15s ease, box-shadow .2s ease;
        }
        .my-subscription-page .btn-view-plans:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(230, 54, 94, 0.4);
            color: #fff;
        }
        .my-subscription-page .plans-choose-title { font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 1.25rem; }
        .my-subscription-page .plan-option-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .my-subscription-page .plan-option-card .card-head { color: #000; background: linear-gradient(135deg, var(--system_primery_color, #e6365e) 0%, var(--system_primery_gredient1, #f64153) 100%); color: #fff; padding: 1.25rem 1.5rem; font-weight: 600; font-size: 1.125rem; }
        .my-subscription-page .plan-option-card .card-body-custom { padding: 1.5rem; flex: 1; }
        .my-subscription-page .plan-option-card .plan-desc { color: #6b7280; font-size: 0.9375rem; margin-bottom: 1rem; }
        .my-subscription-page .plan-option-card .plan-price-block .price { font-size: 1.5rem; font-weight: 700; color: #111827; }
        .my-subscription-page .plan-option-card .plan-validity { font-size: 0.875rem; color: #6b7280; margin-bottom: 1.25rem; }
        .my-subscription-page .plan-option-card .btn-subscribe {
            display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: 14px 24px; font-size: 1rem; font-weight: 600; color: #fff !important;
            background: linear-gradient(135deg, #635bff 0%, #5851ea 100%) !important; border: none !important; border-radius: 8px; text-decoration: none; min-height: 48px;
        }
        .my-subscription-page .plan-option-card .btn-subscribe:hover { color: #fff !important; opacity: 0.95; }
        /* Cancel confirmation modal - professional */
        .cancel-confirm-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1.5rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .cancel-confirm-modal-backdrop.show {
            display: flex;
            opacity: 1;
        }
        .cancel-confirm-modal-backdrop.show .cancel-confirm-modal {
            transform: scale(1);
            opacity: 1;
        }
        .cancel-confirm-modal {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            transform: scale(0.95);
            opacity: 0;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }
        .cancel-confirm-modal .modal-icon-wrap {
            width: 56px;
            height: 56px;
            margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #dc2626;
            border: 2px solid #fecaca;
        }
        .cancel-confirm-modal .modal-header {
            padding: 0 1.75rem 0.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: #111827;
            text-align: center;
            letter-spacing: -0.02em;
        }
        .cancel-confirm-modal .modal-body {
            padding: 0 1.75rem 1.75rem;
            color: #64748b;
            font-size: 0.9375rem;
            line-height: 1.65;
            text-align: center;
        }
        .cancel-confirm-modal .modal-footer {
            padding: 1.25rem 1.75rem 1.5rem;
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .cancel-confirm-modal .btn-modal-cancel {
            padding: 12px 24px;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #475569;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: background .2s ease, border-color .2s ease, color .2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        .cancel-confirm-modal .btn-modal-cancel:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }
        .cancel-confirm-modal .btn-modal-confirm {
            padding: 12px 24px;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35);
            transition: transform .15s ease, box-shadow .2s ease;
        }
        .cancel-confirm-modal .btn-modal-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
        }
        .cancel-confirm-modal .modal-content-wrap {
            padding-top: 1.75rem;
        }
    </style>
@endsection
@section('js')
    <script>
        (function() {
            var form = document.querySelector('.js-cancel-subscription-form');
            var backdrop = document.getElementById('cancelConfirmModal');
            if (!form || !backdrop) return;
            var btnCancel = document.getElementById('cancelConfirmModalCancel');
            var btnConfirm = document.getElementById('cancelConfirmModalOk');
            var confirmed = false;

            form.addEventListener('submit', function(e) {
                if (confirmed) return;
                e.preventDefault();
                backdrop.classList.add('show');
            });

            function closeModal() {
                backdrop.classList.remove('show');
            }

            if (btnCancel) btnCancel.addEventListener('click', closeModal);
            if (btnConfirm) btnConfirm.addEventListener('click', function() {
                closeModal();
                confirmed = true;
                form.submit();
            });
            backdrop.addEventListener('click', function(e) {
                if (e.target === backdrop) closeModal();
            });
        })();
    </script>
@endsection

@section('mainContent')
    <div class="main_content_iner main_content_padding my-subscription-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="checkout_wrapper payment_area">
                        <div class="section-tittle page-header">
                            <h3>{{ __('My Subscription') }}</h3>
                            <p class="subtitle">{{ __('View and manage your account subscription.') }}</p>
                        </div>

                        @if($currentSubscription)
                            @php
                                $endsAt = $currentSubscription->ends_at ? \Carbon\Carbon::parse($currentSubscription->ends_at) : null;
                                $daysRemaining = $endsAt && $endsAt->isFuture() ? (int) now()->diffInDays($endsAt, false) : 0;
                            @endphp
                            <div class="my-subscription-card">
                                <div class="card-head" style="color: #000;">{{ $currentSubscription->plan->name ?? __('Subscription') }}</div>
                                <div class="card-body-custom">
                                    @if($currentSubscription->plan && $currentSubscription->plan->description)
                                        <p class="plan-desc">{{ $currentSubscription->plan->description }}</p>
                                    @endif
                                    <div class="details-grid">
                                        <div class="detail-item">
                                            <span class="detail-label">{{ __('Price') }}</span>
                                            <span class="detail-value price">{{ getPriceFormat($currentSubscription->amount_paid) }} {{ $user->currency->code ?? Settings('currency_code') }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">{{ __('Status') }}</span>
                                            <span class="detail-value">
                                                <span class="status-badge {{ $currentSubscription->status }}">{{ ucfirst($currentSubscription->status) }}</span>
                                            </span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">{{ __('Starts at') }}</span>
                                            <span class="detail-value">{{ optional($currentSubscription->starts_at)->format('M d, Y') }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">{{ __('Ends at') }}</span>
                                            <span class="detail-value">{{ optional($currentSubscription->ends_at)->format('M d, Y') }}</span>
                                        </div>
                                    </div>

                                    @if($currentSubscription->status === 'active' && $daysRemaining > 0)
                                        <div class="days-remaining">
                                            {{ $daysRemaining === 1 ? __('1 day remaining') : __(':count days remaining', ['count' => $daysRemaining]) }}
                                        </div>
                                    @endif

                                    @if($currentSubscription->status === 'active')
                                        <div class="cancel-section">
                                            <div class="cancel-title">{{ __('Cancel subscription') }}</div>
                                            <div class="cancel-desc">{{ __('You will keep access until the end of your billing period. No further charges after cancellation.') }}</div>
                                            <form action="{{ route('mySubscription.cancel') }}" method="post" class="js-cancel-subscription-form">
                                                @csrf
                                                <button type="submit" class="btn-cancel-subscription">
                                                    {{ __('Cancel subscription') }}
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($currentSubscription->status === 'cancelled')
                                        <div class="cancelled-notice">
                                            {{ __('Your subscription is cancelled and will end on :date.', ['date' => optional($currentSubscription->ends_at)->format('M d, Y')]) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <div class="empty-title">{{ __('No active subscription') }}</div>
                                <p>{{ __('You do not have an active subscription. Choose a plan to get started.') }}</p>
                                @if($plans && $plans->isNotEmpty())
                                    <p class="plans-choose-title">{{ __('Choose a plan') }}</p>
                                    <div class="row g-4 mt-2">
                                        @foreach($plans as $plan)
                                            <div class="col-12 col-sm-6 col-lg-4">
                                                <div class="plan-option-card">
                                                    <div class="card-head">{{ $plan->name }}</div>
                                                    <div class="card-body-custom">
                                                        @if($plan->description)
                                                            <p class="plan-desc">{{ $plan->description }}</p>
                                                        @endif
                                                        <div class="plan-price-block">
                                                            <span class="price">{{ getPriceFormat($plan->price) }} {{ $currency }}</span>
                                                        </div>
                                                        <p class="plan-validity">{{ $plan->duration_days }} {{ __('days') }} {{ __('validity') }}</p>
                                                        @if($plan->stripe_price_id)
                                                            <a href="{{ route('accountSubscriptionCheckout', $plan) }}" class="btn-subscribe">
                                                                {{ __('Subscribe with Stripe (auto-renew)') }}
                                                            </a>
                                                        @else
                                                            <a href="{{ route('accountSubscription') }}" class="btn-subscribe">
                                                                {{ __('Pay with Stripe') }}
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <a href="{{ route('accountSubscription') }}" class="btn-view-plans" style="margin-top: 1rem;">
                                        {{ __('View subscription plans') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel subscription confirmation modal --}}
    <div id="cancelConfirmModal" class="cancel-confirm-modal-backdrop" aria-hidden="true">
        <div class="cancel-confirm-modal" role="dialog" aria-labelledby="cancelConfirmModalTitle" onclick="event.stopPropagation()">
            <div class="modal-content-wrap">
                <div class="modal-icon-wrap" aria-hidden="true">!</div>
                <div class="modal-header" id="cancelConfirmModalTitle">
                    {{ __('Cancel subscription') }}
                </div>
                <div class="modal-body">
                    {{ __('Are you sure you want to cancel your subscription? You will keep access until the end date.') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelConfirmModalCancel" class="btn-modal-cancel">
                    {{ __('Keep subscription') }}
                </button>
                <button type="button" id="cancelConfirmModalOk" class="btn-modal-confirm">
                    {{ __('Yes, cancel') }}
                </button>
            </div>
        </div>
    </div>
@endsection

