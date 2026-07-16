<?php

namespace CourierHub\Tests\Unit;

use CourierHub\CourierManager;
use CourierHub\Exceptions\CourierDisabledException;
use CourierHub\Tests\TestCase;

class CourierManagerTest extends TestCase
{
    protected CourierManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make(CourierManager::class);
    }

    public function test_it_returns_default_driver()
    {
        $this->assertEquals('pathao', $this->manager->getDefaultDriver());
    }

    public function test_it_throws_exception_if_driver_disabled()
    {
        $this->expectException(CourierDisabledException::class);
        $this->manager->driver('redx');
    }

    public function test_it_returns_enabled_drivers()
    {
        $enabled = $this->manager->enabledDrivers()->toArray();
        $this->assertContains('pathao', $enabled);
        $this->assertContains('steadfast', $enabled);
        $this->assertNotContains('redx', $enabled);
    }
}
