<?php

namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $apiCode,
        string $message,
        public readonly int $httpStatus,
        public readonly mixed $data = null,
    ) {
        parent::__construct($message);
    }
}
