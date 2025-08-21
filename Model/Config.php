<?php
/**
 * Copyright © Ronangr1, All rights reserved.
 * See LICENSE bundled with this library for license details.
 */
declare(strict_types=1);

namespace Ronangr1\Barbagento\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function isActive(): bool
    {
        return $this->scopeConfig->isSetFlag('barbagento/general/is_active');
    }

    public function getSettings(): array
    {
        $excludedPages = explode(
            ", ",
            $this->scopeConfig
                ->getValue('barbagento/settings/excluded_pages')
        );

        return [
            'general' => [
                'debug' => $this->scopeConfig->getValue('barbagento/settings/debug'),
                'timeout' => $this->scopeConfig->getValue('barbagento/settings/timeout'),
                'excludedPages' => json_encode($excludedPages),
            ],
            'google_tag_manager' => [
                'enable' => $this->scopeConfig->isSetFlag('barbagento/google_tag_manager/enable'),
                'gtagId' => $this->scopeConfig->getValue('barbagento/google_tag_manager/gtag_id'),
            ]
        ];
    }
}
