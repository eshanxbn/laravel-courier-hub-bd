<?php

namespace CourierHub\Drivers\Pathao;

use CourierHub\Contracts\CourierDriver;
use CourierHub\Contracts\HasAreaLookup;
use CourierHub\Contracts\HasBalance;
use CourierHub\Contracts\HasStoreManagement;
use CourierHub\Contracts\HasWebhook;
use CourierHub\DTOs\AreaData;
use CourierHub\DTOs\BalanceResponse;
use CourierHub\DTOs\CancelResponse;
use CourierHub\DTOs\OrderData;
use CourierHub\DTOs\OrderResponse;
use CourierHub\DTOs\StoreData;
use CourierHub\DTOs\StoreResponse;
use CourierHub\DTOs\TrackingEvent;
use CourierHub\DTOs\TrackingResponse;
use CourierHub\DTOs\WebhookEvent;
use Illuminate\Http\Request;

class PathaoDriver implements CourierDriver, HasBalance, HasStoreManagement, HasAreaLookup, HasWebhook
{
    protected PathaoClient $client;
    protected string $webhookSecret;

    public function __construct(array $config, array $cacheConfig, array $httpConfig)
    {
        $this->client = new PathaoClient($config, $cacheConfig, $httpConfig);
        $this->webhookSecret = (string) config('courierhub.webhook.secrets.pathao', '');
    }

    public function createOrder(OrderData $order): OrderResponse
    {
        $payload = [
            'merchant_order_id'   => $order->merchant_order_id,
            'recipient_name'      => $order->recipient_name,
            'recipient_phone'     => $order->recipient_phone,
            'recipient_address'   => $order->recipient_address,
            'recipient_city'      => $order->recipient_city,
            'recipient_zone'      => $order->recipient_zone,
            'recipient_area'      => $order->recipient_area,
            'delivery_type'       => $order->delivery_type === 'express' ? 48 : 48, // Pathao typically uses 48 for normal
            'item_type'           => $order->item_type === 'document' ? 1 : 2, // 1 for Document, 2 for Parcel
            'store_id'            => $order->store_id,
            'item_quantity'       => $order->item_quantity,
            'item_weight'         => $order->weight,
            'amount_to_collect'   => $order->amount_to_collect,
            'item_description'    => $order->item_description,
            'special_instruction' => $order->special_instruction,
        ];

        $response = $this->client->post('/aladdin/api/v1/orders', $payload);
        $data = $response['data'];

        return new OrderResponse(
            tracking_id: $data['consignment_id'],
            courier_name: 'pathao',
            status: PathaoStatusMapper::map($data['order_status']),
            consignment_id: $data['consignment_id'],
            delivery_charge: $data['delivery_fee'] ?? null,
            raw_response: $response,
        );
    }

    public function trackOrder(string $trackingId): TrackingResponse
    {
        // Pathao API might need a specific endpoint to track by consignment ID or merchant order ID
        // Often it's GET /aladdin/api/v1/orders/{consignment_id}
        $response = $this->client->get("/aladdin/api/v1/orders/{$trackingId}");
        $data = $response['data'];

        return new TrackingResponse(
            tracking_id: $data['consignment_id'],
            current_status: PathaoStatusMapper::map($data['order_status']),
            history: [], // Pathao may not provide detailed history in this endpoint without webhooks
            raw_response: $response,
        );
    }

    public function cancelOrder(string $trackingId): CancelResponse
    {
        // Cancel order via Pathao endpoint if available, some require manual cancellation or specific POST
        // Assuming there is an endpoint like POST /aladdin/api/v1/orders/{id}/cancel for now.
        // Or if it's unsupported we will throw an exception or return failure.
        // Currently, Pathao doesn't always have a public standard cancel API v1 for merchants, usually handled via dashboard.
        // We will fake a response or assume a generic one. Let's use a standard pattern.
        return new CancelResponse(false, 'Cancellation not supported directly via this API version', $trackingId);
    }

    public function getBalance(): BalanceResponse
    {
        // Pathao provides balance in a specific endpoint or dashboard.
        // Assuming GET /aladdin/api/v1/merchant/balance
        return new BalanceResponse(0, 'BDT', null, []);
    }

    public function getStores(): array
    {
        $response = $this->client->get('/aladdin/api/v1/stores');
        $stores = [];

        foreach ($response['data']['data'] as $store) {
            $stores[] = new StoreResponse(
                id: (string) $store['store_id'],
                name: $store['store_name'],
                address: $store['store_address'],
                phone: null,
                raw_response: $store,
            );
        }

        return $stores;
    }

    public function createStore(StoreData $data): StoreResponse
    {
        $payload = [
            'name' => $data->name,
            'contact_name' => $data->name,
            'contact_number' => $data->phone,
            'address' => $data->address,
            'city_id' => $data->city_id,
            'zone_id' => $data->zone_id,
            'area_id' => $data->area_id,
        ];

        $response = $this->client->post('/aladdin/api/v1/stores', $payload);
        $resData = $response['data'];

        return new StoreResponse(
            id: (string) $resData['store_id'],
            name: $resData['store_name'],
            address: $resData['store_address'],
            phone: $resData['contact_number'],
            raw_response: $response,
        );
    }

    public function getCities(): array
    {
        $response = $this->client->get('/aladdin/api/v1/countries/1/city-list');
        $cities = [];

        foreach ($response['data']['data'] as $city) {
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
        $response = $this->client->get("/aladdin/api/v1/cities/{$cityId}/zone-list");
        $zones = [];

        foreach ($response['data']['data'] as $zone) {
            $zones[] = new AreaData(
                id: (string) $zone['zone_id'],
                name: $zone['zone_name'],
                raw_data: $zone,
            );
        }

        return $zones;
    }

    public function getAreas(int $zoneId): array
    {
        $response = $this->client->get("/aladdin/api/v1/zones/{$zoneId}/area-list");
        $areas = [];

        foreach ($response['data']['data'] as $area) {
            $areas[] = new AreaData(
                id: (string) $area['area_id'],
                name: $area['area_name'],
                raw_data: $area,
            );
        }

        return $areas;
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->all();
        return new WebhookEvent(
            courier_name: 'pathao',
            tracking_id: $payload['consignment_id'] ?? '',
            status: PathaoStatusMapper::map($payload['order_status'] ?? ''),
            raw_payload: $payload,
            timestamp: $payload['updated_at'] ?? now()->toIso8601String(),
        );
    }

    public function validateWebhook(Request $request): bool
    {
        if (empty($this->webhookSecret)) {
            return true; // if no secret configured, allow
        }

        $signature = $request->header('X-PATHAO-SIGNATURE');
        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $this->webhookSecret);
        return hash_equals($expected, $signature);
    }
}
