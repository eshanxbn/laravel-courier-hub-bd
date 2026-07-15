<?php

namespace CourierHub\Contracts;

interface HasAreaLookup
{
    /**
     * Get all cities/districts.
     *
     * @return \CourierHub\DTOs\AreaData[]
     */
    public function getCities(): array;

    /**
     * Get zones/thanas for a specific city.
     *
     * @return \CourierHub\DTOs\AreaData[]
     */
    public function getZones(int $cityId): array;

    /**
     * Get areas for a specific zone.
     *
     * @return \CourierHub\DTOs\AreaData[]
     */
    public function getAreas(int $zoneId): array;
}
