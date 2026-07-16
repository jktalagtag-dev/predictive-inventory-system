<?php

namespace App\Domains\Catalog\Services;

use RuntimeException;

/**
 * Expected business refusal for category workflows (duplicate code,
 * parent cycle), distinct from unexpected faults.
 */
class CategoryException extends RuntimeException
{
    public function __construct(private readonly string $errorCode, private readonly int $status, string $message)
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }
}
