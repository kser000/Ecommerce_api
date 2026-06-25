<?php

namespace App\Exceptions;

use RuntimeException;

class BusinessException extends RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 422)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
