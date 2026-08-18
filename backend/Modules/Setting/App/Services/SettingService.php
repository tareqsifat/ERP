<?php

namespace Modules\Setting\App\Services;

use Modules\Setting\App\Models\Setting;

/**
 * The only writer of the `settings` table (SettingController is a thin
 * wrapper around this). `get()`/`allGrouped()` are safe to call from
 * anywhere in the app (e.g. a future Notification service reading
 * `notification.low_stock_alerts`) without depending on the HTTP layer.
 */
class SettingService
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()->where('key', $key)->first();

        return $setting ? ($setting->value['value'] ?? $default) : $default;
    }

    public static function set(string $key, mixed $value, string $group, ?int $updatedBy = null): Setting
    {
        return Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => ['value' => $value], 'group' => $group, 'updated_by' => $updatedBy],
        );
    }

    /**
     * Grouped view for the Settings page's four tabs (Currency,
     * Notifications, System, Company) — {group: {key: value}}.
     */
    public static function allGrouped(): array
    {
        $result = ['currency' => [], 'notification' => [], 'system' => [], 'company' => []];

        foreach (Setting::query()->get() as $setting) {
            $shortKey = str($setting->key)->after($setting->group.'.')->value();
            $result[$setting->group][$shortKey] = $setting->value['value'] ?? null;
        }

        return $result;
    }
}
