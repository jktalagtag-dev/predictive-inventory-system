<?php

namespace App\Domains\Identity\Services;

use RuntimeException;

/**
 * Expected business refusal for user-access workflows (e.g. removing the
 * last active Owner), distinct from unexpected faults.
 */
class UserAccessException extends RuntimeException
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
