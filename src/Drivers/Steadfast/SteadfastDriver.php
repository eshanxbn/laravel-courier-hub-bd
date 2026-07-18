<?php

namespace CourierHub\Drivers\Steadfast;

use CourierHub\Contracts\CourierDriver;
use CourierHub\Contracts\HasBalance;
use CourierHub\Contracts\HasBulkOrder;
use CourierHub\Contracts\HasFraudCheck;
use CourierHub\Contracts\HasWebhook;
use CourierHub\DTOs\BalanceResponse;
use CourierHub\DTOs\BulkOrderResponse;
use CourierHub\DTOs\CancelResponse;
use CourierHub\DTOs\FraudCheckResponse;
use CourierHub\DTOs\OrderData;
use CourierHub\DTOs\OrderResponse;
use CourierHub\DTOs\TrackingResponse;
use CourierHub\DTOs\WebhookEvent;
use Illuminate\Http\Request;

class SteadfastDriver implements CourierDriver, HasBulkOrder, HasBalance, HasFraudCheck, HasWebhook
{
    protected SteadfastClient $client;
    protected string $webhookSecret;

    public function __construct(array $config, array $httpConfig)
    {
        $this->client = new SteadfastClient($config, $httpConfig);
        $this->webhookSecret = (string) config('courierhub.webhook.secrets.steadfast', '');
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
        $consignment = $response['consignment'] ?? [];

        return new OrderResponse(
            tracking_id: (string) ($consignment['tracking_code'] ?? $consignment['consignment_id'] ?? ''),
            courier_name: 'steadfast',
            status: SteadfastStatusMapper::map((string) ($consignment['status'] ?? 'pending')),
            consignment_id: isset($consignment['consignment_id']) ? (string) $consignment['consignment_id'] : null,
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
                    tracking_id: (string) ($item['tracking_code'] ?? ''),
                    courier_name: 'steadfast',
                    status: SteadfastStatusMapper::map((string) ($item['status'] ?? 'pending')),
                    consignment_id: isset($item['consignment_id']) ? (string) $item['consignment_id'] : null,
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
            tracking_id: (string) ($response['tracking_code'] ?? $trackingId),
            current_status: SteadfastStatusMapper::map((string) ($response['delivery_status'] ?? '')),
            history: [],
            raw_response: $response,
        );
    }

    public function cancelOrder(string $trackingId): CancelResponse
    {
        return new CancelResponse(false, 'Cancellation is not supported via Steadfast API v1', $trackingId);
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
        try {
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

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->all();

        return new WebhookEvent(
            courier_name: 'steadfast',
            tracking_id: (string) ($payload['tracking_code'] ?? $payload['consignment_id'] ?? ''),
            status: SteadfastStatusMapper::map((string) ($payload['status'] ?? $payload['delivery_status'] ?? '')),
            raw_payload: $payload,
            timestamp: now()->toIso8601String(),
            merchant_order_id: isset($payload['invoice']) ? (string) $payload['invoice'] : null,
            consignment_id: isset($payload['consignment_id']) ? (string) $payload['consignment_id'] : null,
        );
    }

    public function validateWebhook(Request $request): bool
    {
        if ($this->webhookSecret === '') {
            return true;
        }

        $signature = $request->header('X-Steadfast-Signature')
            ?? $request->header('X-Webhook-Signature');

        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $this->webhookSecret);

        return hash_equals($expected, $signature);
    }
}
