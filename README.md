# Zero_StorePickup

Magento's In-Store Pickup in Zero Checkout, and a store locator page.

Magento Open Source has pickup locations (MSI) and an In-Store Pickup
delivery method, but its checkout step is a Knockout component, so it
doesn't run in Zero Checkout. This module adds the missing storefront, on
Magento's own data and rules.

## Install

Requires Magento Open Source 2.4.7–2.4.9 and PHP 8.2–8.4 with the GD extension built with WebP (`php -r 'var_dump(function_exists("imagewebp"));'` says `true`; Magento's own PHP requirements cover the rest). Without WebP in GD, Zero skips its WebP image copies and serves the originals.

```bash
composer require fixstacks/module-zero-store-pickup
bin/magento setup:upgrade
bin/magento setup:di:compile           # production mode
bin/magento setup:static-content:deploy # production mode
bin/magento cache:flush
```

Composer installs `fixstacks/module-zero-core`, `fixstacks/module-zero-checkout` with it.

Part of [`fixstacks/zero`](https://fixstacks.com/zero), which installs the Zero theme and every Zero module at once.

## The stores

A store is an MSI source used as a pickup location: Stores > Inventory >
Sources > a source > "Use as Pickup Location", with its frontend name, its
address, coordinates, phone, email, and the frontend description (shown as
the opening hours). The website sees the stores of its stock.

## In Zero Checkout

With In-Store Pickup Delivery picked (Sales > Delivery Methods > In-Store
Pickup Delivery), a panel after the shipping methods (Zero Checkout's
`zero.checkout.shipping.after`, shown by CSS `:has()`) lists the stores that
have everything in the cart in stock, as Magento's own rate requires. Radio
buttons in the checkout form, no script.

Placing the order (`Model\PlaceOrder\PickupValidator`): without a store the
order is refused with a message. The store is looked up again by its code
and must still have the cart. The shipping address becomes the store's
(Magento's `ToQuoteAddress`, keeping the customer's name, phone and email,
and the pickup location code on the address), which is what Magento's
In-Store Pickup checks on the quote; the address the customer typed stays
the billing address. Magento then keeps the store on the order
(`inventory_pickup_location_order`), and the admin's order page offers
"Notify Order is Ready for Pickup".

## The store locator

`/storelocator`: every store of the website with its address, hours, phone
and email, Directions (Google Maps) and Open in OpenStreetMap; a search by
city, postcode or street (a plain GET form). Under each store a "Map" in a
`<details>`: OpenStreetMap's embed loads only when it is opened. The footer
links to it ("Our stores") where the website has stores.

The page is cached like any page: after editing a store, clean the page
cache.

## Settings

Stores > Configuration > Zero > Store Pickup: the locator page on or off,
its title, and the maps. The delivery method's own settings (title, name,
price) are Magento's.

## Demo

`bin/magento zero:demo:store usa` (Zero_DemoStores, store.json `pickup`)
creates Night Shift Club's two New York stores, a stock of their own for
the usa website and their stock of every product, and switches In-Store
Pickup on there. Note: a second source turns Magento's inventory into
multi-source mode for the whole installation (the product form shows
sources instead of one quantity).

## Tests

`dev/tests/zero-e2e/tests/store-pickup.spec.ts`: the locator (stores,
search, a map only when opened) and an order picked up in store, refused
without a store.

## License

OSL-3.0 or AFL-3.0, your choice: see `NOTICE.md`.

---

<a href="https://vilmate.com"><picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/Fixstacks/zero/main/docs/images/vilmate-logo-dark.svg">
  <img src="https://raw.githubusercontent.com/Fixstacks/zero/main/docs/images/vilmate-logo.svg" alt="Vilmate" height="34" align="right">
</picture></a>

Zero is made by [Fixstacks](https://fixstacks.com/zero) in partnership with [Vilmate](https://vilmate.com), a software development company.
Documentation: https://fixstacks.com/zero/docs. Demo store: https://zero.fixstacks.com.
