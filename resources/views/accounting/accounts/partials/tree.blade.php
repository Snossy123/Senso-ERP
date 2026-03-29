<li class="list-group-item">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong>{{ $account->code }}</strong> - {{ $account->name }} 
            <span class="badge badge-info ml-2">{{ ucfirst($account->type) }}</span>
        </div>
        <div>
            <strong>Balance: </strong> ${{ number_format($account->balance, 2) }}
        </div>
    </div>
    
    @if($account->children->count())
    <ul class="list-group mt-2 ml-4">
        @foreach($account->children as $child)
            @include('accounting.accounts.partials.tree', ['account' => $child])
        @endforeach
    </ul>
    @endif
</li>
