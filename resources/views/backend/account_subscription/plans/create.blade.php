@extends('backend.master')

@section('mainContent')
    <section class="admin-visitor-area up_st_admin_visitor">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-lg-12">
                    <div class="main-title d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">{{ __('Add Subscription Plan') }}</h3>
                        <a href="{{ route('admin.account-subscription.plans.index') }}" class="primary-btn fix-gr-bg">
                            {{ __('common.Back') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <form action="{{ route('admin.account-subscription.plans.store') }}" method="POST" class="form-horizontal">
                        @csrf
                        <div class="white-box">
                            <div class="row">
                                <div class="col-xl-6">
                                    <div class="primary_input mb-25">
                                        <label class="primary_input_label" for="name">{{ __('common.Name') }}</label>
                                        <input type="text" name="name" id="name" class="primary_input_field"
                                               value="{{ old('name') }}" required>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="primary_input mb-25">
                                        <label class="primary_input_label" for="price">{{ __('common.Price') }}</label>
                                        <input type="number" step="0.01" min="0" name="price" id="price"
                                               class="primary_input_field" value="{{ old('price') }}" required>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="primary_input mb-25">
                                        <label class="primary_input_label" for="duration_days">{{ __('Validity (days)') }}</label>
                                        <input type="number" min="1" name="duration_days" id="duration_days"
                                               class="primary_input_field" value="{{ old('duration_days', 30) }}" required>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="primary_input mb-25">
                                        <label class="primary_input_label" for="order">{{ __('common.Order') }}</label>
                                        <input type="number" min="0" name="order" id="order" class="primary_input_field"
                                               value="{{ old('order', 0) }}">
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <div class="primary_input mb-25">
                                        <label class="primary_input_label" for="description">{{ __('common.Description') }}</label>
                                        <textarea name="description" id="description" rows="3"
                                                  class="primary_textarea">{{ old('description') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <div class="primary_input mb-25 d-flex align-items-center">
                                        <label class="primary_checkbox d-flex mr-12" for="status">
                                            <input type="checkbox" id="status" name="status" value="1" checked>
                                            <span class="checkmark"></span>
                                        </label>
                                        <label class="mb-0" for="status">{{ __('common.Active') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-40">
                                <div class="col-lg-12 text-center">
                                    <button class="primary-btn fix-gr-bg" type="submit">
                                        <span class="ti-check"></span>
                                        {{ __('common.Save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

