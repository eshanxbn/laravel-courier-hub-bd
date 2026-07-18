# CourierHub

Unified Bangladesh courier integration for Laravel — Pathao, Steadfast, RedX, eCourier, Paperfly.

## Why CourierHub?

Most courier packages in Bangladesh support only a single courier, with completely different API methods, DTOs, and architectures.

CourierHub provides a unified `Manager/Driver` architecture (like Laravel's Cache or Filesystem) that normalizes **Order Booking**, **Tracking**, and **Webhooks** across the top 5 couriers in Bangladesh.

- ✅ **Switch couriers instantly**: `Courier::driver('pathao')->createOrder($data)`
- ✅ **Unified DTOs**: Same request/response objects for every courier
- ✅ **Normalized Webhooks**: A single event for all courier status updates

## Installation

```bash
composer require eshanxbn/laravel-courier-hub-bd
```

If the package is not yet available on Packagist with a tagged release, install from GitHub:

```bash
composer config repositories.courierhub vcs https://github.com/eshanxbn/laravel-courier-hub-bd
composer require eshanxbn/laravel-courier-hub-bd:dev-main
```

Publish the config:

```bash
php artisan vendor:publish --tag="courierhub-config"
```

## Configuration

Open `.env` and enable the couriers you want to use with their respective credentials.

```env
COURIER_DEFAULT=pathao

COURIER_PATHAO_ENABLED=true
COURIER_PATHAO_CLIENT_ID="your_client_id"
COURIER_PATHAO_CLIENT_SECRET="your_secret"
COURIER_PATHAO_USERNAME="your_email@example.com"
COURIER_PATHAO_PASSWORD="your_password"
COURIER_PATHAO_STORE_ID="your_store_id"

COURIER_STEADFAST_ENABLED=true
COURIER_STEADFAST_API_KEY="your_api_key"
COURIER_STEADFAST_SECRET_KEY="your_secret"
```

Run health check:

```bash
php artisan courier:status
```

## Usage

### Create an Order

```php
use CourierHub\Facades\Courier;
use CourierHub\DTOs\OrderData;

$order = OrderData::from([
    'merchant_order_id' => 'INV-1001',
    'recipient_name'    => 'John Doe',
    'recipient_phone'   => '01712345678',
    'recipient_address' => 'Mirpur 10, Dhaka',
    'amount_to_collect' => 1250, // COD amount
    'weight'            => 1.5,
    // Optional Pathao / RedX location IDs
    'city_id'           => 1,
    'zone_id'           => 2,
    'area_id'           => 3,
    'store_id'          => 10,
]);

// Uses default driver
$response = Courier::createOrder($order);

// Or specify driver
$response = Courier::driver('steadfast')->createOrder($order);

echo $response->tracking_id; // Unified tracking ID
echo $response->status->value; // CourierHub\Enums\CourierStatus
```

### Tracking

```php
$tracking = Courier::driver('pathao')->trackOrder('PATHAO-12345');
echo $tracking->current_status->value;
```

### Capability checks

```php
if (Courier::supports('areas', 'pathao')) {
    $cities = Courier::driver('pathao')->getCities();
}
```

### Webhooks

CourierHub provides a unified webhook endpoint: `POST /webhooks/courier/{provider}`.

Listen to the `CourierWebhookReceived` event in your application:

```php
use CourierHub\Events\CourierWebhookReceived;

Event::listen(CourierWebhookReceived::class, function (CourierWebhookReceived $event) {
    $courier = $event->webhook->courier_name;
    $trackingId = $event->webhook->tracking_id;
    $merchantOrderId = $event->webhook->merchant_order_id;
    $status = $event->webhook->status; // CourierStatus Enum

    // Update your DB
});
```

## License

MIT
