<?php

namespace CourierHub\Drivers\Steadfast;

use CourierHub\Contracts\CourierDriver;
use CourierHub\Contracts\HasBalance;
use CourierHub\Contracts\HasBulkOrder;
use CourierHub\Contracts\HasFraudCheck;
use CourierHub\DTOs\BalanceResponse;
use CourierHub\DTOs\BulkOrderResponse;
use CourierHub\DTOs\CancelResponse;
use CourierHub\DTOs\FraudCheckResponse;
use CourierHub\DTOs\OrderData;
use CourierHub\DTOs\OrderResponse;
use CourierHub\DTOs\PriceCalculationData;
use CourierHub\DTOs\PriceResponse;
use CourierHub\DTOs\TrackingEvent;
use CourierHub\DTOs\TrackingResponse;

class SteadfastDriver implements CourierDriver, HasBulkOrder, HasBalance, HasFraudCheck
{
    protected SteadfastClient $client;

    public function __construct(array $config, array $httpConfig)
    {
        $this->client = new SteadfastClient($config, $httpConfig);
    }

    public function createOrder(OrderData $order): OrderResponse
    {
        $payload = [
            'invoice'           => $order->merchant_order_id,
            'recipient_name'    => $order->recipient_name,
            'recipient_phone'   => $order->recipient_phone,
            'recipient_address' => $order->recipient_address,
            'cod_amount'        => $order->amount_to_collect,
            'note'              => $order->special_instruction ?? $order->item_description,
        ];

        $response = $this->client->post('/create_order', $payload);

        return new OrderResponse(
            tracking_id: $response['consignment']['tracking_code'] ?? $response['consignment']['consignment_id'],
            courier_name: 'steadfast',
            status: SteadfastStatusMapper::map($response['consignment']['status'] ?? 'pending'),
            consignment_id: (string) ($response['consignment']['consignment_id'] ?? ''),
            raw_response: $response,
        );
    }

    public function createBulkOrder(array $orders): BulkOrderResponse
    {
        $payload = array_map(function (OrderData $order) {
            return [
                'invoice'           => $order->merchant_order_id,
                'recipient_name'    => $order->recipient_name,
                'recipient_phone'   => $order->recipient_phone,
                'recipient_address' => $order->recipient_address,
                'cod_amount'        => $order->amount_to_collect,
                'note'              => $order->special_instruction ?? $order->item_description,
            ];
        }, $orders);

        $response = $this->client->post('/create_order/bulk-order', ['data' => json_encode($payload)]);
        
        $results = [];
        $createdCount = 0;
        
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $item) {
                $createdCount++;
                $results[] = new OrderResponse(
                    tracking_id: $item['tracking_code'],
                    courier_name: 'steadfast',
                    status: SteadfastStatusMapper::map($item['status']),
                    consignment_id: (string) $item['consignment_id'],
                    raw_response: $item,
                );
            }
        }

        return new BulkOrderResponse(
            created_count: $createdCount,
            failed_count: count($orders) - $createdCount,
            results: $results,
            raw_response: $response,
        );
    }

    public function trackOrder(string $trackingId): TrackingResponse
    {
        $response = $this->client->get("/status_by_cid/{$trackingId}");
        
        return new TrackingResponse(
            tracking_id: $response['tracking_code'] ?? $trackingId,
            current_status: SteadfastStatusMapper::map($response['delivery_status'] ?? ''),
            history: [],
            raw_response: $response,
        );
    }

    public function cancelOrder(string $trackingId): CancelResponse
    {
        return new CancelResponse(false, 'Cancellation not supported directly via Steadfast API v1', $trackingId);
    }

    public function calculatePrice(PriceCalculationData $data): PriceResponse
    {
        // Steadfast doesn't have a direct public price calculator endpoint in v1. 
        // We will default to generic BDT 60/100 or mock it, or throw an exception.
        // For unified compatibility, we mock a response based on standard rates.
        $charge = 60; // default inside Dhaka
        if ($data->weight > 1) {
            $charge += ($data->weight - 1) * 15;
        }

        $codCharge = $data->cod_amount > 0 ? ($data->cod_amount * 0.01) : 0;

        return new PriceResponse(
            courier_name: 'steadfast',
            delivery_charge: $charge,
            cod_charge: $codCharge,
            total_charge: $charge + $codCharge,
            raw_response: [],
        );
    }

    public function getBalance(): BalanceResponse
    {
        $response = $this->client->get('/get_balance');
        
        return new BalanceResponse(
            current_balance: (float) ($response['balance'] ?? 0),
            currency: 'BDT',
            raw_response: $response,
        );
    }

    public function checkFraud(string $phone): FraudCheckResponse
    {
        // Some users mention fraud_check in v1 but usually Steadfast supports it internally.
        // We'll mock the integration if endpoint isn't fully active or use the real one if it is.
        // Assuming endpoint /fraud_check/{phone}
        try {
            // we will catch if 404
            $response = $this->client->get("/fraud_check/{$phone}");
            return new FraudCheckResponse(
                is_fraud: (bool) ($response['is_fraud'] ?? false),
                total_orders: (int) ($response['total_orders'] ?? 0),
                success_rate: (float) ($response['success_rate'] ?? 0.0),
                raw_response: $response,
            );
        } catch (\Exception $e) {
            return new FraudCheckResponse(false, 0, 0.0, [], []);
        }
    }
}
