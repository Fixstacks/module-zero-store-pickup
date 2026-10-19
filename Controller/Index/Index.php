<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\StorePickup\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Zero\StorePickup\ViewModel\Stores;

/**
 * /storelocator: the website's stores (templates/locator.phtml); a 404 where
 * the website has none. Cacheable: the same for every visitor; ?q= narrows
 * the list.
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly Stores $stores
    ) {
    }

    public function execute()
    {
        // Off, or a website without stores: no empty page to find.
        if (!$this->stores->locatorEnabled() || !$this->stores->hasStores()) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set($this->stores->title());
        $page->getConfig()->setDescription((string) __('Our stores: addresses, opening hours, phone numbers and directions. Order online and pick it up there.'));

        return $page;
    }
}
