<?php

namespace CourierHub\Contracts;

use CourierHub\DTOs\PaymentStatusResponse;

interface HasPaymentStatus
{
    /**
     * Get COD payment reconciliation status for an order.
     */
    public function paymentStatus(string $trackingId): PaymentStatusResponse;
}
