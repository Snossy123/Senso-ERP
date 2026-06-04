@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto"><h4 class="content-title mb-0">{{ __('sales_invoices.new') }}</h4></div>
    <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">{{ __('sales_invoices.back') }}</a>
</div>
@endsection
@section('content')
<div class="card"><div class="card-body">
    <form method="post" action="{{ route('sales.invoices.store') }}">
        @csrf
        @include('sales.invoices._form')
        <div class="mt-3">
            <button type="submit" name="confirm_now" value="0" class="btn btn-primary">{{ __('sales_invoices.save_draft') }}</button>
            <button type="submit" name="confirm_now" value="1" class="btn btn-success">{{ __('sales_invoices.save_confirm') }}</button>
        </div>
    </form>
</div></div>
@endsection
