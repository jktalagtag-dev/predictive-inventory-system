<?php

namespace App\Domains\Planning\Services;

use RuntimeException;

/**
 * Expected business refusal for forecasting, EOQ, ROP, and restocking
 * workflows, distinct from unexpected faults.
 */
class PlanningException extends RuntimeException
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
