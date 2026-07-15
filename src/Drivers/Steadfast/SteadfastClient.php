<?php

namespace CourierHub\Drivers\Steadfast;

use CourierHub\Exceptions\CourierApiException;
use CourierHub\Exceptions\InvalidConfigurationException;
use Illuminate\Support\Facades\Http;

class SteadfastClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $secretKey;
    protected int $timeout;
    protected int $retry;
    protected int $retryDelay;

    public function __construct(array $config, array $httpConfig)
    {
        if (empty($config['api_key']) || empty($config['secret_key'])) {
            throw new InvalidConfigurationException("Steadfast credentials (api_key, secret_key) are not fully configured.");
        }

        $this->apiKey = $config['api_key'];
        $this->secretKey = $config['secret_key'];
        $this->baseUrl = $config['base_url'];
        
        $this->timeout = $httpConfig['timeout'] ?? 30;
        $this->retry = $httpConfig['retry'] ?? 3;
        $this->retryDelay = $httpConfig['retry_delay'] ?? 100;
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('get', $endpoint, $query);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('post', $endpoint, $data);
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
            ])
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retry, $this->retryDelay)
            ->{$method}($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new CourierApiException(
                "Steadfast API Error: " . $response->body(),
                $response->status(),
                null,
                $response->json() ?? []
            );
        }

        return $response->json();
    }
}
