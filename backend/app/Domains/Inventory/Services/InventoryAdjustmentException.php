<?php

namespace App\Domains\Inventory\Services;

use RuntimeException;

/**
 * Expected business refusal for inventory adjustment workflows (illegal
 * state transition, negative stock, self-approval, duplicate operation),
 * distinct from unexpected faults.
 */
class InventoryAdjustmentException extends RuntimeException
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
