<?php
/**
 * Copyright © Ronangr1. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ronangr1\Barbagento\Block\Html;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;

class Breadcrumbs extends \Magento\Catalog\Block\Breadcrumbs
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $crumbs = [];

    /**
     * @var string[]
     */
    protected array $properties = ['label', 'title', 'link', 'first', 'last', 'readonly'];

    /**
     * @param Registry $registry
     * @param Context $context
     * @param Data $catalogData
     * @param mixed[] $data
     */
    public function __construct(
        private readonly Registry $registry,
        Context $context,
        Data $catalogData,
        array $data = []
    ) {
        parent::__construct($context, $catalogData, $data);
    }

    /**
     * Add crumb to internal list
     *
     * @param string $crumbName
     * @param mixed[] $crumbInfo
     * @return $this
     */
    public function addCrumb($crumbName, array $crumbInfo): static
    {
        foreach ($this->properties as $key) {
            if (!isset($crumbInfo[$key])) {
                $crumbInfo[$key] = null;
            }
        }

        if (!isset($this->crumbs[$crumbName]) || empty($this->crumbs[$crumbName]['readonly'])) {
            $this->crumbs[$crumbName] = $crumbInfo;
        }

        return $this;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        $this->addCrumb(
            'home',
            [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->getBaseUrl()
            ]
        );

        $path = $this->_catalogData->getBreadcrumbPath();
        if (!empty($path)) {
            foreach ($path as $name => $breadcrumb) {
                $this->addCrumb((string)$name, $breadcrumb);
            }
        } else {
            /** @var Product|null $currentProduct */
            $currentProduct = $this->registry->registry('current_product');
            if ($currentProduct instanceof Product && $currentProduct->getName()) {
                $this->addCrumb(
                    $currentProduct->getName(),
                    [
                        'label' => $currentProduct->getName(),
                        'title' => '',
                        'link' => ''
                    ]
                );
            }
        }

        if (!empty($this->crumbs)) {
            reset($this->crumbs);
            $firstKey = key($this->crumbs);
            if ($firstKey !== null) {
                $this->crumbs[$firstKey]['first'] = true;
            }

            end($this->crumbs);
            $lastKey = key($this->crumbs);
            if ($lastKey !== null) {
                $this->crumbs[$lastKey]['last'] = true;
            }
        }

        $this->setData('crumbs', $this->crumbs);
        $this->assign('crumbs', $this->crumbs);

        return parent::_toHtml();
    }

    /**
     * Get breadcrumbs array
     *
     * @return array<string, array<string, mixed>>
     */
    public function getCrumbs(): array
    {
        return $this->crumbs;
    }
}
