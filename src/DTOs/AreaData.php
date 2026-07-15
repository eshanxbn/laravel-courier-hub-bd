<?php

namespace CourierHub\DTOs;

class AreaData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $postcode = null,
        public array  $raw_data = [],
    ) {}
}
