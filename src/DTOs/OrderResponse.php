<?php

namespace CourierHub\DTOs;

use CourierHub\Enums\CourierStatus;

class OrderResponse
{
    public function __construct(
        public string        $tracking_id,
        public string        $courier_name,
        public CourierStatus $status,
        public ?string       $consignment_id = null,
        public ?float        $delivery_charge = null,
        public array         $raw_response = [],
    ) {}
}
