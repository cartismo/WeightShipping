<?php

namespace Modules\WeightShipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Traits\HasMultiStoreModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\WeightShipping\Services\WeightShippingService;

class SettingsController extends Controller
{
    use HasMultiStoreModuleSettings;

    protected function getModuleSlug(): string
    {
        return 'weight-shipping';
    }

    protected function getDefaultSettings(): array
    {
        return WeightShippingService::defaultSettings();
    }

    public function index(): Response
    {
        $data = $this->getMultiStoreData();
        $data['options'] = $this->getOptions();
        $data['translations'] = $this->getTranslations();

        return Inertia::render('WeightShipping::Admin/Settings', $data);
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
            'settings.rates.*.min_weight' => 'required_with:settings.rates|numeric|min:0',
            'settings.rates.*.max_weight' => 'nullable|numeric|min:0',
            'settings.rates.*.cost' => 'required_with:settings.rates|numeric|min:0',
            'settings.sort_order' => 'integer|min:0',
        ]);

        return $this->saveStoreSettings($request);
    }

    protected function getOptions(): array
    {
        return [
            'weight_units' => [
                ['value' => 'kg', 'label' => $this->trans('weight_unit_kg')],
                ['value' => 'lb', 'label' => $this->trans('weight_unit_lb')],
                ['value' => 'g', 'label' => $this->trans('weight_unit_g')],
                ['value' => 'oz', 'label' => $this->trans('weight_unit_oz')],
            ],
            'calculation_types' => [
                [
                    'value' => 'per_unit',
                    'label' => $this->trans('calculation_type_per_unit'),
                    'description' => $this->trans('calculation_type_per_unit_desc'),
                ],
                [
                    'value' => 'tiered',
                    'label' => $this->trans('calculation_type_tiered'),
                    'description' => $this->trans('calculation_type_tiered_desc'),
                ],
            ],
            'currency' => $this->getCurrencyOptions(),
        ];
    }

    protected function getTranslations(): array
    {
        $translations = trans('weightshipping::settings');
        if (!is_array($translations)) {
            $translations = trans('weightshipping::settings', [], config('app.fallback_locale', 'en'));
        }

        return is_array($translations) ? $translations : [];
    }

    protected function getCurrencyOptions(): array
    {
        $currency = Currency::getBaseCurrency();

        return [
            'code' => $currency?->code ?? 'EUR',
            'symbol' => $currency?->symbol,
            'symbol_left' => $currency?->symbol_left,
            'symbol_right' => $currency?->symbol_right,
        ];
    }

    protected function trans(string $key): string
    {
        $translationKey = 'weightshipping::settings.' . $key;
        $translated = trans($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        $fallbackLocale = config('app.fallback_locale', 'en');
        $fallbackTranslation = trans($translationKey, [], $fallbackLocale);

        return $fallbackTranslation !== $translationKey ? $fallbackTranslation : $translationKey;
    }
}
