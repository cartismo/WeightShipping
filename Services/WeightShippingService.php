<?php

namespace Modules\WeightShipping\Services;

use App\Models\InstalledModule;

class WeightShippingService
{
    protected ?array $settings = null;

    public function getSettings(): array
    {
        if ($this->settings === null) {
            $module = InstalledModule::where('slug', 'weight-shipping')->first();
            $defaultSettings = config('weightshipping.settings', []);
            $this->settings = array_replace_recursive($defaultSettings, $module?->settings ?? []);
        }
        return $this->settings;
    }

    public function isEnabled(): bool
    {
        return $this->getSettings()['enabled'] ?? false;
    }

    public function getTitle(): string
    {
        return $this->getSettings()['title'] ?? 'Weight Based Shipping';
    }

    public function getWeightUnit(): string
    {
        return $this->getSettings()['weight_unit'] ?? 'kg';
    }

    public function isAvailable(float $weight): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        $maxWeight = $this->getSettings()['max_weight'] ?? null;
        return $maxWeight === null || $weight <= $maxWeight;
    }

    public function calculateCost(float $weight, float $cartTotal = 0): float
    {
        if (!$this->isEnabled()) {
            return 0.00;
        }

        $settings = $this->getSettings();

        // Check for free shipping threshold
        $freeThreshold = $settings['free_shipping_threshold'] ?? null;
        if ($freeThreshold !== null && $cartTotal >= $freeThreshold) {
            return 0.00;
        }

        $calculationType = $settings['calculation_type'] ?? 'per_unit';
        $baseCost = (float) ($settings['base_cost'] ?? 0);
        $handlingFee = (float) ($settings['handling_fee'] ?? 0);

        if ($calculationType === 'tiered') {
            $cost = $this->calculateTieredCost($weight, $settings['rates'] ?? []);
        } else {
            $costPerUnit = (float) ($settings['cost_per_unit'] ?? 0);
            $cost = $baseCost + ($weight * $costPerUnit);
        }

        return $cost + $handlingFee;
    }

    protected function calculateTieredCost(float $weight, array $rates): float
    {
        if (empty($rates)) {
            return 0.00;
        }

        usort($rates, fn($a, $b) => ($a['min_weight'] ?? 0) <=> ($b['min_weight'] ?? 0));

        foreach ($rates as $rate) {
            $minWeight = (float) ($rate['min_weight'] ?? 0);
            $maxWeight = (float) ($rate['max_weight'] ?? PHP_FLOAT_MAX);

            if ($weight >= $minWeight && $weight <= $maxWeight) {
                return (float) ($rate['cost'] ?? 0);
            }
        }

        $lastRate = end($rates);
        return (float) ($lastRate['cost'] ?? 0);
    }

    public function getShippingMethod(float $weight, float $cartTotal = 0): ?array
    {
        if (!$this->isAvailable($weight)) {
            return null;
        }

        $cost = $this->calculateCost($weight, $cartTotal);

        return [
            'id' => 'weight-shipping',
            'title' => $this->getTitle(),
            'description' => $this->getSettings()['description'] ?? '',
            'cost' => $cost,
            'formatted_cost' => $cost > 0 ? number_format($cost, 2) : 'Free',
            'weight' => $weight,
            'weight_unit' => $this->getWeightUnit(),
        ];
    }
}