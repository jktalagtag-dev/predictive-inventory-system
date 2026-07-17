<?php

namespace App\Domains\Governance\Services;

use App\Domains\Governance\Models\Setting;
use App\Domains\Governance\Support\SettingDefinition;
use App\Domains\Governance\Support\SettingsRegistry;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Typed, versioned configuration reads and writes. Every write is wrapped
 * in a transaction with an audit entry (CLAUDE.md section 55) and every
 * read redacts sensitive values unless the caller holds
 * settings.read_sensitive, regardless of the requesting branch scope.
 */
class SettingsService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?int $branchId, ?string $prefix, User $actor): array
    {
        $definitions = collect(SettingsRegistry::all())
            ->when($prefix !== null, fn ($collection) => $collection->filter(fn (SettingDefinition $definition) => Str::startsWith($definition->key, $prefix)));

        $keys = $definitions->keys()->all();

        $overrides = Setting::query()
            ->whereIn('setting_key', $keys)
            ->where('branch_id', $branchId)
            ->get()
            ->keyBy('setting_key');

        return $definitions
            ->map(fn (SettingDefinition $definition) => $this->present($definition, $overrides->get($definition->key), $branchId, $actor))
            ->values()
            ->all();
    }

    /**
     * Internal, non-redacted read for other services that need an
     * operational parameter (e.g. export retention days) rather than the
     * user-facing, permission-redacted view returned by get()/list().
     */
    public function resolveValue(string $key, ?int $branchId): mixed
    {
        $definition = SettingsRegistry::find($key);

        if ($definition === null) {
            throw new SettingException('UNKNOWN_SETTING', 404, 'This setting key is not in the approved registry.');
        }

        $override = Setting::query()->where('setting_key', $key)->where('branch_id', $branchId)->first();

        return $override?->value_json ?? $definition->defaultValue;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $key, ?int $branchId, User $actor): array
    {
        $definition = SettingsRegistry::find($key);

        if ($definition === null) {
            throw new SettingException('UNKNOWN_SETTING', 404, 'This setting key is not in the approved registry.');
        }

        $override = Setting::query()->where('setting_key', $key)->where('branch_id', $branchId)->first();

        return $this->present($definition, $override, $branchId, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function upsert(string $key, ?int $branchId, string $valueType, mixed $value, ?int $expectedVersion, User $actor, string $correlationId): array
    {
        $definition = SettingsRegistry::find($key);

        if ($definition === null) {
            throw new SettingException('UNKNOWN_SETTING', 404, 'This setting key is not in the approved registry.');
        }

        if ($valueType !== $definition->valueType) {
            throw new SettingException('INVALID_SETTING_VALUE', 422, "This setting must be of type {$definition->valueType}.");
        }

        if ($definition->ownerOnly && ! $actor->hasRole('owner')) {
            throw new SettingException('FORBIDDEN', 403, 'Only an Owner may change this setting.');
        }

        $this->assertValueMatchesType($definition, $value);

        return DB::transaction(function () use ($definition, $branchId, $value, $expectedVersion, $actor, $correlationId) {
            $existing = Setting::query()->where('setting_key', $definition->key)->where('branch_id', $branchId)->lockForUpdate()->first();

            $before = $existing ? $this->redactedAuditValue($definition, $existing->value_json) : null;

            if ($existing === null) {
                if ($expectedVersion !== null && $expectedVersion !== 0) {
                    throw new SettingException('VERSION_CONFLICT', 409, 'This setting was changed by someone else. Reload and try again.');
                }

                $setting = Setting::query()->create([
                    'branch_id' => $branchId,
                    'setting_key' => $definition->key,
                    'value_type' => $definition->valueType,
                    'value_json' => $value,
                    'is_sensitive' => $definition->isSensitive,
                    'description' => $definition->description,
                    'row_version' => 1,
                    'created_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);
            } else {
                if ($expectedVersion !== $existing->row_version) {
                    throw new SettingException('VERSION_CONFLICT', 409, 'This setting was changed by someone else. Reload and try again.');
                }

                $existing->value_json = $value;
                $existing->row_version = $existing->row_version + 1;
                $existing->updated_by_user_id = $actor->id;
                $existing->save();
                $setting = $existing;
            }

            $this->auditLogger->record(
                actor: $actor,
                action: 'setting.updated',
                entityType: 'system_setting',
                entityId: $setting->id,
                branchId: $branchId,
                correlationId: $correlationId,
                before: $before,
                after: $this->redactedAuditValue($definition, $value),
            );

            return $this->present($definition, $setting, $branchId, $actor);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SettingDefinition $definition, ?Setting $override, ?int $branchId, User $actor): array
    {
        $canReadSensitive = $actor->hasPermission('settings.read_sensitive');
        $rawValue = $override?->value_json ?? $definition->defaultValue;

        return [
            'key' => $definition->key,
            'branchId' => $branchId,
            'valueType' => $definition->valueType,
            'value' => ($definition->isSensitive && ! $canReadSensitive) ? null : $rawValue,
            'isSensitive' => $definition->isSensitive,
            'isRedacted' => $definition->isSensitive && ! $canReadSensitive,
            'ownerOnly' => $definition->ownerOnly,
            'description' => $definition->description,
            'version' => $override?->row_version ?? 0,
            'isOverridden' => $override !== null,
        ];
    }

    private function assertValueMatchesType(SettingDefinition $definition, mixed $value): void
    {
        $valid = match ($definition->valueType) {
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'decimal' => is_string($value) && is_numeric($value),
            'string' => is_string($value),
            'json' => is_array($value),
            'date' => is_string($value) && strtotime($value) !== false,
            default => false,
        };

        if (! $valid) {
            throw new SettingException('INVALID_SETTING_VALUE', 422, "The provided value does not match the {$definition->valueType} type.");
        }
    }

    /**
     * Sensitive setting values are never stored in the audit trail in the
     * clear, even though the setting row itself holds the real value
     * (CLAUDE.md section 55, "Redact secrets ... from audit payloads").
     */
    private function redactedAuditValue(SettingDefinition $definition, mixed $value): array
    {
        return ['value' => $definition->isSensitive ? '[redacted]' : $value];
    }
}
