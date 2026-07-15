<?php

namespace CourierHub\Enums;

enum CourierStatus: string
{
    case Pending          = 'pending';
    case Confirmed        = 'confirmed';
    case PickedUp         = 'picked_up';
    case InTransit        = 'in_transit';
    case OutForDelivery   = 'out_for_delivery';
    case Delivered        = 'delivered';
    case PartialDelivered = 'partial_delivered';
    case Cancelled        = 'cancelled';
    case OnHold           = 'on_hold';
    case ReturnInTransit  = 'return_in_transit';
    case Returned         = 'returned';
    case Failed           = 'failed';
    case Unknown          = 'unknown';
}
