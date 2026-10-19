<?php
/**
 * Copyright © Ronangr1, All rights reserved.
 * See LICENSE bundled with this library for license details.
 */
declare(strict_types=1);

namespace Ronangr1\Barbagento\ViewModel;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Ronangr1\Barbagento\Model\Config;

class Init implements ArgumentInterface
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Retrieve settings as DataObject
     *
     * @return DataObject
     */
    public function getSettings(): DataObject
    {
        return $this->config->getSettings();
    }

    /**
     * Check if Barbagento is active and applicable to current page
     *
     * @return bool
     */
    public function isApplicable(): bool
    {
        return $this->config->isApplicable();
    }
}
