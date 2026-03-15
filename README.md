# WeightShipping

Weight-based shipping module for Cartismo.

What it covers:
- address-based shipping method with cost calculated from total cart weight
- per-unit pricing or tiered weight ranges
- optional handling fee, free shipping threshold, and max weight limit
- multi-store admin settings
- module-local tests in `Tests/`

Important notes:
- this module depends on Cartismo checkout passing cart weight into the shipping contract
- orders above the configured `max_weight` do not see this shipping method
- this module uses the standard Cartismo address checkout form and does not add a custom frontend component

Documentation:
- `/docs/WEIGHT_SHIPPING.md`

Tests:
- `php artisan test ../modules/WeightShipping/Tests/Unit`
