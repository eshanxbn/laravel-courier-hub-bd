<?php

namespace CourierHub\Facades;

use CourierHub\CourierManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \CourierHub\Contracts\CourierDriver driver(string|null $driver = null)
 * @method static \CourierHub\DTOs\OrderResponse createOrder(\CourierHub\DTOs\OrderData $order)
 * @method static \CourierHub\DTOs\TrackingResponse trackOrder(string $trackingId)
 * @method static \CourierHub\DTOs\CancelResponse cancelOrder(string $trackingId)
 * @method static \CourierHub\DTOs\PriceResponse calculatePrice(\CourierHub\DTOs\PriceCalculationData $data)
 * @method static \Illuminate\Support\Collection comparePrices(\CourierHub\DTOs\PriceCalculationData $data)
 * @method static \CourierHub\Contracts\CourierDriver cheapest(\CourierHub\DTOs\PriceCalculationData $data)
 * @method static \Illuminate\Support\Collection enabledDrivers()
 * @method static bool isEnabled(string $name)
 *
 * @see \CourierHub\CourierManager
 */
class Courier extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return CourierManager::class;
    }
}
