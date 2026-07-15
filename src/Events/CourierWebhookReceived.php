<?php

namespace CourierHub\Events;

use CourierHub\DTOs\WebhookEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierWebhookReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WebhookEvent $webhook
    ) {}
}
