<?php

namespace CourierHub\Drivers\RedX;

use CourierHub\Enums\CourierStatus;

class RedXStatusMapper
{
    public static function map(string $status): CourierStatus
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'pending'           => CourierStatus::Pending,
            'parcel-placed'     => CourierStatus::Pending,
            'pickup-requested'  => CourierStatus::Confirmed,
            'picked-up'         => CourierStatus::PickedUp,
            'in-transit'        => CourierStatus::InTransit,
            'out-for-delivery'  => CourierStatus::OutForDelivery,
            'delivered'         => CourierStatus::Delivered,
            'partial-delivered' => CourierStatus::PartialDelivered,
            'return-in-transit' => CourierStatus::ReturnInTransit,
            'returned'          => CourierStatus::Returned,
            'cancelled'         => CourierStatus::Cancelled,
            default             => CourierStatus::Unknown,
        };
    }
}
