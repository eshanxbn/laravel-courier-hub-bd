<?php

namespace CourierHub\DTOs;

class BulkOrderResponse
{
    public function __construct(
        public int   $created_count,
        public int   $failed_count,
        /** @var \CourierHub\DTOs\OrderResponse[] */
        public array $results = [],
        public array $raw_response = [],
    ) {}
}
