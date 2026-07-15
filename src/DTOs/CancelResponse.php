<?php

namespace CourierHub\DTOs;

class CancelResponse
{
    public function __construct(
        public bool    $success,
        public string  $message,
        public ?string $tracking_id = null,
        public array   $raw_response = [],
    ) {}
}
