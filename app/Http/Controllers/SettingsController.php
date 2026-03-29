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
                'company_name' => ['label' => 'Company Name', 'type' => 'string'],
                'company_logo' => ['label' => 'Company Logo', 'type' => 'file'],
                'vat_number' => ['label' => 'VAT Number', 'type' => 'string'],
                'invoice_prefix' => ['label' => 'Invoice Prefix', 'type' => 'string', 'default' => 'INV-'],
                'invoice_format' => ['label' => 'Invoice Format', 'type' => 'select', 'options' => ['standard' => 'Standard', 'modern' => 'Modern', 'classic' => 'Classic']],
            ],
            'localization' => [
                'language' => ['label' => 'Language', 'type' => 'select', 'options' => ['en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'ar' => 'Arabic']],
                'timezone' => ['label' => 'Timezone', 'type' => 'string', 'default' => 'UTC'],
                'date_format' => ['label' => 'Date Format', 'type' => 'select', 'options' => ['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY']],
            ],
            'sales' => [
                'default_payment_method' => ['label' => 'Default Payment Method', 'type' => 'select', 'options' => ['cash' => 'Cash', 'card' => 'Credit Card', 'bank_transfer' => 'Bank Transfer']],
                'pos_tax_enabled' => ['label' => 'Enable Tax in POS', 'type' => 'boolean', 'default' => true],
                'default_discount_limit' => ['label' => 'Max Discount (%)', 'type' => 'integer', 'default' => 20],
            ],
            'inventory' => [
                'low_stock_threshold' => ['label' => 'Low Stock Threshold', 'type' => 'integer', 'default' => 5],
                'allow_negative_stock' => ['label' => 'Allow Negative Stock', 'type' => 'boolean', 'default' => false],
                'auto_stock_updates' => ['label' => 'Auto Update Stock on Sale', 'type' => 'boolean', 'default' => true],
            ],
            'security' => [
                'session_timeout' => ['label' => 'Session Timeout (min)', 'type' => 'integer', 'default' => 120],
                'max_login_attempts' => ['label' => 'Max Login Attempts', 'type' => 'integer', 'default' => 5],
                'password_min_length' => ['label' => 'Min Password Length', 'type' => 'integer', 'default' => 8],
            ],
            'notifications' => [
                'email_alerts_enabled' => ['label' => 'Enable Email Alerts', 'type' => 'boolean', 'default' => true],
                'low_stock_notification' => ['label' => 'Low Stock Alerts', 'type' => 'boolean', 'default' => true],
                'notification_email' => ['label' => 'Notification Email Address', 'type' => 'string'],
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

        return back()->with('success', ucfirst($group) . ' settings updated successfully.');
    }
}
