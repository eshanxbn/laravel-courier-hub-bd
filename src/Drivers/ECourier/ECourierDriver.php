<?php

namespace CourierHub\Drivers\ECourier;

use CourierHub\Contracts\CourierDriver;
use CourierHub\Contracts\HasAreaLookup;
use CourierHub\Contracts\HasPaymentStatus;
use CourierHub\DTOs\AreaData;
use CourierHub\DTOs\CancelResponse;
use CourierHub\DTOs\OrderData;
use CourierHub\DTOs\OrderResponse;
use CourierHub\DTOs\PaymentStatusResponse;
use CourierHub\DTOs\TrackingEvent;
use CourierHub\DTOs\TrackingResponse;

class ECourierDriver implements CourierDriver, HasAreaLookup, HasPaymentStatus
{
    protected ECourierClient $client;

    public function __construct(array $config, array $cacheConfig, array $httpConfig)
    {
        $this->client = new ECourierClient($config, $cacheConfig, $httpConfig);
    }

    public function createOrder(OrderData $order): OrderResponse
    {
        $payload = [
            'recipient_name'    => $order->recipient_name,
            'recipient_mobile'  => $order->recipient_phone,
            'recipient_city'    => $order->recipient_city,
            'recipient_thana'   => $order->recipient_zone,
            'recipient_area'    => $order->recipient_area,
            'recipient_address' => $order->recipient_address,
            'package_code'      => $order->merchant_order_id,
            'product_price'     => $order->amount_to_collect,
            'payment_method'    => $order->amount_to_collect > 0 ? 'COD' : 'MPAY',
            'product_weight'    => $order->weight,
            'special_instruction' => $order->special_instruction ?? '',
        ];

        $response = $this->client->post('/order-place', $payload);

        return new OrderResponse(
            tracking_id: $response['tracking'] ?? '',
            courier_name: 'ecourier',
            status: ECourierStatusMapper::map('pending'),
            consignment_id: $response['tracking'] ?? '',
            raw_response: $response,
        );
    }

    public function trackOrder(string $trackingId): TrackingResponse
    {
        $payload = ['tracking' => $trackingId];
        $response = $this->client->post('/order-tracking', $payload);
        
        $current = 'pending';
        $history = [];

        if (isset($response['data']) && is_array($response['data'])) {
            $latest = end($response['data']);
            $current = $latest['status'] ?? 'pending';

            foreach ($response['data'] as $event) {
                $history[] = new TrackingEvent(
                    status: ECourierStatusMapper::map($event['status']),
                    timestamp: $event['date'] ?? now()->toIso8601String(),
                    description: $event['comment'] ?? null,
                );
            }
        }

        return new TrackingResponse(
            tracking_id: $trackingId,
            current_status: ECourierStatusMapper::map($current),
            history: $history,
            raw_response: $response,
        );
    }

    public function cancelOrder(string $trackingId): CancelResponse
    {
        $payload = ['tracking' => $trackingId];
        $response = $this->client->post('/cancel-order', $payload);

        $success = ($response['status'] ?? 0) == 200;
        
        return new CancelResponse(
            success: $success,
            message: $response['message'] ?? '',
            tracking_id: $trackingId,
            raw_response: $response,
        );
    }

    public function getCities(): array
    {
        $response = $this->client->post('/city-list');
        $cities = [];

        foreach ($response['data'] ?? [] as $city) {
            $cities[] = new AreaData(
                id: (string) $city['city_id'],
                name: $city['city_name'],
                raw_data: $city,
            );
        }

        return $cities;
    }

    public function getZones(int $cityId): array
    {
        $response = $this->client->post('/thana-list', ['city_id' => $cityId]);
        $zones = [];

        foreach ($response['data'] ?? [] as $zone) {
            $zones[] = new AreaData(
                id: (string) $zone['thana_id'],
                name: $zone['thana_name'],
                raw_data: $zone,
            );
        }

        return $zones;
    }

    public function getAreas(int $zoneId): array
    {
        $response = $this->client->post('/postcode-list', ['thana_id' => $zoneId]);
        $areas = [];

        foreach ($response['data'] ?? [] as $area) {
            $areas[] = new AreaData(
                id: (string) $area['postcode_id'],
                name: $area['postcode_name'],
                postcode: $area['postcode'] ?? null,
                raw_data: $area,
            );
        }

        return $areas;
    }

    public function paymentStatus(string $trackingId): PaymentStatusResponse
    {
        $payload = ['tracking' => $trackingId];
        $response = $this->client->post('/payment-status', $payload);

        $status = $response['status'] ?? 'pending';
        
        return new PaymentStatusResponse(
            tracking_id: $trackingId,
            status: $status,
            paid_amount: (float) ($response['paid_amount'] ?? 0),
            pending_amount: (float) ($response['pending_amount'] ?? 0),
            payment_date: $response['payment_date'] ?? null,
            raw_response: $response,
        );
    }
}
