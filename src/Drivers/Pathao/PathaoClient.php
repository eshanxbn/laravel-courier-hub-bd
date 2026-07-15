<?php

namespace CourierHub\Drivers\Pathao;

use CourierHub\Exceptions\CourierApiException;
use CourierHub\Exceptions\InvalidConfigurationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PathaoClient
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected int $retry;
    protected int $retryDelay;
    
    public function __construct(array $config, array $cacheConfig, array $httpConfig)
    {
        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['username']) || empty($config['password'])) {
            throw new InvalidConfigurationException("Pathao credentials are not fully configured.");
        }

        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->username = $config['username'];
        $this->password = $config['password'];
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
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retry, $this->retryDelay)
            ->{$method}($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new CourierApiException(
                "Pathao API Error: " . $response->body(),
                $response->status(),
                null,
                $response->json() ?? []
            );
        }

        return $response->json();
    }

    protected function getAccessToken(): string
    {
        $cacheKey = 'courierhub_pathao_access_token';

        return Cache::remember($cacheKey, 86400 * 5, function () {
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->retry($this->retry, $this->retryDelay)
                ->post($this->baseUrl . '/aladdin/api/v1/issue-token', [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'username'      => $this->username,
                    'password'      => $this->password,
                    'grant_type'    => 'password',
                ]);

            if ($response->failed()) {
                throw new CourierApiException(
                    "Failed to get Pathao access token: " . $response->body(),
                    $response->status(),
                    null,
                    $response->json() ?? []
                );
            }

            return $response->json('access_token');
        });
    }
}
