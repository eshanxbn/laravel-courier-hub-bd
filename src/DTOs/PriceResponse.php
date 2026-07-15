<?php

namespace CourierHub\DTOs;

class PriceResponse
{
    public function __construct(
        public string $courier_name,
        public float  $delivery_charge,
        public float  $cod_charge,
        public float  $total_charge,
        public array  $raw_response = [],
    ) {}
}
