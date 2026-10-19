<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\StorePickup\Model\PlaceOrder;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Model\Quote;
use Zero\Checkout\Api\PlaceOrderValidatorInterface;
use Zero\StorePickup\Model\Locations;

/**
 * With In-Store Pickup picked, the order needs a store that has the whole
 * cart (looked up again: the posted code is only a key). The shipping
 * address becomes the store's, as Magento's In-Store Pickup expects (its
 * quote validation compares them), with the customer's name, phone and
 * email; the address the customer typed stays the billing address.
 */
class PickupValidator implements PlaceOrderValidatorInterface
{
    public const RATE = InStorePickup::DELIVERY_METHOD;
    public const FIELD = 'pickup_location';

    public function __construct(
        private readonly Locations $locations,
        private readonly ToQuoteAddress $toQuoteAddress
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function validate(Quote $quote, array $request): void
    {
        if (($request['shipping_method'] ?? '') !== self::RATE) {
            return;
        }
        $location = $this->locations->forQuoteByCode($quote, trim((string) ($request[self::FIELD] ?? '')));
        if (!$location) {
            throw new LocalizedException(__('Choose the store to pick your order up from.'));
        }
        $shipping = $quote->getShippingAddress();
        $converted = $this->toQuoteAddress->convert($location, $shipping, [
            'shipping_method' => $shipping->getShippingMethod(),
            'shipping_description' => $shipping->getShippingDescription(),
        ]);
        $shipping->addData($converted->getData());
        $shipping->setCustomerAddressId(null)
            ->setSaveInAddressBook(0)
            ->setSameAsBilling(0);
        $extension = $shipping->getExtensionAttributes();
        $extension->setPickupLocationCode($location->getPickupLocationCode());
        $shipping->setExtensionAttributes($extension);
    }
}
