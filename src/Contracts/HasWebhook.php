<?php

namespace CourierHub\Contracts;

use CourierHub\DTOs\WebhookEvent;
use Illuminate\Http\Request;

interface HasWebhook
{
    /**
     * Parse the incoming webhook request and return a normalized WebhookEvent.
     */
    public function parseWebhook(Request $request): WebhookEvent;

    /**
     * Validate the webhook request signature.
     */
    public function validateWebhook(Request $request): bool;
}
