<?php

namespace App\Domains\Governance\Support;

final class SettingDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $valueType,
        public readonly mixed $defaultValue,
        public readonly bool $ownerOnly,
        public readonly bool $isSensitive,
        public readonly string $description,
    ) {
    }
}
