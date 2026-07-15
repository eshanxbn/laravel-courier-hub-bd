<?php

namespace CourierHub\DTOs;

class PriceCalculationData
{
    public function __construct(
        public float   $weight,
        public float   $cod_amount,
        public ?string $from_area = null,
        public ?string $to_area = null,
        public string  $delivery_type = 'normal',
        public string  $item_type = 'parcel',
        public ?int    $store_id = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            weight: (float) $data['weight'],
            cod_amount: (float) ($data['cod_amount'] ?? 0.0),
            from_area: $data['from_area'] ?? null,
            to_area: $data['to_area'] ?? null,
            delivery_type: $data['delivery_type'] ?? 'normal',
            item_type: $data['item_type'] ?? 'parcel',
            store_id: isset($data['store_id']) ? (int) $data['store_id'] : null,
        );
    }
}
