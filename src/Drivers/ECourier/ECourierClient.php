<?php

namespace CourierHub\Drivers\ECourier;

use CourierHub\Exceptions\CourierApiException;
use CourierHub\Exceptions\InvalidConfigurationException;
use Illuminate\Support\Facades\Http;

class ECourierClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiSecret;
    protected string $userId;
    protected int $timeout;
    protected int $retry;
    protected int $retryDelay;

    public function __construct(array $config, array $cacheConfig, array $httpConfig)
    {
        if (empty($config['api_key']) || empty($config['api_secret']) || empty($config['user_id'])) {
            throw new InvalidConfigurationException("eCourier credentials are not fully configured.");
        }

        $this->apiKey = $config['api_key'];
        $this->apiSecret = $config['api_secret'];
        $this->userId = $config['user_id'];
        $this->baseUrl = $config['sandbox'] ? $config['base_url']['sandbox'] : $config['base_url']['production'];
        
        $this->timeout = $httpConfig['timeout'] ?? 30;
        $this->retry = $httpConfig['retry'] ?? 3;
        $this->retryDelay = $httpConfig['retry_delay'] ?? 100;
    }

    public function post(string $endpoint, array $data = []): array
    {
        $response = Http::withHeaders([
                'API-KEY' => $this->apiKey,
                'API-SECRET' => $this->apiSecret,
                'USER-ID' => $this->userId,
            ])
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retry, $this->retryDelay)
            ->post($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new CourierApiException(
                "eCourier API Error: " . $response->body(),
                $response->status(),
                null,
                $response->json() ?? []
            );
        }

        return $response->json();
    }
}
