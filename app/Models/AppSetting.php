<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type', 'label', 'description'];

    protected static array $defaults = [
        'discord_notifications_enabled' => [
            'value' => 'true', 'type' => 'boolean',
            'label' => 'Discord Notifications',
            'description' => 'Master toggle for all Discord DM notifications to volunteers.',
        ],
        'volunteer_portal_open' => [
            'value' => 'true', 'type' => 'boolean',
            'label' => 'Volunteer Portal Open',
            'description' => 'When disabled, volunteers see a maintenance message and cannot access the portal.',
        ],
        'max_upload_size_kb' => [
            'value' => '10240', 'type' => 'integer',
            'label' => 'Max Upload Size (KB)',
            'description' => 'Maximum file size for volunteer uploads. Default: 10240 (10 MB).',
        ],
        'notify_on_application_submitted' => [
            'value' => 'true', 'type' => 'boolean',
            'label' => 'Notify: Application Submitted',
            'description' => 'Send a Discord DM to the volunteer when their application is received.',
        ],
        'notify_on_status_change' => [
            'value' => 'true', 'type' => 'boolean',
            'label' => 'Notify: Status Change',
            'description' => 'Send a Discord DM when an application status changes.',
        ],
        'notify_on_rota_published' => [
            'value' => 'true', 'type' => 'boolean',
            'label' => 'Notify: Rota Published',
            'description' => 'Send a Discord DM to all accepted volunteers when the rota goes live.',
        ],
        'notify_on_document_shared' => [
            'value' => 'true', 'type' => 'boolean',
            'label' => 'Notify: Document Shared',
            'description' => 'Send a Discord DM when a document is uploaded with notifications enabled.',
        ],
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        $value = $setting?->value ?? static::$defaults[$key]['value'] ?? $default;

        $type = $setting?->type ?? static::$defaults[$key]['type'] ?? 'string';

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            default   => $value,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        $type = static::$defaults[$key]['type'] ?? 'string';

        static::updateOrCreate(
            ['key' => $key],
            [
                'value'       => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
                'type'        => $type,
                'label'       => static::$defaults[$key]['label'] ?? $key,
                'description' => static::$defaults[$key]['description'] ?? null,
            ]
        );
    }

    public static function allWithDefaults(): array
    {
        $stored = static::all()->keyBy('key');
        $result = [];

        foreach (static::$defaults as $key => $defaults) {
            $setting = $stored->get($key);
            $result[$key] = [
                'key'         => $key,
                'value'       => $setting?->value ?? $defaults['value'],
                'type'        => $defaults['type'],
                'label'       => $defaults['label'],
                'description' => $defaults['description'],
            ];
        }

        return array_values($result);
    }
}
