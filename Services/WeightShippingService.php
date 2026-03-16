<?php

namespace Modules\WeightShipping\Services;

use App\Contracts\AbstractShippingMethod;
use App\Models\InstalledModule;

class WeightShippingService extends AbstractShippingMethod
{
    public static function defaultSettings(): array
    {
        return [
            'enabled' => true,
            'title' => self::translateSetting('default_title', 'Weight Based Shipping'),
            'description' => self::translateSetting('default_description', 'Shipping cost calculated by order weight'),
            'weight_unit' => 'kg',
            'calculation_type' => 'per_unit',
            'base_cost' => 0.00,
            'cost_per_unit' => 5.00,
            'free_shipping_threshold' => null,
            'max_weight' => null,
            'handling_fee' => 0.00,
            'rates' => [],
            'sort_order' => 0,
        ];
    }

    public function __construct(?InstalledModule $module = null)
    {
        if ($module === null) {
            $module = InstalledModule::query()->where('slug', 'weight-shipping')->first();
        }

        parent::__construct($module);

        $this->settings = array_replace_recursive(self::defaultSettings(), $this->settings);
    }

    public function getDeliveryType(): string
    {
        return self::DELIVERY_TYPE_ADDRESS;
    }

    public function getWeightUnit(): string
    {
        return (string) ($this->settings['weight_unit'] ?? 'kg');
    }

    public function isAvailable(float $cartWeight): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $maxWeight = $this->settings['max_weight'] ?? null;

        return $maxWeight === null || $maxWeight === '' || $cartWeight <= (float) $maxWeight;
    }

    public function calculateCost(float $cartTotal, float $cartWeight = 0, array $deliveryData = []): float
    {
        if (!$this->isEnabled()) {
            return 0.00;
        }

        if (!$this->isAvailable($cartWeight)) {
            return -1;
        }

        $freeThreshold = $this->settings['free_shipping_threshold'] ?? null;
        if ($freeThreshold !== null && $freeThreshold !== '' && $cartTotal >= (float) $freeThreshold) {
            return 0.00;
        }

        $baseCost = (float) ($this->settings['base_cost'] ?? 0);
        $handlingFee = (float) ($this->settings['handling_fee'] ?? 0);
        $calculationType = (string) ($this->settings['calculation_type'] ?? 'per_unit');

        $cost = $calculationType === 'tiered'
            ? $this->calculateTieredCost($cartWeight, (array) ($this->settings['rates'] ?? []))
            : $baseCost + ($cartWeight * (float) ($this->settings['cost_per_unit'] ?? 0));

        return round(max(0, $cost + $handlingFee), 2);
    }

    public function getShippingMethod(float $cartTotal = 0, float $cartWeight = 0): ?array
    {
        if (!$this->isAvailable($cartWeight)) {
            return null;
        }

        $cost = $this->calculateCost($cartTotal, $cartWeight);
        if ($cost < 0) {
            return null;
        }

        $data = parent::getShippingMethod($cartTotal, $cartWeight);

        if (!$data) {
            return null;
        }

        $data['weight'] = $cartWeight;
        $data['weight_unit'] = $this->getWeightUnit();

        return $data;
    }

    public function hasFreeShipping(float $cartTotal): bool
    {
        $threshold = $this->settings['free_shipping_threshold'] ?? null;

        return $threshold !== null && $threshold !== '' && $cartTotal >= (float) $threshold;
    }

    public function getAmountForFreeShipping(float $cartTotal): ?float
    {
        $threshold = $this->settings['free_shipping_threshold'] ?? null;

        if ($threshold === null || $threshold === '' || $cartTotal >= (float) $threshold) {
            return null;
        }

        return (float) $threshold - $cartTotal;
    }

    protected function calculateTieredCost(float $cartWeight, array $rates): float
    {
        if (empty($rates)) {
            return (float) ($this->settings['base_cost'] ?? 0);
        }

        $normalizedRates = collect($rates)
            ->filter(fn($rate) => is_array($rate))
            ->map(function (array $rate): array {
                return [
                    'min_weight' => (float) ($rate['min_weight'] ?? 0),
                    'max_weight' => array_key_exists('max_weight', $rate) && $rate['max_weight'] !== null && $rate['max_weight'] !== ''
                        ? (float) $rate['max_weight']
                        : null,
                    'cost' => (float) ($rate['cost'] ?? 0),
                ];
            })
            ->sortBy('min_weight')
            ->values()
            ->all();

        foreach ($normalizedRates as $rate) {
            $maxWeight = $rate['max_weight'] ?? null;

            if ($cartWeight >= $rate['min_weight'] && ($maxWeight === null || $cartWeight <= $maxWeight)) {
                return $rate['cost'];
            }
        }

        $lastRate = end($normalizedRates);

        return (float) ($lastRate['cost'] ?? 0);
    }

    protected static function translateSetting(string $key, string $fallback): string
    {
        return __('weightshipping::settings.' . $key);
    }

    protected function getIcon(): string
    {
        return 'scale';
    }
}
