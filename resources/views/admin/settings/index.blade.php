@extends('layouts.master')
@section('title', __('settings.page_title'))

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="left-content">
            <div>
                <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">{{ __('settings.page_title') }}</h2>
                <p class="mg-b-0">{{ __('settings.page_subtitle') }}</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('messages.common.close') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-3 col-md-4">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="nav flex-column nav-pills" id="settings-tabs" role="tablist" aria-orientation="vertical">
                        @foreach($settingsConfig as $groupKey => $group)
                            <button class="nav-link text-start py-3 px-4 {{ $loop->first ? 'active' : '' }}" 
                                    id="tab-{{ $groupKey }}" 
                                    data-toggle="pill" 
                                    data-target="#content-{{ $groupKey }}" 
                                    type="button" role="tab">
                                <i class="fas fa-{{ match($groupKey) {
                                    'business' => 'briefcase',
                                    'localization' => 'globe',
                                    'sales' => 'shopping-cart',
                                    'inventory' => 'boxes',
                                    'security' => 'shield-alt',
                                    'notifications' => 'bell',
                                    default => 'cog'
                                } }} me-2"></i> 
                                {{ __('settings.group_' . $groupKey) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9 col-md-8">
            <div class="tab-content" id="settings-content">
                @foreach($settingsConfig as $groupKey => $config)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                         id="content-{{ $groupKey }}" role="tabpanel">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 text-primary">{{ __('settings.group_' . $groupKey) }} {{ __('settings.configuration_suffix') }}</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.settings.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="group" value="{{ $groupKey }}">
                                    
                                    @foreach($config as $key => $field)
                                        <div class="mb-4 row">
                                            <label class="col-sm-4 col-form-label fw-bold">{{ $field['label'] }}</label>
                                            <div class="col-sm-8">
                                                @if($field['type'] === 'string')
                                                    <input type="text" name="{{ $key }}" class="form-control" 
                                                           value="{{ setting($key, $field['default'] ?? '') }}">
                                                @elseif($field['type'] === 'integer')
                                                    <input type="number" name="{{ $key }}" class="form-control" 
                                                           value="{{ setting($key, $field['default'] ?? 0) }}">
                                                @elseif($field['type'] === 'boolean')
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" name="{{ $key }}" 
                                                               {{ setting($key, $field['default'] ?? false) ? 'checked' : '' }}>
                                                    </div>
                                                @elseif($field['type'] === 'select')
                                                    <select name="{{ $key }}" class="form-select">
                                                        @foreach($field['options'] as $v => $l)
                                                            <option value="{{ $v }}" {{ setting($key) == $v ? 'selected' : '' }}>{{ $l }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($field['type'] === 'file')
                                                    @if(setting($key))
                                                        <div class="mb-2">
                                                            <img src="{{ asset('storage/' . setting($key)) }}" class="img-thumbnail" style="height: 50px;">
                                                        </div>
                                                    @endif
                                                    <input type="file" name="{{ $key }}" class="form-control">
                                                @endif
                                                @if(isset($field['description']))
                                                    <div class="form-text mt-1 text-mutedSmall">{{ $field['description'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary px-5">
                                            <i class="fas fa-save me-2"></i> {{ __('settings.save_group', ['group' => __('settings.group_' . $groupKey)]) }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .nav-pills .nav-link.active {
        background-color: #f8f9fa;
        color: #0d6efd;
        border-left: 4px solid #0d6efd;
        border-radius: 0;
    }
    .nav-pills .nav-link {
        color: #495057;
        font-weight: 500;
        border-bottom: 1px solid #f1f1f1;
    }
    .nav-pills .nav-link:hover {
        background-color: #fbfbfb;
    }
</style>
@endsection
