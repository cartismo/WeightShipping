<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoreSettingsTabs from '@/Components/Admin/StoreSettingsTabs.vue';
import {
    ScaleIcon,
    ArrowLeftIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon,
    CurrencyDollarIcon,
    Cog6ToothIcon,
    InformationCircleIcon,
    PlusIcon,
    TrashIcon,
    CalculatorIcon,
    GiftIcon,
    ListBulletIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    module: Object,
    stores: Array,
    storeSettings: Object,
    defaultSettings: Object,
});

const storeTabsRef = ref(null);
const saving = ref(false);

const submit = () => {
    if (!storeTabsRef.value) return;

    saving.value = true;
    router.put(route('admin.shipping.weight.settings.update'), {
        store_id: storeTabsRef.value.activeStoreId,
        is_enabled: storeTabsRef.value.isEnabled,
        settings: storeTabsRef.value.localSettings,
    }, {
        preserveScroll: true,
        onFinish: () => saving.value = false,
    });
};

const resetAll = () => {
    if (confirm('Reset all settings to defaults?') && storeTabsRef.value) {
        Object.assign(storeTabsRef.value.localSettings, props.defaultSettings);
    }
};

const hasChanges = computed(() => {
    if (!storeTabsRef.value) return false;
    const currentStoreSettings = props.storeSettings[storeTabsRef.value.activeStoreId];
    if (!currentStoreSettings) return true;
    const original = { ...props.defaultSettings, ...(currentStoreSettings.settings || {}) };
    return JSON.stringify(storeTabsRef.value.localSettings) !== JSON.stringify(original) ||
           storeTabsRef.value.isEnabled !== currentStoreSettings.is_enabled;
});

const weightUnits = [
    { value: 'kg', label: 'Kilograms (kg)' },
    { value: 'lb', label: 'Pounds (lb)' },
    { value: 'g', label: 'Grams (g)' },
    { value: 'oz', label: 'Ounces (oz)' },
];

const addRate = () => {
    if (!storeTabsRef.value) return;
    const settings = storeTabsRef.value.localSettings;
    if (!settings.rates) settings.rates = [];
    const lastRate = settings.rates[settings.rates.length - 1];
    const minWeight = lastRate ? lastRate.max_weight : 0;
    settings.rates.push({ min_weight: minWeight, max_weight: minWeight + 10, cost: 10.00 });
};

const removeRate = (index) => {
    if (storeTabsRef.value) {
        storeTabsRef.value.localSettings.rates.splice(index, 1);
    }
};
</script>

<template>
    <AdminLayout :title="`${module.name} Settings`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('admin.modules.installed.index')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div class="flex items-center space-x-3">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow-lg">
                            <ScaleIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">{{ module.name }}</h1>
                            <p class="text-sm text-gray-500">Shipping Method Configuration</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span v-if="hasChanges" class="text-sm text-amber-600 font-medium">Unsaved changes</span>
                    <button type="button" @click="resetAll" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        <ArrowPathIcon class="w-4 h-4 inline mr-2" />Reset
                    </button>
                    <button type="button" @click="submit" :disabled="saving || !hasChanges" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-cyan-600 rounded-xl hover:from-blue-700 hover:to-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-blue-500/25">
                        <CheckIcon class="w-4 h-4 inline mr-2" />{{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </template>

        <StoreSettingsTabs ref="storeTabsRef" :stores="stores" :store-settings="storeSettings" :default-settings="defaultSettings" module-slug="weight-shipping">
            <template #default="{ store, settings, updateSetting, isEnabled }">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Left Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-5 border-b border-gray-100"><h3 class="font-semibold text-gray-900">Module Status</h3></div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Status</span>
                                    <span :class="settings.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-3 py-1 text-xs font-semibold rounded-full">{{ settings.enabled ? 'Active' : 'Inactive' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Unit</span>
                                    <span class="text-sm text-gray-900">{{ settings.weight_unit || 'kg' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl shadow-lg p-5 text-white">
                            <div class="flex items-center space-x-3 mb-4">
                                <CalculatorIcon class="w-8 h-8 opacity-80" />
                                <div>
                                    <p class="text-sm opacity-80">Calculation</p>
                                    <p class="text-lg font-bold">{{ settings.calculation_type === 'tiered' ? 'Tiered Rates' : 'Per Unit' }}</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-white/20 space-y-2">
                                <div v-if="settings.calculation_type === 'per_unit'" class="flex items-center space-x-2">
                                    <CurrencyDollarIcon class="w-5 h-5 opacity-80" />
                                    <span class="text-sm opacity-80">${{ settings.base_cost || 0 }} + ${{ settings.cost_per_unit || 0 }}/{{ settings.weight_unit || 'kg' }}</span>
                                </div>
                                <div v-else class="flex items-center space-x-2">
                                    <ListBulletIcon class="w-5 h-5 opacity-80" />
                                    <span class="text-sm opacity-80">{{ settings.rates?.length || 0 }} tier(s) configured</span>
                                </div>
                                <div v-if="settings.free_shipping_threshold" class="flex items-center space-x-2">
                                    <GiftIcon class="w-5 h-5 opacity-80" />
                                    <span class="text-sm opacity-80">Free over ${{ settings.free_shipping_threshold }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100">
                            <div class="flex items-start space-x-3">
                                <InformationCircleIcon class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" />
                                <div>
                                    <h4 class="text-sm font-medium text-blue-900">Weight-Based Shipping</h4>
                                    <p class="text-sm text-blue-700 mt-1">Calculate shipping costs based on total order weight for {{ store?.name }}.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Enable Toggle -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div :class="settings.enabled ? 'bg-blue-100' : 'bg-gray-100'" class="p-3 rounded-xl transition-colors">
                                        <component :is="settings.enabled ? CheckCircleIcon : XCircleIcon" :class="settings.enabled ? 'text-blue-600' : 'text-gray-400'" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Enable Weight-Based Shipping</h3>
                                        <p class="text-sm text-gray-500">Calculate shipping by order weight for {{ store?.name }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="updateSetting('enabled', !settings.enabled)" :class="settings.enabled ? 'bg-blue-500' : 'bg-gray-300'" class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <span :class="settings.enabled ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out" />
                                </button>
                            </div>
                        </div>

                        <!-- General Settings -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <Cog6ToothIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">General Settings</h2>
                                </div>
                            </div>
                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Title <span class="text-red-500">*</span></label>
                                        <input type="text" :value="settings.title" @input="updateSetting('title', $event.target.value)" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="e.g., Weight Based Shipping" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Weight Unit</label>
                                        <select :value="settings.weight_unit" @change="updateSetting('weight_unit', $event.target.value)" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                            <option v-for="unit in weightUnits" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                                        <input type="number" :value="settings.sort_order" @input="updateSetting('sort_order', parseInt($event.target.value) || 0)" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="0" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea :value="settings.description" @input="updateSetting('description', $event.target.value)" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none" placeholder="Brief description shown at checkout..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Calculation Method -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <CalculatorIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Calculation Method</h2>
                                </div>
                            </div>
                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label :class="settings.calculation_type === 'per_unit' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500' : 'border-gray-200 hover:border-gray-300'" class="relative flex cursor-pointer rounded-xl border-2 p-5 transition-all">
                                        <input type="radio" :checked="settings.calculation_type === 'per_unit'" @change="updateSetting('calculation_type', 'per_unit')" class="sr-only" />
                                        <div class="flex items-start space-x-4">
                                            <div :class="settings.calculation_type === 'per_unit' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-500'" class="p-2 rounded-lg">
                                                <CurrencyDollarIcon class="w-5 h-5" />
                                            </div>
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-900">Per Weight Unit</span>
                                                <span class="mt-1 block text-xs text-gray-500">Base cost + (weight x rate per unit)</span>
                                            </div>
                                        </div>
                                    </label>
                                    <label :class="settings.calculation_type === 'tiered' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500' : 'border-gray-200 hover:border-gray-300'" class="relative flex cursor-pointer rounded-xl border-2 p-5 transition-all">
                                        <input type="radio" :checked="settings.calculation_type === 'tiered'" @change="updateSetting('calculation_type', 'tiered')" class="sr-only" />
                                        <div class="flex items-start space-x-4">
                                            <div :class="settings.calculation_type === 'tiered' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-500'" class="p-2 rounded-lg">
                                                <ListBulletIcon class="w-5 h-5" />
                                            </div>
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-900">Tiered Rates</span>
                                                <span class="mt-1 block text-xs text-gray-500">Different fixed rates per weight range</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Per Unit Settings -->
                                <div v-if="settings.calculation_type === 'per_unit'" class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-200">
                                    <div class="bg-gray-50 rounded-xl p-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Base Cost</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                                            <input type="number" :value="settings.base_cost" @input="updateSetting('base_cost', parseFloat($event.target.value) || 0)" step="0.01" min="0" class="w-full pl-10 pr-4 py-3 text-lg font-semibold border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" placeholder="0.00" />
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">Fixed cost added to every order</p>
                                    </div>
                                    <div class="bg-blue-50 rounded-xl p-5 border border-blue-100">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Cost per {{ settings.weight_unit || 'kg' }}</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                                            <input type="number" :value="settings.cost_per_unit" @input="updateSetting('cost_per_unit', parseFloat($event.target.value) || 0)" step="0.01" min="0" class="w-full pl-10 pr-4 py-3 text-lg font-semibold border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" placeholder="0.00" />
                                        </div>
                                        <p class="mt-2 text-xs text-blue-700">Multiplied by total order weight</p>
                                    </div>
                                </div>

                                <!-- Tiered Rates -->
                                <div v-if="settings.calculation_type === 'tiered'" class="pt-4 border-t border-gray-200">
                                    <div class="flex items-center justify-between mb-4">
                                        <label class="text-sm font-medium text-gray-700">Weight Tiers</label>
                                        <button type="button" @click="addRate" class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors">
                                            <PlusIcon class="w-4 h-4 mr-2" />Add Tier
                                        </button>
                                    </div>
                                    <div v-if="settings.rates?.length" class="space-y-3">
                                        <div v-for="(rate, index) in settings.rates" :key="index" class="flex items-end gap-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-2">Min Weight ({{ settings.weight_unit || 'kg' }})</label>
                                                <input type="number" v-model.number="rate.min_weight" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-2">Max Weight ({{ settings.weight_unit || 'kg' }})</label>
                                                <input type="number" v-model.number="rate.max_weight" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-500 mb-2">Cost ($)</label>
                                                <input type="number" v-model.number="rate.cost" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                            </div>
                                            <button type="button" @click="removeRate(index)" class="p-2.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                                        <ScaleIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                                        <p class="text-sm text-gray-500">No tiers configured yet</p>
                                        <button type="button" @click="addRate" class="mt-3 text-sm text-blue-600 hover:text-blue-700 font-medium">Add your first tier</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Settings -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <CurrencyDollarIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Additional Settings</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="bg-gray-50 rounded-xl p-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Handling Fee</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                                            <input type="number" :value="settings.handling_fee" @input="updateSetting('handling_fee', parseFloat($event.target.value) || 0)" step="0.01" min="0" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" placeholder="0.00" />
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">Additional fee per order</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Max Weight</label>
                                        <div class="relative">
                                            <input type="number" :value="settings.max_weight" @input="updateSetting('max_weight', parseFloat($event.target.value) || null)" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" placeholder="No limit" />
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">{{ settings.weight_unit || 'kg' }}</span>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">Maximum order weight</p>
                                    </div>
                                    <div class="bg-green-50 rounded-xl p-5 border border-green-100">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            <GiftIcon class="w-4 h-4 inline mr-1 text-green-600" />Free Shipping Over
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                                            <input type="number" :value="settings.free_shipping_threshold" @input="updateSetting('free_shipping_threshold', parseFloat($event.target.value) || null)" step="0.01" min="0" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white" placeholder="No threshold" />
                                        </div>
                                        <p class="mt-2 text-xs text-green-700">Orders above this get free shipping</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </StoreSettingsTabs>
    </AdminLayout>
</template>