<?php

namespace CourierHub\DTOs;

use CourierHub\Enums\CourierStatus;

class TrackingResponse
{
    /**
     * @param string $tracking_id
     * @param CourierStatus $current_status
     * @param TrackingEvent[] $history
     * @param string|null $estimated_delivery
     * @param array $raw_response
     */
    public function __construct(
        public string        $tracking_id,
        public CourierStatus $current_status,
        public array         $history = [],
        public ?string       $estimated_delivery = null,
        public array         $raw_response = [],
    ) {}
}
