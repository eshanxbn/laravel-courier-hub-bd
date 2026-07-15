<?php

namespace CourierHub\Drivers\RedX;

use CourierHub\Exceptions\CourierApiException;
use CourierHub\Exceptions\InvalidConfigurationException;
use Illuminate\Support\Facades\Http;

class RedXClient
{
    protected string $baseUrl;
    protected string $accessToken;
    protected int $timeout;
    protected int $retry;
    protected int $retryDelay;

    public function __construct(array $config, array $httpConfig)
    {
        if (empty($config['access_token'])) {
            throw new InvalidConfigurationException("RedX access_token is not configured.");
        }

        $this->accessToken = $config['access_token'];
        $this->baseUrl = $config['sandbox'] ? $config['base_url']['sandbox'] : $config['base_url']['production'];
        
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
        $response = Http::withToken($this->accessToken)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retry, $this->retryDelay)
            ->{$method}($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new CourierApiException(
                "RedX API Error: " . $response->body(),
                $response->status(),
                null,
                $response->json() ?? []
            );
        }

        return $response->json();
    }
}
