<?php

namespace CourierHub\Http\Controllers;

use CourierHub\Contracts\HasWebhook;
use CourierHub\Events\CourierWebhookReceived;
use CourierHub\Facades\Courier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController
{
    public function handle(Request $request, string $provider): Response
    {
        if (!Courier::isEnabled($provider)) {
            return response('Courier driver is disabled', 400);
        }

        $driver = Courier::driver($provider);
        
        if ($driver instanceof HasWebhook) {
            // Optional: You can handle signature verification directly here if not using middleware,
            // but the driver interface handles the validation logic.
            if (!$driver->validateWebhook($request)) {
                return response('Invalid webhook signature', 403);
            }

            $event = $driver->parseWebhook($request);
            
            // Dispatch the normalized event
            event(new CourierWebhookReceived($event));
        }
        
        return response('OK', 200);
    }
}
