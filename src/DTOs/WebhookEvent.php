<?php

namespace CourierHub\DTOs;

use CourierHub\Enums\CourierStatus;

class WebhookEvent
{
    public function __construct(
        public string        $courier_name,
        public string        $tracking_id,
        public CourierStatus $status,
        public array         $raw_payload,
        public ?string       $timestamp = null,
        public ?string       $merchant_order_id = null,
    ) {}
}
