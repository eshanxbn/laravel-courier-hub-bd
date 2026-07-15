<?php

namespace CourierHub\Contracts;

use CourierHub\DTOs\StoreData;
use CourierHub\DTOs\StoreResponse;

interface HasStoreManagement
{
    /**
     * Get a list of stores/pickup locations.
     *
     * @return \CourierHub\DTOs\StoreResponse[]
     */
    public function getStores(): array;

    /**
     * Create a new store/pickup location.
     */
    public function createStore(StoreData $data): StoreResponse;
}
