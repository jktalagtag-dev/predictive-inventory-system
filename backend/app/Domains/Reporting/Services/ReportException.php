<?php

namespace App\Domains\Reporting\Services;

use RuntimeException;

class ReportException extends RuntimeException
{
    public function __construct(private readonly string $errorCode, private readonly int $httpStatus, string $message)
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->httpStatus;
    }
}
