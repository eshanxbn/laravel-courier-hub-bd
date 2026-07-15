<?php

namespace CourierHub\DTOs;

use CourierHub\Enums\CourierStatus;

class TrackingEvent
{
    public function __construct(
        public CourierStatus $status,
        public string        $timestamp,
        public ?string       $location = null,
        public ?string       $description = null,
    ) {}
}
