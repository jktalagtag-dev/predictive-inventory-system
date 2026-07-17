<?php

namespace App\Domains\Governance\Support;

/**
 * The approved set of configurable setting keys (REST_API_SPECIFICATION.md
 * section 15, "Key must exist in approved registry"). A setting cannot be
 * created or read outside this list, which keeps configuration reviewable
 * in code rather than free-form.
 */
final class SettingsRegistry
{
    /**
     * @return array<string, SettingDefinition>
     */
    public static function all(): array
    {
        return [
            'inventory.negative_stock_allowed' => new SettingDefinition(
                key: 'inventory.negative_stock_allowed',
                valueType: 'boolean',
                defaultValue: false,
                ownerOnly: true,
                isSensitive: false,
                description: 'Whether available inventory may go negative without an explicit adjustment override (CLAUDE.md section 44).',
            ),
            'pos.default_tax_rate' => new SettingDefinition(
                key: 'pos.default_tax_rate',
                valueType: 'decimal',
                defaultValue: '0.1200',
                ownerOnly: false,
                isSensitive: false,
                description: 'Default sales tax rate applied at checkout when a product does not define its own rate.',
            ),
            'reports.export_retention_days' => new SettingDefinition(
                key: 'reports.export_retention_days',
                valueType: 'integer',
                defaultValue: 30,
                ownerOnly: false,
                isSensitive: false,
                description: 'Number of days a generated report export remains downloadable before it expires.',
            ),
            'security.session_lifetime_minutes' => new SettingDefinition(
                key: 'security.session_lifetime_minutes',
                valueType: 'integer',
                defaultValue: 120,
                ownerOnly: true,
                isSensitive: false,
                description: 'Maximum idle session lifetime in minutes before re-authentication is required.',
            ),
            'security.mfa_required_for_owner' => new SettingDefinition(
                key: 'security.mfa_required_for_owner',
                valueType: 'boolean',
                defaultValue: false,
                ownerOnly: true,
                isSensitive: true,
                description: 'Whether multifactor authentication is required at login for Owner accounts (CLAUDE.md section 57).',
            ),
        ];
    }

    public static function find(string $key): ?SettingDefinition
    {
        return self::all()[$key] ?? null;
    }
}
