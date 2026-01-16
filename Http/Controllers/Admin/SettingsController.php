<?php

namespace Modules\WeightShipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HasMultiStoreModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    use HasMultiStoreModuleSettings;

    protected function getModuleSlug(): string
    {
        return 'weight-shipping';
    }

    protected function getDefaultSettings(): array
    {
        return config('weightshipping.settings', [
            'enabled' => false,
            'title' => 'Weight Based Shipping',
            'description' => 'Shipping cost calculated based on order weight.',
            'weight_unit' => 'kg',
            'calculation_type' => 'per_unit',
            'base_cost' => 5.00,
            'cost_per_unit' => 1.00,
            'free_shipping_threshold' => null,
            'max_weight' => null,
            'handling_fee' => 0,
            'rates' => [],
            'sort_order' => 0,
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('WeightShipping::Admin/Settings', $this->getMultiStoreData());
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'is_enabled' => 'boolean',
            'settings.enabled' => 'boolean',
            'settings.title' => 'required|string|max:255',
            'settings.description' => 'nullable|string|max:1000',
            'settings.weight_unit' => 'required|in:kg,lb,g,oz',
            'settings.calculation_type' => 'required|in:per_unit,tiered',
            'settings.base_cost' => 'required|numeric|min:0',
            'settings.cost_per_unit' => 'required|numeric|min:0',
            'settings.free_shipping_threshold' => 'nullable|numeric|min:0',
            'settings.max_weight' => 'nullable|numeric|min:0',
            'settings.handling_fee' => 'nullable|numeric|min:0',
            'settings.rates' => 'nullable|array',
            'settings.sort_order' => 'integer|min:0',
        ]);

        return $this->saveStoreSettings($request);
    }
}