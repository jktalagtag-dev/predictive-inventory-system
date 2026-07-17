<?php

namespace App\Domains\Inventory\Services;

use RuntimeException;

/**
 * Expected business refusal for goods-receiving workflows (illegal state
 * transition, over-receipt, duplicate delivery reference, tracked-product
 * requirement), distinct from unexpected faults.
 */
class GoodsReceiptException extends RuntimeException
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
