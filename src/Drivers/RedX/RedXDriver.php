<?php

namespace CourierHub\Drivers\RedX;

use CourierHub\Contracts\CourierDriver;
use CourierHub\Contracts\HasStoreManagement;
use CourierHub\Contracts\HasWebhook;
use CourierHub\DTOs\CancelResponse;
use CourierHub\DTOs\OrderData;
use CourierHub\DTOs\OrderResponse;
use CourierHub\DTOs\StoreData;
use CourierHub\DTOs\StoreResponse;
use CourierHub\DTOs\TrackingEvent;
use CourierHub\DTOs\TrackingResponse;
use CourierHub\DTOs\WebhookEvent;
use Illuminate\Http\Request;

class RedXDriver implements CourierDriver, HasStoreManagement, HasWebhook
{
    protected RedXClient $client;
    protected string $webhookSecret;

    public function __construct(array $config, array $httpConfig)
    {
        $this->client = new RedXClient($config, $httpConfig);
        $this->webhookSecret = config('courierhub.webhook.secrets.redx', '');
    }

    public function createOrder(OrderData $order): OrderResponse
    {
        $payload = [
            'customer_name'          => $order->recipient_name,
            'customer_phone'         => $order->recipient_phone,
            'delivery_area'          => $order->recipient_area ?? $order->recipient_city ?? 'N/A',
            'delivery_area_id'       => $order->area_id ?? (int) ($order->recipient_area ?? 0),
            'customer_address'       => $order->recipient_address,
            'merchant_invoice_id'    => $order->merchant_order_id,
            'cash_collection_amount' => $order->amount_to_collect,
            'parcel_weight'          => (int) round($order->weight * 1000), // RedX expects grams
            'instruction'            => $order->special_instruction ?? '',
            'value'                  => $order->amount_to_collect,
        ];

        if ($order->store_id) {
            $payload['pickup_store_id'] = $order->store_id;
        }

        $response = $this->client->post('/parcel', $payload);
        $data = $response['parcel'] ?? [];

        return new OrderResponse(
            tracking_id: (string) ($data['tracking_id'] ?? ''),
            courier_name: 'redx',
            status: RedXStatusMapper::map('pending'),
            consignment_id: isset($data['tracking_id']) ? (string) $data['tracking_id'] : null,
            raw_response: $response,
        );
    }

    public function trackOrder(string $trackingId): TrackingResponse
    {
        $response = $this->client->get("/parcel/track/{$trackingId}");
        
        // Find current status from history or response
        $current = 'pending';
        $history = [];
        if (isset($response['tracking_history']) && is_array($response['tracking_history'])) {
            $latest = end($response['tracking_history']);
            $current = $latest['status'] ?? 'pending';
            
            foreach ($response['tracking_history'] as $event) {
                $history[] = new TrackingEvent(
                    status: RedXStatusMapper::map($event['status']),
                    timestamp: $event['date'] ?? now()->toIso8601String(),
                    description: $event['message'] ?? null,
                );
            }
        }

        return new TrackingResponse(
            tracking_id: $trackingId,
            current_status: RedXStatusMapper::map($current),
            history: $history,
            raw_response: $response,
        );
    }

    public function cancelOrder(string $trackingId): CancelResponse
    {
        return new CancelResponse(false, 'Cancellation not supported via RedX public API', $trackingId);
    }

    public function getStores(): array
    {
        $response = $this->client->get('/pickup/stores');
        $stores = [];

        foreach ($response['pickup_stores'] ?? [] as $store) {
            $stores[] = new StoreResponse(
                id: (string) $store['id'],
                name: $store['name'],
                address: $store['address'],
                phone: $store['phone'] ?? null,
                raw_response: $store,
            );
        }

        return $stores;
    }

    public function createStore(StoreData $data): StoreResponse
    {
        $payload = [
            'name' => $data->name,
            'phone' => $data->phone,
            'address' => $data->address,
            'area_id' => $data->area_id,
        ];

        $response = $this->client->post('/pickup/stores', $payload);
        $resData = $response['pickup_store'] ?? [];

        return new StoreResponse(
            id: (string) ($resData['id'] ?? ''),
            name: $resData['name'] ?? '',
            address: $resData['address'] ?? '',
            phone: $resData['phone'] ?? '',
            raw_response: $response,
        );
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->all();

        return new WebhookEvent(
            courier_name: 'redx',
            tracking_id: (string) ($payload['tracking_id'] ?? ''),
            status: RedXStatusMapper::map((string) ($payload['status'] ?? '')),
            raw_payload: $payload,
            timestamp: now()->toIso8601String(),
            merchant_order_id: isset($payload['merchant_invoice_id'])
                ? (string) $payload['merchant_invoice_id']
                : (isset($payload['invoice_number']) ? (string) $payload['invoice_number'] : null),
            consignment_id: isset($payload['tracking_id']) ? (string) $payload['tracking_id'] : null,
        );
    }

    public function validateWebhook(Request $request): bool
    {
        if (empty($this->webhookSecret)) {
            return true;
        }

        $signature = $request->header('X-Redx-Signature');
        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $this->webhookSecret);
        return hash_equals($expected, $signature);
    }
}
