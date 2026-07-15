<?php

namespace CourierHub\Contracts;

use CourierHub\DTOs\CancelResponse;
use CourierHub\DTOs\OrderData;
use CourierHub\DTOs\OrderResponse;
use CourierHub\DTOs\PriceCalculationData;
use CourierHub\DTOs\PriceResponse;
use CourierHub\DTOs\TrackingResponse;

interface CourierDriver
{
    /**
     * Create a new order/parcel.
     */
    public function createOrder(OrderData $order): OrderResponse;

    /**
     * Track an order by its tracking ID.
     */
    public function trackOrder(string $trackingId): TrackingResponse;

    /**
     * Cancel an order by its tracking ID.
     */
    public function cancelOrder(string $trackingId): CancelResponse;

    /**
     * Calculate delivery charge based on area and weight.
     */
    public function calculatePrice(PriceCalculationData $data): PriceResponse;
}
