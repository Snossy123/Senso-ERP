@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto"><h4 class="content-title mb-0">Edit {{ $customer->name }}</h4></div>
    <a href="{{ route('crm.customers.show', $customer) }}" class="btn btn-secondary">Back</a>
</div>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('crm.customers.update', $customer) }}">
            @csrf
            @method('PUT')
            @include('crm.customers._form', ['customer' => $customer])
            <button class="btn btn-primary">Update</button>
        </form>
    </div>
</div>
@endsection
