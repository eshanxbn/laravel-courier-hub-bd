<?php

namespace CourierHub\Drivers\Steadfast;

use CourierHub\Enums\CourierStatus;

class SteadfastStatusMapper
{
    public static function map(string $status): CourierStatus
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'pending'           => CourierStatus::Pending,
            'approved'          => CourierStatus::Confirmed,
            'delivered'         => CourierStatus::Delivered,
            'partial delivered' => CourierStatus::PartialDelivered,
            'cancelled'         => CourierStatus::Cancelled,
            'in_transit'        => CourierStatus::InTransit,
            'returned'          => CourierStatus::Returned,
            'hold'              => CourierStatus::OnHold,
            'unknown'           => CourierStatus::Unknown,
            default             => CourierStatus::Unknown,
        };
    }
}
