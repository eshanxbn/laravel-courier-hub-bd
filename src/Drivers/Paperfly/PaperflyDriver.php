<?php

namespace CourierHub\Drivers\Paperfly;

use CourierHub\Contracts\CourierDriver;
use CourierHub\DTOs\CancelResponse;
use CourierHub\DTOs\OrderData;
use CourierHub\DTOs\OrderResponse;
use CourierHub\DTOs\TrackingResponse;

class PaperflyDriver implements CourierDriver
{
    protected PaperflyClient $client;

    public function __construct(array $config, array $httpConfig)
    {
        $this->client = new PaperflyClient($config, $httpConfig);
    }

    public function createOrder(OrderData $order): OrderResponse
    {
        $payload = [
            'merOrderRef'   => $order->merchant_order_id,
            'custName'      => $order->recipient_name,
            'custAddress'   => $order->recipient_address,
            'custPhone'     => $order->recipient_phone,
            'packagePrice'  => $order->amount_to_collect,
            'deliveryOption' => $order->delivery_type,
            'productSizeWeight' => $order->weight,
            'orderType'     => '1', // 1 for normal, etc.
        ];

        $response = $this->client->post('/OrderPlacement', $payload);

        return new OrderResponse(
            tracking_id: $response['tracking_number'] ?? '',
            courier_name: 'paperfly',
            status: PaperflyStatusMapper::map('pending'),
            consignment_id: $response['tracking_number'] ?? '',
            raw_response: $response,
        );
    }

    public function trackOrder(string $trackingId): TrackingResponse
    {
        $response = $this->client->get("/tracking/{$trackingId}");
        
        $current = 'pending';
        if (isset($response['status'])) {
            $current = $response['status'];
        }

        return new TrackingResponse(
            tracking_id: $trackingId,
            current_status: PaperflyStatusMapper::map($current),
            history: [],
            raw_response: $response,
        );
    }

    public function cancelOrder(string $trackingId): CancelResponse
    {
        $response = $this->client->post("/cancel/{$trackingId}");
        $success = ($response['success'] ?? false) == true;

        return new CancelResponse(
            success: $success,
            message: $response['message'] ?? '',
            tracking_id: $trackingId,
            raw_response: $response,
        );
    }
}
