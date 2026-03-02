<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\ServicePointCartPage\Replacer;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteReplacementResponseTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerShop\Yves\ServicePointCartPage\Dependency\Client\ServicePointCartPageToQuoteClientInterface;
use SprykerShop\Yves\ServicePointCartPage\Dependency\Client\ServicePointCartPageToServicePointCartClientInterface;
use SprykerShop\Yves\ServicePointCartPage\MessageAdder\MessageAdderInterface;
use SprykerShop\Yves\ServicePointCartPage\ServicePointCartPageConfig;

class QuoteItemReplacer implements QuoteItemReplacerInterface
{
    /**
     * @var \SprykerShop\Yves\ServicePointCartPage\MessageAdder\MessageAdderInterface
     */
    protected MessageAdderInterface $messageAdder;

    /**
     * @var \SprykerShop\Yves\ServicePointCartPage\Dependency\Client\ServicePointCartPageToServicePointCartClientInterface
     */
    protected ServicePointCartPageToServicePointCartClientInterface $servicePointCartClient;

    /**
     * @var \SprykerShop\Yves\ServicePointCartPage\Dependency\Client\ServicePointCartPageToQuoteClientInterface
     */
    protected ServicePointCartPageToQuoteClientInterface $quoteClient;

    /**
     * @var \SprykerShop\Yves\ServicePointCartPage\ServicePointCartPageConfig
     */
    protected ServicePointCartPageConfig $servicePointCartPageConfig;

    public function __construct(
        MessageAdderInterface $messageAdder,
        ServicePointCartPageToServicePointCartClientInterface $servicePointCartClient,
        ServicePointCartPageToQuoteClientInterface $quoteClient,
        ServicePointCartPageConfig $servicePointCartPageConfig
    ) {
        $this->servicePointCartClient = $servicePointCartClient;
        $this->messageAdder = $messageAdder;
        $this->quoteClient = $quoteClient;
        $this->servicePointCartPageConfig = $servicePointCartPageConfig;
    }

    public function replaceQuoteItems(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        $quoteReplacementResponseTransfer = $this->servicePointCartClient->replaceQuoteItems($quoteTransfer);

        $isQuoteResponseSuccessful = $this->getIsQuoteResponseSuccessful($quoteReplacementResponseTransfer);
        $quoteResponseTransfer = (new QuoteResponseTransfer())
            ->setQuoteTransfer($quoteReplacementResponseTransfer->getQuoteOrFail())
            ->setIsSuccessful($isQuoteResponseSuccessful);

        if ($quoteReplacementResponseTransfer->getFailedReplacementItems()->count() !== 0) {
            $this->unsetPropertiesForFailedReplacementItems($quoteReplacementResponseTransfer);
        }

        if ($quoteReplacementResponseTransfer->getErrors()->count() !== 0) {
            $this->messageAdder->addQuoteResponseErrors($quoteReplacementResponseTransfer->getErrors());
        }

        $this->quoteClient->setQuote($quoteReplacementResponseTransfer->getQuoteOrFail());

        return $quoteResponseTransfer;
    }

    protected function unsetPropertiesForFailedReplacementItems(QuoteReplacementResponseTransfer $quoteReplacementResponseTransfer): void
    {
        $failedItemGroupKeys = [];
        foreach ($quoteReplacementResponseTransfer->getFailedReplacementItems() as $itemTransfer) {
            $failedItemGroupKeys[] = $itemTransfer->getGroupKeyOrFail();
        }

        foreach ($quoteReplacementResponseTransfer->getQuoteOrFail()->getItems() as $itemTransfer) {
            if (in_array($itemTransfer->getGroupKeyOrFail(), $failedItemGroupKeys, true)) {
                $this->unsetPropertiesForFailedReplacementItem($itemTransfer);
            }
        }
    }

    protected function unsetPropertiesForFailedReplacementItem(ItemTransfer $itemTransfer): void
    {
        $itemTransferProperties = $this->servicePointCartPageConfig->getQuoteItemFieldsAllowedForReset();
        foreach ($itemTransferProperties as $itemTransferProperty) {
            $itemTransfer->offsetSet($itemTransferProperty, null);
        }
    }

    protected function getIsQuoteResponseSuccessful(QuoteReplacementResponseTransfer $quoteReplacementResponseTransfer): bool
    {
        return $quoteReplacementResponseTransfer->getErrors()->count() === 0
            || $quoteReplacementResponseTransfer->getFailedReplacementItems()->count() === 0;
    }
}
