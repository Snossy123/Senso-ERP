<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Synthetic support user email domain
    |--------------------------------------------------------------------------
    |
    | When no support_email is provided at tenant creation, the tenant
    | administrator account uses support.t{id}@{domain} for uniqueness.
    |
    */
    'support_email_domain' => env('TENANT_SUPPORT_EMAIL_DOMAIN', 'tenants.invalid'),
];
