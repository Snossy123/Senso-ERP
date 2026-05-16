@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto"><h4 class="content-title mb-0">New customer</h4></div>
    <a href="{{ route('crm.customers.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('crm.customers.store') }}">
            @csrf
            @include('crm.customers._form')
            <button class="btn btn-primary">Save customer</button>
        </form>
    </div>
</div>
@endsection
