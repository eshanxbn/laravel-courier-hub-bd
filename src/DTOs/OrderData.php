<?php

namespace CourierHub\DTOs;

class OrderData
{
    public function __construct(
        public string  $recipient_name,
        public string  $recipient_phone,
        public string  $recipient_address,
        public float   $amount_to_collect,     // COD amount (0 for prepaid)
        public float   $weight,                // in KG
        public string  $merchant_order_id,
        public ?string $recipient_city = null,
        public ?string $recipient_zone = null,
        public ?string $recipient_area = null,
        public ?string $item_description = null,
        public int     $item_quantity = 1,
        public string  $delivery_type = 'normal', // normal | express
        public string  $item_type = 'parcel',     // parcel | document
        public ?string $special_instruction = null,
        public ?int    $store_id = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            recipient_name: $data['recipient_name'],
            recipient_phone: $data['recipient_phone'],
            recipient_address: $data['recipient_address'],
            amount_to_collect: (float) $data['amount_to_collect'],
            weight: (float) $data['weight'],
            merchant_order_id: (string) $data['merchant_order_id'],
            recipient_city: $data['recipient_city'] ?? null,
            recipient_zone: $data['recipient_zone'] ?? null,
            recipient_area: $data['recipient_area'] ?? null,
            item_description: $data['item_description'] ?? null,
            item_quantity: (int) ($data['item_quantity'] ?? 1),
            delivery_type: $data['delivery_type'] ?? 'normal',
            item_type: $data['item_type'] ?? 'parcel',
            special_instruction: $data['special_instruction'] ?? null,
            store_id: isset($data['store_id']) ? (int) $data['store_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'recipient_name'      => $this->recipient_name,
            'recipient_phone'     => $this->recipient_phone,
            'recipient_address'   => $this->recipient_address,
            'amount_to_collect'   => $this->amount_to_collect,
            'weight'              => $this->weight,
            'merchant_order_id'   => $this->merchant_order_id,
            'recipient_city'      => $this->recipient_city,
            'recipient_zone'      => $this->recipient_zone,
            'recipient_area'      => $this->recipient_area,
            'item_description'    => $this->item_description,
            'item_quantity'       => $this->item_quantity,
            'delivery_type'       => $this->delivery_type,
            'item_type'           => $this->item_type,
            'special_instruction' => $this->special_instruction,
            'store_id'            => $this->store_id,
        ];
    }
}
