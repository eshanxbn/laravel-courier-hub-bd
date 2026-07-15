<?php

namespace CourierHub\Tests;

use CourierHub\CourierHubServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            CourierHubServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        
        // Mock configurations for tests
        config()->set('courierhub.couriers.pathao.enabled', true);
        config()->set('courierhub.couriers.pathao.client_id', 'test_client');
        config()->set('courierhub.couriers.pathao.client_secret', 'test_secret');
        config()->set('courierhub.couriers.pathao.username', 'test_user');
        config()->set('courierhub.couriers.pathao.password', 'test_pass');
        config()->set('courierhub.couriers.pathao.base_url.sandbox', 'https://mock.pathao.test');

        config()->set('courierhub.couriers.steadfast.enabled', true);
        config()->set('courierhub.couriers.steadfast.api_key', 'test_api_key');
        config()->set('courierhub.couriers.steadfast.secret_key', 'test_secret_key');
        config()->set('courierhub.couriers.steadfast.base_url', 'https://mock.steadfast.test');
        
        config()->set('courierhub.couriers.redx.enabled', false); // disabled by default
    }
}
