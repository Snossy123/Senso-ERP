@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto"><h4 class="content-title mb-0">Customer tags</h4></div>
    <a href="{{ route('crm.customers.index') }}" class="btn btn-secondary">Customers</a>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <form method="post" action="{{ route('crm.tags.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input type="color" name="color" class="form-control" value="#6366f1">
                    </div>
                    <button class="btn btn-primary btn-block">Add tag</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <ul class="list-group">
                    @foreach($tags as $tag)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <span class="badge mr-2" style="background:{{ $tag->color }};width:12px;height:12px;">&nbsp;</span>
                                {{ $tag->name }}
                                <small class="text-muted">({{ $tag->customers_count }} customers)</small>
                            </span>
                            <form method="post" action="{{ route('crm.tags.destroy', $tag) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
