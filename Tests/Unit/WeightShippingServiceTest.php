<?php

namespace Modules\WeightShipping\Tests\Unit;

use App\Models\Currency;
use App\Models\InstalledModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once dirname(__DIR__, 2) . '/Services/WeightShippingService.php';

use Modules\WeightShipping\Services\WeightShippingService;

class WeightShippingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_weight_shipping_uses_cart_weight_and_free_shipping_threshold(): void
    {
        Currency::query()->create([
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => 'EUR',
            'symbol_left' => 'EUR ',
            'symbol_right' => null,
            'decimal_places' => 2,
            'exchange_rate' => 1,
            'is_base' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $module = new InstalledModule([
            'slug' => 'weight-shipping',
            'name' => 'Weight Shipping',
            'settings' => [
                'enabled' => true,
                'title' => 'Weight Shipping',
                'description' => 'Weight-based delivery',
                'weight_unit' => 'kg',
                'calculation_type' => 'per_unit',
                'base_cost' => 4.00,
                'cost_per_unit' => 2.50,
                'free_shipping_threshold' => 100,
                'max_weight' => 30,
                'handling_fee' => 1.00,
                'sort_order' => 7,
            ],
        ]);

        $service = new WeightShippingService($module);

        $this->assertSame(10.00, $service->calculateCost(50, 2));
        $this->assertSame(0.00, $service->calculateCost(120, 2));
        $this->assertSame(-1.0, $service->calculateCost(50, 31));
        $this->assertTrue($service->hasFreeShipping(100));
        $this->assertSame(10.0, $service->getAmountForFreeShipping(90));

        $method = $service->getShippingMethod(50, 2);

        $this->assertSame(7, $method['sort_order']);
        $this->assertSame(10.00, $method['cost']);
        $this->assertSame(2.0, $method['weight']);
        $this->assertSame('kg', $method['weight_unit']);
        $this->assertIsString($method['formatted_cost']);
        $this->assertNull($service->getShippingMethod(50, 31));
    }

    public function test_weight_shipping_supports_tiered_rates(): void
    {
        $module = new InstalledModule([
            'slug' => 'weight-shipping',
            'name' => 'Weight Shipping',
            'settings' => [
                'enabled' => true,
                'calculation_type' => 'tiered',
                'rates' => [
                    ['min_weight' => 0, 'max_weight' => 2, 'cost' => 6.50],
                    ['min_weight' => 2.01, 'max_weight' => 5, 'cost' => 9.75],
                ],
                'handling_fee' => 0,
            ],
        ]);

        $service = new WeightShippingService($module);

        $this->assertSame(6.50, $service->calculateCost(50, 2));
        $this->assertSame(9.75, $service->calculateCost(50, 4));
        $this->assertSame(9.75, $service->calculateCost(50, 7));
    }
}
