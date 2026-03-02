<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\ServicePointCartPage\Dependency\Client;

use Generated\Shared\Transfer\QuoteReplacementResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

class ServicePointCartPageToServicePointCartClientBridge implements ServicePointCartPageToServicePointCartClientInterface
{
    /**
     * @var \Spryker\Client\ServicePointCart\ServicePointCartClientInterface
     */
    protected $servicePointCartClient;

    /**
     * @param \Spryker\Client\ServicePointCart\ServicePointCartClientInterface $servicePointCartClient
     */
    public function __construct($servicePointCartClient)
    {
        $this->servicePointCartClient = $servicePointCartClient;
    }

    public function replaceQuoteItems(QuoteTransfer $quoteTransfer): QuoteReplacementResponseTransfer
    {
        return $this->servicePointCartClient->replaceQuoteItems($quoteTransfer);
    }
}
