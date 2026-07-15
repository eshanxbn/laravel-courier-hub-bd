<?php

namespace CourierHub\Drivers\ECourier;

use CourierHub\Enums\CourierStatus;

class ECourierStatusMapper
{
    public static function map(string $status): CourierStatus
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'pending'           => CourierStatus::Pending,
            'approved'          => CourierStatus::Confirmed,
            'picked up'         => CourierStatus::PickedUp,
            'in transit'        => CourierStatus::InTransit,
            'out for delivery'  => CourierStatus::OutForDelivery,
            'delivered'         => CourierStatus::Delivered,
            'partial delivered' => CourierStatus::PartialDelivered,
            'return'            => CourierStatus::Returned,
            'cancelled'         => CourierStatus::Cancelled,
            default             => CourierStatus::Unknown,
        };
    }
}
