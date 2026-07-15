<?php

namespace CourierHub\Contracts;

use CourierHub\DTOs\BulkOrderResponse;

interface HasBulkOrder
{
    /**
     * Create multiple orders/parcels at once.
     *
     * @param \CourierHub\DTOs\OrderData[] $orders
     */
    public function createBulkOrder(array $orders): BulkOrderResponse;
}
