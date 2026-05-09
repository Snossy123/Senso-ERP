<?php

use App\Models\PosShift;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tenant.{tenantId}.pos', function ($user, $tenantId) {
    return isset($user->tenant_id) && (int) $user->tenant_id === (int) $tenantId;
});

Broadcast::channel('tenant.{tenantId}.inventory', function ($user, $tenantId) {
    return isset($user->tenant_id) && (int) $user->tenant_id === (int) $tenantId;
});

Broadcast::channel('shift.{shiftId}', function ($user, $shiftId) {
    $shift = PosShift::find($shiftId);

    return $shift
        && isset($user->tenant_id)
        && (int) $shift->tenant_id === (int) $user->tenant_id;
});
