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

    public function test_it_reports_driver_capabilities()
    {
        $this->assertTrue($this->manager->supports('webhook', 'pathao'));
        $this->assertTrue($this->manager->supports('areas', 'pathao'));
        $this->assertTrue($this->manager->supports('webhook', 'steadfast'));
        $this->assertFalse($this->manager->supports('areas', 'steadfast'));
    }

    public function test_order_data_accepts_location_ids()
    {
        $order = \CourierHub\DTOs\OrderData::from([
            'recipient_name' => 'John',
            'recipient_phone' => '01712345678',
            'recipient_address' => 'Dhaka',
            'amount_to_collect' => 100,
            'weight' => 1,
            'merchant_order_id' => 'INV-1',
            'city_id' => 1,
            'zone_id' => 2,
            'area_id' => 3,
            'store_id' => 9,
        ]);

        $this->assertSame(1, $order->city_id);
        $this->assertSame(2, $order->zone_id);
        $this->assertSame(3, $order->area_id);
        $this->assertSame(9, $order->store_id);
    }
}
