<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\App\Services\SettingService;

/**
 * Sane starting defaults for every settings tab, per PRD v1 §3.15/§4.13
 * — "Company Settings (company name/branding, shown here as 'Vishesh
 * Textiles', logo, and contact details)." Idempotent (SettingService::
 * set() is an updateOrCreate), safe to re-run.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        SettingService::set('currency.code', 'BDT', 'currency');
        SettingService::set('currency.symbol', '৳', 'currency');
        SettingService::set('currency.format', '#,##0.00', 'currency');

        SettingService::set('notification.low_stock_alerts', true, 'notification');
        SettingService::set('notification.order_status_alerts', true, 'notification');
        SettingService::set('notification.email_enabled', false, 'notification');

        SettingService::set('system.date_format', 'd M Y', 'system');
        SettingService::set('system.timezone', 'Asia/Dhaka', 'system');
        SettingService::set('system.items_per_page', 20, 'system');
        SettingService::set('system.fiscal_year_start_month', 7, 'system');

        SettingService::set('company.name', 'Vishesh Textiles', 'company');
        SettingService::set('company.logo_path', null, 'company');
        SettingService::set('company.address', '', 'company');
        SettingService::set('company.phone', '', 'company');
        SettingService::set('company.email', '', 'company');
    }
}
