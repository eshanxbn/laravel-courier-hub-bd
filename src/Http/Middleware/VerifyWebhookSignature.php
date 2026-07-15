<?php

namespace CourierHub\Http\Middleware;

use Closure;
use CourierHub\Contracts\HasWebhook;
use CourierHub\Facades\Courier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next, string $provider = null): Response
    {
        // Extract provider from route if not explicitly passed
        $provider = $provider ?? $request->route('provider');

        if (!$provider || !Courier::isEnabled($provider)) {
            return response('Courier disabled or not found', 400);
        }

        $driver = Courier::driver($provider);

        if ($driver instanceof HasWebhook) {
            if (!$driver->validateWebhook($request)) {
                return response('Invalid webhook signature', 403);
            }
        }

        return $next($request);
    }
}
