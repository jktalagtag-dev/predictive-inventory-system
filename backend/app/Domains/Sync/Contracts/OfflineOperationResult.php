<?php

namespace App\Domains\Sync\Contracts;

/**
 * The accepted outcome of a successfully handled offline operation.
 */
final class OfflineOperationResult
{
    public function __construct(
        public readonly string $resourceType,
        public readonly int $resourceId,
        public readonly array $resourceSnapshot,
    ) {
    }
}
