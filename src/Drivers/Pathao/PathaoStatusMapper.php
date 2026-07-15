<?php

namespace CourierHub\Drivers\Pathao;

use CourierHub\Enums\CourierStatus;

class PathaoStatusMapper
{
    public static function map(string $status): CourierStatus
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'pending'           => CourierStatus::Pending,
            'pickup_requested'  => CourierStatus::Pending,
            'assigned_for_pickup' => CourierStatus::Confirmed,
            'picked'            => CourierStatus::PickedUp,
            'in_transit'        => CourierStatus::InTransit,
            'out_for_delivery'  => CourierStatus::OutForDelivery,
            'delivered'         => CourierStatus::Delivered,
            'partial_delivered' => CourierStatus::PartialDelivered,
            'return_in_transit' => CourierStatus::ReturnInTransit,
            'returned'          => CourierStatus::Returned,
            'on_hold'           => CourierStatus::OnHold,
            'cancelled'         => CourierStatus::Cancelled,
            default             => CourierStatus::Unknown,
        };
    }
}
