<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'config',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected function config(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if ($value === null || $value === '') {
                    return [];
                }

                try {
                    return json_decode(Crypt::decryptString($value), true) ?? [];
                } catch (\Throwable) {
                    return json_decode($value, true) ?? [];
                }
            },
            set: function ($value) {
                if (empty($value)) {
                    return null;
                }

                return Crypt::encryptString(json_encode($value));
            }
        );
    }

    public static function drivers(): array
    {
        return ['manual', 'stripe', 'paypal'];
    }
}
