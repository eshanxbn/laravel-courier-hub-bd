<?php

namespace CourierHub\Exceptions;

class CourierApiException extends CourierException
{
    public function __construct(
        string $message = "",
        int $code = 0,
        \Throwable $previous = null,
        public readonly array $responseBody = []
    ) {
        parent::__construct($message, $code, $previous);
    }
}
