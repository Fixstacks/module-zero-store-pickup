<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\StorePickup\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\Store\Model\ScopeInterface;
use Zero\StorePickup\Model\Locations;
use Zero\StorePickup\Model\PlaceOrder\PickupValidator;

/**
 * The stores (pickup locations) as the checkout panel and the store locator
 * show them: name, address lines, hours (the source's frontend
 * description), phone, email, a map and directions.
 */
class Stores implements ArgumentInterface
{
    /** @var array<string, string> */
    private array $countries = [];

    public function __construct(
        private readonly Locations $locations,
        private readonly CheckoutSession $checkoutSession,
        private readonly CountryFactory $countryFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly RequestInterface $request
    ) {
    }

    public function rateCode(): string
    {
        return PickupValidator::RATE;
    }

    public function fieldName(): string
    {
        return PickupValidator::FIELD;
    }

    /**
     * The stores that can take the current cart.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forCart(): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            return [];
        }

        return array_map([$this, 'card'], $this->locations->forQuote($quote));
    }

    /**
     * The store the cart's shipping address is already set to (a reload), or ''.
     */
    public function chosen(): string
    {
        try {
            return (string) $this->checkoutSession->getQuote()->getShippingAddress()->getExtensionAttributes()?->getPickupLocationCode();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Every store of the website, narrowed by ?q= (name, city, postcode, street).
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $query = mb_strtolower($this->query());
        $cards = array_map([$this, 'card'], $this->locations->all());
        if ($query === '') {
            return $cards;
        }

        return array_values(array_filter($cards, static fn (array $card) => str_contains(
            mb_strtolower($card['name'] . ' ' . implode(' ', $card['lines'])),
            $query
        )));
    }

    /** The website has at least one store (pickup location). */
    public function hasStores(): bool
    {
        return $this->locations->all() !== [];
    }

    public function query(): string
    {
        return mb_substr(trim((string) $this->request->getParam('q')), 0, 80);
    }

    public function title(): string
    {
        return (string) $this->scopeConfig->getValue('zero_store_pickup/locator/title', ScopeInterface::SCOPE_STORE)
            ?: (string) __('Our stores');
    }

    public function showsMaps(): bool
    {
        return $this->scopeConfig->isSetFlag('zero_store_pickup/locator/maps', ScopeInterface::SCOPE_STORE);
    }

    public function locatorEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag('zero_store_pickup/locator/enabled', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return array{code: string, name: string, lines: string[], hours: string, phone: string, email: string,
     *     map: string, embed: string, directions: string}
     */
    private function card(PickupLocationInterface $location): array
    {
        $lat = $location->getLatitude();
        $lon = $location->getLongitude();
        $hasPoint = $lat !== null && $lon !== null && ($lat != 0.0 || $lon != 0.0);
        $lines = array_values(array_filter([
            (string) $location->getStreet(),
            trim(implode(' ', array_filter([
                (string) $location->getCity() . ($location->getRegion() ? ',' : ''),
                (string) $location->getRegion(),
                (string) $location->getPostcode(),
            ]))),
            $this->country((string) $location->getCountryId()),
        ]));
        $address = implode(', ', $lines);

        return [
            'code' => $location->getPickupLocationCode(),
            'name' => (string) ($location->getName() ?: $location->getPickupLocationCode()),
            'lines' => $lines,
            'hours' => trim((string) $location->getDescription()),
            'phone' => (string) $location->getPhone(),
            'email' => (string) $location->getEmail(),
            'map' => $hasPoint
                ? sprintf('https://www.openstreetmap.org/?mlat=%1$.6F&mlon=%2$.6F#map=17/%1$.6F/%2$.6F', $lat, $lon)
                : 'https://www.openstreetmap.org/search?query=' . rawurlencode($address),
            'embed' => $hasPoint
                ? sprintf(
                    'https://www.openstreetmap.org/export/embed.html?bbox=%.6F,%.6F,%.6F,%.6F&layer=mapnik&marker=%.6F,%.6F',
                    $lon - 0.006,
                    $lat - 0.003,
                    $lon + 0.006,
                    $lat + 0.003,
                    $lat,
                    $lon
                )
                : '',
            'directions' => 'https://www.google.com/maps/dir/?api=1&destination='
                . rawurlencode($hasPoint ? sprintf('%.6F,%.6F', $lat, $lon) : $address),
        ];
    }

    private function country(string $id): string
    {
        if ($id === '') {
            return '';
        }
        if (!isset($this->countries[$id])) {
            $this->countries[$id] = (string) $this->countryFactory->create()->loadByCode($id)->getName();
        }

        return $this->countries[$id];
    }
}
