<?php

return [
    'name' => 'WeightShipping',

    'settings' => [
        'enabled' => true,
        'title' => 'Weight Based Shipping',
        'description' => 'Shipping cost calculated by weight',
        'weight_unit' => 'kg',
        'calculation_type' => 'per_unit',
        'base_cost' => 0.00,
        'cost_per_unit' => 5.00,
        'free_shipping_threshold' => null,
        'max_weight' => null,
        'handling_fee' => 0.00,
        'rates' => [],
        'sort_order' => 0,
    ],
];