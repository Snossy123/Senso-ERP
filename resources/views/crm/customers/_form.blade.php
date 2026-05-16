@php $customer = $customer ?? null; @endphp
<div class="form-row">
    <div class="form-group col-md-6">
        <label>Name *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $customer?->name) }}" required>
    </div>
    <div class="form-group col-md-6">
        <label>Company</label>
        <input type="text" name="company" class="form-control" value="{{ old('company', $customer?->company) }}">
    </div>
    <div class="form-group col-md-4">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $customer?->email) }}">
    </div>
    <div class="form-group col-md-4">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer?->phone) }}">
    </div>
    <div class="form-group col-md-4">
        <label>Source</label>
        <input type="text" name="source" class="form-control" value="{{ old('source', $customer?->source) }}" placeholder="walk-in, web, referral">
    </div>
    <div class="form-group col-md-6">
        <label>Assigned to</label>
        <select name="assigned_user_id" class="form-control">
            <option value="">—</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('assigned_user_id', $customer?->assigned_user_id) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label>Tags</label>
        <select name="tag_ids[]" class="form-control" multiple size="4">
            @foreach($tags as $tag)
                <option value="{{ $tag->id }}"
                    @selected(in_array($tag->id, old('tag_ids', $customer?->tags->pluck('id')->all() ?? [])))>
                    {{ $tag->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-12">
        <label>Address</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $customer?->address) }}">
    </div>
    <div class="form-group col-md-4">
        <label>City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $customer?->city) }}">
    </div>
    <div class="form-group col-md-4">
        <label>Tax number</label>
        <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $customer?->tax_number) }}">
    </div>
    <div class="form-group col-md-4 d-flex align-items-end">
        <div class="custom-control custom-checkbox mb-3">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                @checked(old('is_active', $customer?->is_active ?? true))>
            <label class="custom-control-label" for="is_active">Active</label>
        </div>
    </div>
</div>
