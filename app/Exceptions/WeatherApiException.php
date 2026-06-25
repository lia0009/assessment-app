<?php

namespace App\Exceptions;

use RuntimeException;

class WeatherApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 502,
    ) {
        parent::__construct($message);
    }
}
