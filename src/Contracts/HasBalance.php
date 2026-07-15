<?php

namespace CourierHub\Contracts;

use CourierHub\DTOs\BalanceResponse;

interface HasBalance
{
    /**
     * Get the merchant's current balance/payable amount.
     */
    public function getBalance(): BalanceResponse;
}
