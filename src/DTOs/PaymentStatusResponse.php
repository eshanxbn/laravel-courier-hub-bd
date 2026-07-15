<?php

namespace CourierHub\DTOs;

class PaymentStatusResponse
{
    public function __construct(
        public string $tracking_id,
        public string $status,
        public float  $paid_amount,
        public float  $pending_amount,
        public ?string $payment_date = null,
        public array  $raw_response = [],
    ) {}
}
