<?php

namespace App\Domains\Sync\Services;

use RuntimeException;

/**
 * The queued payload was internally valid but conflicts with server
 * state that changed since the operation was queued offline (e.g. stock
 * moved enough that the client's stale snapshot no longer holds). Never
 * auto-resolved by timestamp; a human reviews local vs. server values
 * (CLAUDE.md section 42).
 */
class SyncOperationConflictException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $conflictPayload  Safe local/server comparison values only.
     */
    public function __construct(private readonly string $errorCode, string $message, private readonly array $conflictPayload)
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function conflictPayload(): array
    {
        return $this->conflictPayload;
    }
}
