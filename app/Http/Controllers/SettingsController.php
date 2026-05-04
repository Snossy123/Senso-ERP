<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    protected SettingService $settings;

    public function __construct(SettingService $settings)
    {
        $this->settings = $settings;
        $this->middleware('auth');
    }

    public function index()
    {
        $groups = $this->settings->allGrouped();
        
        // Define default structure for grouped settings
        $settingsConfig = [
            'business' => [
                'company_name' => ['label' => __('settings.field_company_name'), 'type' => 'string'],
                'company_logo' => ['label' => __('settings.field_company_logo'), 'type' => 'file'],
                'vat_number' => ['label' => __('settings.field_vat_number'), 'type' => 'string'],
                'invoice_prefix' => ['label' => __('settings.field_invoice_prefix'), 'type' => 'string', 'default' => 'INV-'],
                'invoice_format' => ['label' => __('settings.field_invoice_format'), 'type' => 'select', 'options' => [
                    'standard' => __('settings.invoice_format_standard'),
                    'modern' => __('settings.invoice_format_modern'),
                    'classic' => __('settings.invoice_format_classic'),
                ]],
            ],
            'localization' => [
                'language' => [
                    'label' => __('settings.field_default_language'),
                    'type' => 'select',
                    'options' => collect(config('locales.supported', []))
                        ->mapWithKeys(fn (array $meta, string $code) => [$code => $meta['native']])
                        ->all(),
                ],
                'timezone' => ['label' => __('settings.field_timezone'), 'type' => 'string', 'default' => 'UTC'],
                'date_format' => ['label' => __('settings.field_date_format'), 'type' => 'select', 'options' => ['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY']],
            ],
            'sales' => [
                'default_payment_method' => ['label' => __('settings.field_default_payment_method'), 'type' => 'select', 'options' => [
                    'cash' => __('settings.payment_cash'),
                    'card' => __('settings.payment_card'),
                    'bank_transfer' => __('settings.payment_bank_transfer'),
                ]],
                'pos_tax_enabled' => ['label' => __('settings.field_pos_tax_enabled'), 'type' => 'boolean', 'default' => true],
                'default_discount_limit' => ['label' => __('settings.field_default_discount_limit'), 'type' => 'integer', 'default' => 20],
            ],
            'inventory' => [
                'low_stock_threshold' => ['label' => __('settings.field_low_stock_threshold'), 'type' => 'integer', 'default' => 5],
                'allow_negative_stock' => ['label' => __('settings.field_allow_negative_stock'), 'type' => 'boolean', 'default' => false],
                'auto_stock_updates' => ['label' => __('settings.field_auto_stock_updates'), 'type' => 'boolean', 'default' => true],
            ],
            'security' => [
                'session_timeout' => ['label' => __('settings.field_session_timeout'), 'type' => 'integer', 'default' => 120],
                'max_login_attempts' => ['label' => __('settings.field_max_login_attempts'), 'type' => 'integer', 'default' => 5],
                'password_min_length' => ['label' => __('settings.field_password_min_length'), 'type' => 'integer', 'default' => 8],
            ],
            'notifications' => [
                'email_alerts_enabled' => ['label' => __('settings.field_email_alerts_enabled'), 'type' => 'boolean', 'default' => true],
                'low_stock_notification' => ['label' => __('settings.field_low_stock_notification'), 'type' => 'boolean', 'default' => true],
                'notification_email' => ['label' => __('settings.field_notification_email'), 'type' => 'string'],
            ],
        ];

        return view('admin.settings.index', compact('groups', 'settingsConfig'));
    }

    public function store(Request $request)
    {
        $group = $request->input('group', 'general');
        $inputs = $request->except(['_token', 'group']);

        foreach ($inputs as $key => $value) {
            if ($request->hasFile($key)) {
                $path = $request->file($key)->store('settings', 'public');
                $value = $path;
            }

            // Handle checkbox/boolean
            if ($value === 'on') $value = true;
            if ($value === 'off') $value = false;

            $this->settings->set($key, $value, $group);
        }

        $groupLabel = match ($group) {
            'business' => __('settings.group_business'),
            'localization' => __('settings.group_localization'),
            'sales' => __('settings.group_sales'),
            'inventory' => __('settings.group_inventory'),
            'security' => __('settings.group_security'),
            'notifications' => __('settings.group_notifications'),
            default => ucfirst($group),
        };

        return back()->with('success', __('settings.flash_updated', ['group' => $groupLabel]));
    }
}
