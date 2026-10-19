<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero_StorePickup: Magento's In-Store Pickup (MSI pickup locations) in Zero
 * Checkout, where its Knockout step doesn't run, and a store locator page.
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Zero_StorePickup', __DIR__);
