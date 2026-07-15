<?php

namespace CourierHub\Console;

use CourierHub\Facades\Courier;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class CourierStatusCommand extends Command
{
    protected $signature = 'courier:status';
    protected $description = 'Check the status and configuration of CourierHub drivers';

    public function handle(): int
    {
        $this->info('CourierHub Drivers Status');
        
        $couriers = config('courierhub.couriers', []);
        
        $rows = [];
        foreach ($couriers as $name => $config) {
            $enabled = ($config['enabled'] ?? false) ? '✅ Yes' : '❌ No';
            
            $sandboxStr = '—';
            if (isset($config['sandbox'])) {
                $sandboxStr = $config['sandbox'] ? '✅ Yes' : '❌ No';
            }
            
            $creds = $this->checkCredentials($name, $config);

            $rows[] = [
                $name,
                $enabled,
                $sandboxStr,
                $creds
            ];
        }

        $this->table(
            ['Courier', 'Enabled', 'Sandbox', 'Credentials'],
            $rows
        );

        return self::SUCCESS;
    }

    protected function checkCredentials(string $name, array $config): string
    {
        $required = match($name) {
            'pathao' => ['client_id', 'client_secret', 'username', 'password'],
            'steadfast' => ['api_key', 'secret_key'],
            'redx' => ['access_token'],
            'ecourier' => ['api_key', 'api_secret', 'user_id'],
            'paperfly' => ['username', 'password', 'api_key'],
            default => [],
        };

        if (empty($required)) {
            return '—';
        }

        $missing = [];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                $missing[] = $key;
            }
        }

        if (count($missing) > 0) {
            return '⚠️ Missing: ' . implode(', ', $missing);
        }

        return '✅ Configured';
    }
}
