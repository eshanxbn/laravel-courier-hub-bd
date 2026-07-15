<?php

namespace CourierHub\DTOs;

class FraudCheckResponse
{
    public function __construct(
        public bool   $is_fraud,
        public int    $total_orders = 0,
        public float  $success_rate = 0.0,
        public array  $details = [],
        public array  $raw_response = [],
    ) {}
}
