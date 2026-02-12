@extends('backend.master')

@section('mainContent')
    <section class="admin-visitor-area up_st_admin_visitor">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-lg-12">
                    <div class="main-title d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">{{ __('Account Subscription Plans') }}</h3>
                        <a href="{{ route('admin.account-subscription.plans.create') }}" class="primary-btn fix-gr-bg">
                            {{ __('common.Add New') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="QA_section QA_section_heading_custom check_box_table">
                        <div class="QA_table">
                            <table class="table Crm_table_active3">
                                <thead>
                                <tr>
                                    <th>{{ __('common.SL') }}</th>
                                    <th>{{ __('common.Name') }}</th>
                                    <th>{{ __('common.Price') }}</th>
                                    <th>{{ __('Validity (days)') }}</th>
                                    <th>{{ __('common.Status') }}</th>
                                    <th>{{ __('common.Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($plans as $key => $plan)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $plan->name }}</td>
                                        <td>{{ getPriceFormat($plan->price) }}</td>
                                        <td>{{ $plan->duration_days }}</td>
                                        <td>
                                            @if($plan->status)
                                                <span class="badge_1">{{ __('common.Active') }}</span>
                                            @else
                                                <span class="badge_4">{{ __('common.Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown CRM_dropdown">
                                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                                        id="dropdownMenu{{ $plan->id }}" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                    {{ __('common.Action') }}
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right"
                                                     aria-labelledby="dropdownMenu{{ $plan->id }}">
                                                    <a class="dropdown-item"
                                                       href="{{ route('admin.account-subscription.plans.edit', $plan) }}">{{ __('common.Edit') }}</a>
                                                    <form action="{{ route('admin.account-subscription.plans.destroy', $plan) }}"
                                                          method="post"
                                                          onsubmit="return confirm('{{ __('common.Are you sure to delete ?') }}')">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="submit" class="dropdown-item">{{ __('common.Delete') }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

