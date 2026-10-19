<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\StorePickup\Model;

use Magento\Framework\Api\SortOrderBuilder;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

/**
 * The website's pickup locations (MSI sources used as pickup locations, of
 * the website's stock), by name: all of them for the store locator, or for a
 * cart only those that have every item in stock, as Magento's own In-Store
 * Pickup rate requires.
 */
class Locations
{
    private const LIMIT = 200;

    public function __construct(
        private readonly GetPickupLocationsInterface $getPickupLocations,
        private readonly SearchRequestBuilderInterfaceFactory $searchRequestBuilderFactory,
        private readonly SearchRequestExtensionInterfaceFactory $searchRequestExtensionFactory,
        private readonly ProductInfoInterfaceFactory $productInfoFactory,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @return PickupLocationInterface[]
     */
    public function all(): array
    {
        return $this->search(null, null);
    }

    /**
     * @return PickupLocationInterface[]
     */
    public function forQuote(Quote $quote): array
    {
        return $this->search($this->skus($quote), null);
    }

    /**
     * The location with this code, if it can take the whole cart.
     */
    public function forQuoteByCode(Quote $quote, string $code): ?PickupLocationInterface
    {
        return $code === '' ? null : ($this->search($this->skus($quote), $code)[0] ?? null);
    }

    /**
     * @param string[]|null $skus
     * @return PickupLocationInterface[]
     */
    private function search(?array $skus, ?string $code): array
    {
        $builder = $this->searchRequestBuilderFactory->create()
            ->setScopeType(SalesChannelInterface::TYPE_WEBSITE)
            ->setScopeCode((string) $this->storeManager->getWebsite()->getCode())
            ->setSortOrders([$this->sortOrderBuilder->setField(PickupLocationInterface::FRONTEND_NAME)->setAscendingDirection()->create()])
            ->setPageSize(self::LIMIT);
        if ($code !== null) {
            $builder->setPickupLocationCodeFilter($code);
        }
        if ($skus !== null) {
            $extension = $this->searchRequestExtensionFactory->create();
            $extension->setProductsInfo(array_map(fn (string $sku) => $this->productInfoFactory->create(['sku' => $sku]), $skus));
            $builder->setSearchRequestExtension($extension);
        }
        try {
            return $this->getPickupLocations->execute($builder->create())->getItems();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return string[] the cart's shipped items (a configurable's chosen variant, not the parent)
     */
    private function skus(Quote $quote): array
    {
        $skus = [];
        foreach ($quote->getAllItems() as $item) {
            if (!$item->getChildren() && !$item->getIsVirtual()) {
                $skus[] = (string) $item->getSku();
            }
        }

        return array_values(array_unique($skus));
    }
}
