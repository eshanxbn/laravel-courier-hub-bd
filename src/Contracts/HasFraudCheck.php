<?php

namespace CourierHub\Contracts;

use CourierHub\DTOs\FraudCheckResponse;

interface HasFraudCheck
{
    /**
     * Check if a customer phone number is fraudulent or has a high return rate.
     */
    public function checkFraud(string $phone): FraudCheckResponse;
}
