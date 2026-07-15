<?php

namespace CourierHub\DTOs;

class BalanceResponse
{
    public function __construct(
        public float  $current_balance,
        public string $currency = 'BDT',
        public ?float $pending_balance = null,
        public array  $raw_response = [],
    ) {}
}
