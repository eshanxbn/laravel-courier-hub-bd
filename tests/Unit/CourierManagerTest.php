<?php

namespace CourierHub\Tests\Unit;

use CourierHub\CourierManager;
use CourierHub\DTOs\PriceCalculationData;
use CourierHub\Exceptions\CourierDisabledException;
use CourierHub\Tests\TestCase;
use Illuminate\Support\Facades\Http;

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

    public function test_it_can_compare_prices()
    {
        Http::fake([
            'mock.pathao.test/aladdin/api/v1/merchant/price-plan' => Http::response(['data' => ['price' => 120]]),
            'mock.pathao.test/aladdin/api/v1/issue-token' => Http::response(['access_token' => 'mock_token']),
            // Steadfast uses fixed mocked price in our driver currently (60 + cod)
        ]);

        $data = new PriceCalculationData(weight: 1, cod_amount: 1000);
        $prices = $this->manager->comparePrices($data);

        $this->assertCount(2, $prices);
        
        // Steadfast should be 60 + 10 = 70
        // Pathao should be 120 + 10 = 130
        $this->assertEquals('steadfast', $prices->first()->courier_name);
        $this->assertEquals(70, $prices->first()->total_charge);
        
        $this->assertEquals('pathao', $prices->last()->courier_name);
        $this->assertEquals(130, $prices->last()->total_charge);
    }
}
