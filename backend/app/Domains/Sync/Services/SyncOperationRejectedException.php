<?php

namespace App\Domains\Sync\Services;

use RuntimeException;

/**
 * A validation, authorization, or business-rule refusal — never
 * automatically retried by the client (DEVELOPMENT_ROADMAP.md M9
 * acceptance criteria, "validation, authorization, and conflicts stop
 * automatic retry").
 */
class SyncOperationRejectedException extends RuntimeException
{
    public function __construct(private readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
