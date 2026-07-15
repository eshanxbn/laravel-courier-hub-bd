<?php

namespace CourierHub\DTOs;

class StoreData
{
    public function __construct(
        public string  $name,
        public string  $phone,
        public string  $address,
        public ?int    $city_id = null,
        public ?int    $zone_id = null,
        public ?int    $area_id = null,
        public ?string $hub_id = null,
    ) {}
}
