<?php

namespace App\Domains\Catalog\Services;

use RuntimeException;

/**
 * Expected business refusal for product workflows (duplicate SKU or
 * barcode), distinct from unexpected faults.
 */
class ProductException extends RuntimeException
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
