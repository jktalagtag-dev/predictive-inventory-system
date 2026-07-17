<?php

namespace App\Domains\Sales\Services;

use RuntimeException;

/**
 * Expected business refusal for POS/sales workflows (insufficient stock,
 * payment mismatch, forbidden override, illegal state transition),
 * distinct from unexpected faults.
 */
class SaleException extends RuntimeException
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
