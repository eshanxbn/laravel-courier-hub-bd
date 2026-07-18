<?php

namespace CourierHub\Http\Controllers;

use CourierHub\Contracts\HasWebhook;
use CourierHub\Events\CourierWebhookReceived;
use CourierHub\Facades\Courier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function handle(Request $request, string $provider): Response
    {
        if (!Courier::isEnabled($provider)) {
            return response('Courier driver is disabled', 400);
        }

        $driver = Courier::driver($provider);

        if (!$driver instanceof HasWebhook) {
            Log::warning("CourierHub: driver [{$provider}] received a webhook but does not implement HasWebhook.");

            return response('Webhook not supported for this courier', 400);
        }

        if (!$driver->validateWebhook($request)) {
            return response('Invalid webhook signature', 403);
        }

        $event = $driver->parseWebhook($request);

        event(new CourierWebhookReceived($event));

        return response('OK', 200);
    }
}
