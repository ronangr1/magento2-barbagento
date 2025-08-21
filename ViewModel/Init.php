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
    public function __construct(
        private readonly Config $config
    )
    {
    }

    public function getSettings(): DataObject
    {
        $arraySettings = $this->config->getSettings();
        return new DataObject([
            'debug' => $arraySettings['general']['debug'],
            'timeout' => $arraySettings['general']['timeout'],
            'excluded_pages' => $arraySettings['general']['excludedPages'],
            'gtag_enable' => $arraySettings['google_tag_manager']['enable'],
            'gtag_id' => $arraySettings['google_tag_manager']['gtagId'],
        ]);
    }
}
