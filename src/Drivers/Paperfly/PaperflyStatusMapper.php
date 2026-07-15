<?php

namespace CourierHub\Drivers\Paperfly;

use CourierHub\Enums\CourierStatus;

class PaperflyStatusMapper
{
    public static function map(string $status): CourierStatus
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'pending'           => CourierStatus::Pending,
            'picked'            => CourierStatus::PickedUp,
            'in transit'        => CourierStatus::InTransit,
            'delivery'          => CourierStatus::Delivered,
            'return'            => CourierStatus::Returned,
            'cancelled'         => CourierStatus::Cancelled,
            default             => CourierStatus::Unknown,
        };
    }
}
