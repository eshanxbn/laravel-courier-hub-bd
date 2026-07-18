<?php

namespace CourierHub;

use CourierHub\Contracts\HasAreaLookup;
use CourierHub\Contracts\HasBalance;
use CourierHub\Contracts\HasBulkOrder;
use CourierHub\Contracts\HasFraudCheck;
use CourierHub\Contracts\HasPaymentStatus;
use CourierHub\Contracts\HasStoreManagement;
use CourierHub\Contracts\HasWebhook;
use CourierHub\Exceptions\CourierDisabledException;
use CourierHub\Exceptions\CourierNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Manager;

class CourierManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('courierhub.default', 'pathao');
    }

    /**
     * Override createDriver to check if the driver is enabled.
     */
    protected function createDriver($driver)
    {
        if (!$this->isEnabled($driver)) {
            throw new CourierDisabledException("The courier driver [{$driver}] is disabled or not configured.");
        }

        try {
            return parent::createDriver($driver);
        } catch (\InvalidArgumentException $e) {
            throw new CourierNotFoundException("Courier driver [{$driver}] is not supported.", 0, $e);
        }
    }

    /**
     * Check if a courier is enabled in the configuration.
     */
    public function isEnabled(string $name): bool
    {
        return (bool) $this->config->get("courierhub.couriers.{$name}.enabled", false);
    }

    /**
     * Get a collection of all enabled courier names.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function enabledDrivers(): Collection
    {
        $couriers = $this->config->get('courierhub.couriers', []);

        return collect($couriers)
            ->filter(fn ($config) => !empty($config['enabled']))
            ->keys();
    }

    /**
     * Whether the given (or default) driver supports a capability.
     *
     * Supported capabilities: webhook, areas, fraud, bulk, balance, stores, payment_status
     */
    public function supports(string $capability, ?string $driver = null): bool
    {
        $instance = $this->driver($driver);

        return match ($capability) {
            'webhook' => $instance instanceof HasWebhook,
            'areas' => $instance instanceof HasAreaLookup,
            'fraud' => $instance instanceof HasFraudCheck,
            'bulk' => $instance instanceof HasBulkOrder,
            'balance' => $instance instanceof HasBalance,
            'stores' => $instance instanceof HasStoreManagement,
            'payment_status' => $instance instanceof HasPaymentStatus,
            default => false,
        };
    }

    // Driver Factories

    protected function createPathaoDriver()
    {
        return new \CourierHub\Drivers\Pathao\PathaoDriver(
            $this->config->get('courierhub.couriers.pathao'),
            $this->config->get('courierhub.cache'),
            $this->config->get('courierhub.http')
        );
    }

    protected function createSteadfastDriver()
    {
        return new \CourierHub\Drivers\Steadfast\SteadfastDriver(
            $this->config->get('courierhub.couriers.steadfast'),
            $this->config->get('courierhub.http')
        );
    }

    protected function createRedxDriver()
    {
        return new \CourierHub\Drivers\RedX\RedXDriver(
            $this->config->get('courierhub.couriers.redx'),
            $this->config->get('courierhub.http')
        );
    }

    protected function createEcourierDriver()
    {
        return new \CourierHub\Drivers\ECourier\ECourierDriver(
            $this->config->get('courierhub.couriers.ecourier'),
            $this->config->get('courierhub.cache'),
            $this->config->get('courierhub.http')
        );
    }

    protected function createPaperflyDriver()
    {
        return new \CourierHub\Drivers\Paperfly\PaperflyDriver(
            $this->config->get('courierhub.couriers.paperfly'),
            $this->config->get('courierhub.http')
        );
    }
}
