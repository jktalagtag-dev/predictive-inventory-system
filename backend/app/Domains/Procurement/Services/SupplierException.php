<?php

namespace App\Domains\Procurement\Services;

use RuntimeException;

class SupplierException extends RuntimeException
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
