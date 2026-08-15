<?php

return [
    'default_base_url' => env('QP_EXPRESS_URL', 'https://api.qpxpress.com/'),
    'timeout' => (int) env('QP_EXPRESS_TIMEOUT', 20),
    'token_ttl_minutes' => 50,
];
