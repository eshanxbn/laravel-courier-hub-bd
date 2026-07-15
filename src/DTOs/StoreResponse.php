<?php

namespace CourierHub\DTOs;

class StoreResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $address,
        public ?string $phone = null,
        public array  $raw_response = [],
    ) {}
}
