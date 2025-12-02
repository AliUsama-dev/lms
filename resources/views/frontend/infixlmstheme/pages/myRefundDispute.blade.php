@extends(theme('layouts.dashboard_master'))
@section('title')
    {{Settings('site_title')  ? Settings('site_title')  : 'Metamorphosis LMS'}} | {{__('product.Store Refund & Dispute')}}
@endsection
@section('css') @endsection
@section('js') @endsection

@section('mainContent')
    <x-my-refund-dispute-page-secton/>
@endsection
