<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useConfirmDialog } from '@/Composables/useConfirmDialog.js';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoreSettingsTabs from '@/Components/Admin/StoreSettingsTabs.vue';
import {
    ArrowPathIcon,
    CalculatorIcon,
    CheckCircleIcon,
    CurrencyDollarIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    PlusIcon,
    ScaleIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    module: Object,
    stores: Array,
    storeSettings: Object,
    defaultSettings: Object,
    options: Object,
    translations: Object,
});

const page = usePage();
const { confirm } = useConfirmDialog();
const storeTabsRef = ref(null);
const saving = ref(false);

const t = (key, replacements = {}) => {
    let value = props.translations?.[key] || key;

    Object.entries(replacements).forEach(([name, replacement]) => {
        value = value.replace(`:${name}`, replacement);
    });

    return value;
};

const unwrap = (value) => {
    return value && typeof value === 'object' && 'value' in value ? value.value : value;
};

const currentSettings = computed(() => unwrap(storeTabsRef.value?.localSettings) || {});
const currentEnabled = computed(() => unwrap(storeTabsRef.value?.isEnabled) ?? false);
const activeStoreId = computed(() => unwrap(storeTabsRef.value?.activeStoreId) || null);
const errorMessages = computed(() => Object.values(page.props.errors || {}));

const currencySymbol = computed(() => {
    const currency = props.options?.currency || {};
    return currency.symbol_left || currency.symbol_right || currency.symbol || currency.code || '';
});

const hasChanges = computed(() => {
    if (!activeStoreId.value) {
        return false;
    }

    const currentStoreSettings = props.storeSettings?.[activeStoreId.value];
    if (!currentStoreSettings) {
        return true;
    }

    const original = {
        ...props.defaultSettings,
        ...(currentStoreSettings.settings || {}),
    };

    return JSON.stringify(currentSettings.value) !== JSON.stringify(original)
        || currentEnabled.value !== !!currentStoreSettings.is_enabled;
});

const calculationSummary = computed(() => {
    if (currentSettings.value.calculation_type === 'tiered') {
        return t('summary_tiered', {
            count: String(currentSettings.value.rates?.length || 0),
        });
    }

    return t('summary_per_unit', {
        base: `${currencySymbol.value}${Number(currentSettings.value.base_cost || 0).toFixed(2)}`,
        rate: `${currencySymbol.value}${Number(currentSettings.value.cost_per_unit || 0).toFixed(2)}`,
        unit: currentSettings.value.weight_unit || 'kg',
    });
});

const freeShippingSummary = computed(() => {
    const threshold = currentSettings.value.free_shipping_threshold;
    if (threshold === null || threshold === '' || threshold === undefined) {
        return null;
    }

    return t('summary_free_shipping', {
        amount: `${currencySymbol.value}${Number(threshold).toFixed(2)}`,
    });
});

const weightLimitSummary = computed(() => {
    const maxWeight = currentSettings.value.max_weight;
    if (maxWeight === null || maxWeight === '' || maxWeight === undefined) {
        return t('no_limit');
    }

    return t('summary_weight_limit', {
        weight: String(maxWeight),
        unit: currentSettings.value.weight_unit || 'kg',
    });
});

const submit = () => {
    if (!activeStoreId.value) {
        return;
    }

    saving.value = true;
    router.put(route('admin.shipping.weight.settings.update'), {
        store_id: activeStoreId.value,
        is_enabled: currentEnabled.value,
        settings: currentSettings.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
        },
    });
};

const resetAll = async () => {
    const confirmed = await confirm({
        title: t('reset'),
        message: t('reset_confirm'),
        variant: 'warning',
    });
    if (!confirmed) return;

    Object.assign(currentSettings.value, JSON.parse(JSON.stringify(props.defaultSettings || {})));
};

const addRate = () => {
    if (!Array.isArray(currentSettings.value.rates)) {
        currentSettings.value.rates = [];
    }

    const lastRate = currentSettings.value.rates[currentSettings.value.rates.length - 1] || null;
    const nextMin = Number(lastRate?.max_weight ?? lastRate?.min_weight ?? 0);

    currentSettings.value.rates.push({
        min_weight: nextMin,
        max_weight: nextMin + 1,
        cost: 0,
    });
};

const removeRate = (index) => {
    currentSettings.value.rates.splice(index, 1);
};
</script>

<template>
    <AdminLayout :title="`${module.name} - ${t('title')}`">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-sky-100 p-3">
                        <ScaleIcon class="h-6 w-6 text-sky-700" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ module.name }}</h1>
                        <p class="text-sm text-gray-500">{{ t('module_settings') }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span v-if="hasChanges" class="text-sm font-medium text-amber-600">{{ t('unsaved_changes') }}</span>
                    <button
                        type="button"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        @click="resetAll"
                    >
                        {{ t('reset') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-sky-800 disabled:opacity-50"
                        :disabled="saving || !hasChanges"
                        @click="submit"
                    >
                        <ArrowPathIcon v-if="saving" class="h-4 w-4 animate-spin" />
                        <CheckCircleIcon v-else class="h-4 w-4" />
                        {{ saving ? t('saving') : t('save') }}
                    </button>
                </div>
            </div>
        </template>

        <div v-if="errorMessages.length" class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4">
            <div class="flex items-start gap-3">
                <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 text-red-600" />
                <div class="space-y-1 text-sm text-red-700">
                    <div class="font-medium">{{ t('validation_failed') }}</div>
                    <div v-for="message in errorMessages" :key="message">{{ message }}</div>
                </div>
            </div>
        </div>

        <StoreSettingsTabs
            ref="storeTabsRef"
            :stores="stores"
            :store-settings="storeSettings"
            :default-settings="defaultSettings"
            module-slug="weight-shipping"
        >
            <template #default="{ settings }">
                <div class="grid gap-6 xl:grid-cols-4">
                    <div class="space-y-6 xl:col-span-1">
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-3">
                                <InformationCircleIcon class="h-5 w-5 text-gray-400" />
                                <h2 class="font-semibold text-gray-900">{{ t('module_status') }}</h2>
                            </div>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-500">{{ t('status') }}</span>
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="currentEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ currentEnabled ? t('active') : t('inactive') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-500">{{ t('weight_unit') }}</span>
                                    <span class="font-medium text-gray-900">{{ settings.weight_unit || 'kg' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-500">{{ t('sort_order') }}</span>
                                    <span class="font-medium text-gray-900">{{ settings.sort_order || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-gradient-to-br from-sky-700 to-cyan-600 p-5 text-white shadow-lg">
                            <div class="text-sm text-white/80">{{ t('calculation_summary') }}</div>
                            <div class="mt-2 text-lg font-semibold">{{ calculationSummary }}</div>
                            <div class="mt-3 text-sm text-white/80">{{ weightLimitSummary }}</div>
                            <div v-if="freeShippingSummary" class="mt-2 text-sm text-white/80">{{ freeShippingSummary }}</div>
                        </div>
                    </div>

                    <div class="space-y-6 xl:col-span-3">
                        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <ScaleIcon class="h-5 w-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">{{ t('display_settings') }}</h2>
                                </div>
                            </div>

                            <div class="space-y-6 p-6">
                                <div class="grid gap-6 md:grid-cols-3">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('display_title') }}</label>
                                        <input
                                            :value="settings.title"
                                            type="text"
                                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                            :placeholder="t('display_title_placeholder')"
                                            @input="currentSettings.title = $event.target.value"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('weight_unit') }}</label>
                                        <select
                                            :value="settings.weight_unit"
                                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                            @change="currentSettings.weight_unit = $event.target.value"
                                        >
                                            <option v-for="unit in options.weight_units" :key="unit.value" :value="unit.value">
                                                {{ unit.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('sort_order') }}</label>
                                        <input
                                            :value="settings.sort_order"
                                            type="number"
                                            min="0"
                                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                            @input="currentSettings.sort_order = Number($event.target.value || 0)"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">{{ t('sort_order_hint') }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('description') }}</label>
                                    <textarea
                                        :value="settings.description"
                                        rows="2"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                        :placeholder="t('description_placeholder')"
                                        @input="currentSettings.description = $event.target.value"
                                    ></textarea>
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <CalculatorIcon class="h-5 w-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">{{ t('calculation_settings') }}</h2>
                                </div>
                            </div>

                            <div class="space-y-6 p-6">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label
                                        v-for="option in options.calculation_types"
                                        :key="option.value"
                                        class="cursor-pointer rounded-2xl border p-4 transition"
                                        :class="settings.calculation_type === option.value ? 'border-sky-500 bg-sky-50' : 'border-gray-200 hover:border-gray-300'"
                                    >
                                        <div class="flex items-start gap-3">
                                            <input
                                                :checked="settings.calculation_type === option.value"
                                                type="radio"
                                                class="mt-1 text-sky-600 focus:ring-sky-500"
                                                @change="currentSettings.calculation_type = option.value"
                                            />
                                            <div>
                                                <div class="font-medium text-gray-900">{{ option.label }}</div>
                                                <div class="text-sm text-gray-500">{{ option.description }}</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div v-if="settings.calculation_type === 'per_unit'" class="grid gap-6 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('base_cost') }}</label>
                                        <input
                                            :value="settings.base_cost"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                            @input="currentSettings.base_cost = Number($event.target.value || 0)"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">{{ t('base_cost_hint') }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('cost_per_unit') }}</label>
                                        <input
                                            :value="settings.cost_per_unit"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                            @input="currentSettings.cost_per_unit = Number($event.target.value || 0)"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">{{ t('cost_per_unit_hint') }}</p>
                                    </div>
                                </div>

                                <div v-else class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-medium text-gray-900">{{ t('tiers') }}</h3>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl bg-sky-50 px-4 py-2 text-sm font-medium text-sky-700 transition hover:bg-sky-100"
                                            @click="addRate"
                                        >
                                            <PlusIcon class="h-4 w-4" />
                                            {{ t('add_tier') }}
                                        </button>
                                    </div>

                                    <div v-if="settings.rates?.length" class="space-y-3">
                                        <div
                                            v-for="(rate, index) in settings.rates"
                                            :key="index"
                                            class="grid gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-[1fr_1fr_1fr_auto]"
                                        >
                                            <div>
                                                <label class="mb-2 block text-xs font-medium text-gray-600">{{ t('min_weight') }}</label>
                                                <input v-model.number="rate.min_weight" type="number" min="0" step="0.01" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500" />
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-xs font-medium text-gray-600">{{ t('max_weight') }}</label>
                                                <input v-model.number="rate.max_weight" type="number" min="0" step="0.01" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500" />
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-xs font-medium text-gray-600">{{ t('tier_cost') }}</label>
                                                <input v-model.number="rate.cost" type="number" min="0" step="0.01" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500" />
                                            </div>
                                            <div class="flex items-end">
                                                <button
                                                    type="button"
                                                    class="rounded-xl p-3 text-red-600 transition hover:bg-red-50"
                                                    :aria-label="t('remove_tier')"
                                                    @click="removeRate(index)"
                                                >
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">
                                        {{ t('tiers_empty') }}
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <CurrencyDollarIcon class="h-5 w-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">{{ t('additional_settings') }}</h2>
                                </div>
                            </div>

                            <div class="grid gap-6 p-6 md:grid-cols-3">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('handling_fee') }}</label>
                                    <input
                                        :value="settings.handling_fee"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                        @input="currentSettings.handling_fee = Number($event.target.value || 0)"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">{{ t('handling_fee_hint') }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('max_weight_setting') }}</label>
                                    <input
                                        :value="settings.max_weight"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                        @input="currentSettings.max_weight = $event.target.value === '' ? null : Number($event.target.value)"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">{{ t('max_weight_hint') }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">{{ t('free_shipping_threshold') }}</label>
                                    <input
                                        :value="settings.free_shipping_threshold"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-sky-500 focus:ring-sky-500"
                                        @input="currentSettings.free_shipping_threshold = $event.target.value === '' ? null : Number($event.target.value)"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">{{ t('free_shipping_threshold_hint') }}</p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </template>
        </StoreSettingsTabs>
    </AdminLayout>
</template>
