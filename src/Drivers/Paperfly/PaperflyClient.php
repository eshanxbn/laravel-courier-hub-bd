<?php

namespace CourierHub\Drivers\Paperfly;

use CourierHub\Exceptions\CourierApiException;
use CourierHub\Exceptions\InvalidConfigurationException;
use Illuminate\Support\Facades\Http;

class PaperflyClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $apiKey;
    protected int $timeout;
    protected int $retry;
    protected int $retryDelay;

    public function __construct(array $config, array $httpConfig)
    {
        if (empty($config['username']) || empty($config['password'])) {
            throw new InvalidConfigurationException("Paperfly credentials are not fully configured.");
        }

        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->apiKey = $config['api_key'] ?? '';
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
        // Paperfly uses Basic Auth (usually) or token based on username/password in headers
        $response = Http::withBasicAuth($this->username, $this->password)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retry, $this->retryDelay)
            ->{$method}($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new CourierApiException(
                "Paperfly API Error: " . $response->body(),
                $response->status(),
                null,
                $response->json() ?? []
            );
        }

        return $response->json();
    }
}
