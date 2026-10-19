<?php
/**
 * Copyright © Ronangr1, All rights reserved.
 * See LICENSE bundled with this library for license details.
 */
declare(strict_types=1);

namespace Ronangr1\Barbagento\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_IS_ACTIVE = 'barbagento/general/is_active';

    public const XML_PATH_DEBUG = 'barbagento/settings/debug';

    public const XML_PATH_TIMEOUT = 'barbagento/settings/timeout';

    public const XML_PATH_EXCLUDED_PAGES = 'barbagento/settings/excluded_pages';

    public const XML_PATH_GTAG_ENABLE = 'barbagento/google_tag_manager/enable';

    public const XML_PATH_GTAG_ID = 'barbagento/google_tag_manager/gtag_id';
    
    public const DEFAULT_TIMEOUT = 7000;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param DataObjectFactory $dataObjectFactory
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SerializerInterface $serializer,
        private readonly DataObjectFactory $dataObjectFactory,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Check if module is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IS_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve list of excluded pages
     *
     * @return string[]
     */
    public function getExcludedPages(): array
    {
        $raw = (string)$this->scopeConfig->getValue(
            self::XML_PATH_EXCLUDED_PAGES,
            ScopeInterface::SCOPE_STORE
        );

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Check if current page is in excluded pages list
     *
     * @return bool
     */
    public function isCurrentPageExcluded(): bool
    {
        $excludedPages = $this->getExcludedPages();
        if (empty($excludedPages)) {
            return false;
        }

        $path = $this->request instanceof HttpRequest
            ? '/' . trim((string)$this->request->getPathInfo(), '/') . '/'
            : '';
        $action = $this->request instanceof HttpRequest
            ? (string)$this->request->getFullActionName()
            : '';
        $module = (string)$this->request->getModuleName();

        foreach ($excludedPages as $excluded) {
            $p = trim($excluded, '/');
            $actionP = str_replace('/', '_', $p);

            if (($path !== '//' && str_contains($path, '/' . $p . '/'))
                || strcasecmp($module, $p) === 0
                || ($action !== '' && (strcasecmp($action, $actionP) === 0 || str_starts_with($action, $actionP . '_')))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if module is active and applicable to current page
     *
     * @return bool
     */
    public function isApplicable(): bool
    {
        return $this->isActive() && !$this->isCurrentPageExcluded();
    }

    /**
     * Get module configuration settings
     *
     * @return DataObject
     */
    public function getSettings(): DataObject
    {
        return $this->dataObjectFactory->create([
            'data' => [
                'debug' => $this->scopeConfig->isSetFlag(
                    self::XML_PATH_DEBUG,
                    ScopeInterface::SCOPE_STORE
                ),
                'timeout' => (int)($this->scopeConfig->getValue(
                    self::XML_PATH_TIMEOUT,
                    ScopeInterface::SCOPE_STORE
                ) ?: self::DEFAULT_TIMEOUT),
                'excluded_pages' => $this->serializer->serialize($this->getExcludedPages()),
                'gtag_enable' => $this->scopeConfig->isSetFlag(
                    self::XML_PATH_GTAG_ENABLE,
                    ScopeInterface::SCOPE_STORE
                ),
                'gtag_id' => (string)($this->scopeConfig->getValue(
                    self::XML_PATH_GTAG_ID,
                    ScopeInterface::SCOPE_STORE
                ) ?? ''),
            ]
        ]);
    }
}
