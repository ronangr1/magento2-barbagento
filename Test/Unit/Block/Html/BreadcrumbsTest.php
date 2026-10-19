<?php
/**
 * Copyright © Ronangr1. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ronangr1\Barbagento\Test\Unit\Block\Html;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Ronangr1\Barbagento\Block\Html\Breadcrumbs;

class BreadcrumbsTest extends TestCase
{
    /**
     * @var MockObject&Registry
     */
    private MockObject $registryMock;

    /**
     * @var MockObject&CatalogHelper
     */
    private MockObject $catalogHelperMock;

    /**
     * @var MockObject&Product
     */
    private MockObject $productMock;

    /**
     * @var Breadcrumbs
     */
    private Breadcrumbs $block;

    protected function setUp(): void
    {
        $this->registryMock = $this->createMock(Registry::class);
        $this->catalogHelperMock = $this->createMock(CatalogHelper::class);

        $eventManagerMock = $this->createMock(ManagerInterface::class);
        $urlBuilderMock = $this->createMock(UrlInterface::class);
        $urlBuilderMock->method('getBaseUrl')->willReturn('http://localhost/');
        $layoutMock = $this->createMock(LayoutInterface::class);
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->method('getBaseUrl')->willReturn('http://localhost/');
        $escaperMock = $this->createMock(Escaper::class);
        $scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $cacheStateMock = $this->createMock(\Magento\Framework\App\Cache\StateInterface::class);
        $cacheStateMock->method('isEnabled')->willReturn(false);

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getEventManager')->willReturn($eventManagerMock);
        $contextMock->method('getUrlBuilder')->willReturn($urlBuilderMock);
        $contextMock->method('getLayout')->willReturn($layoutMock);
        $contextMock->method('getStoreManager')->willReturn($storeManagerMock);
        $contextMock->method('getEscaper')->willReturn($escaperMock);
        $contextMock->method('getScopeConfig')->willReturn($scopeConfigMock);
        $contextMock->method('getCacheState')->willReturn($cacheStateMock);
        $storeManagerMock->method('getStore')->willReturn($storeMock);
        $escaperMock->method('escapeHtml')->willReturnArgument(0);

        $this->block = new Breadcrumbs(
            $this->registryMock,
            $contextMock,
            $this->catalogHelperMock
        );

        $this->productMock = $this->createMock(Product::class);
    }

    public function testToHtmlGeneratesCorrectCrumbsWithProduct(): void
    {
        $this->catalogHelperMock->method('getBreadcrumbPath')->willReturn([]);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($this->productMock);

        $productName = 'Super Test Product';
        $this->productMock->method('getName')->willReturn($productName);

        $this->block->toHtml();

        $crumbs = $this->block->getData('crumbs');
        $this->assertIsArray($crumbs);
        $this->assertArrayHasKey('home', $crumbs);
        $this->assertArrayHasKey($productName, $crumbs);
        $this->assertTrue($crumbs['home']['first']);
        $this->assertTrue($crumbs[$productName]['last']);
    }

    public function testToHtmlHandlesNullProductGracefully(): void
    {
        $this->catalogHelperMock->method('getBreadcrumbPath')->willReturn([]);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn(null);

        $this->block->toHtml();

        $crumbs = $this->block->getData('crumbs');
        $this->assertIsArray($crumbs);
        $this->assertArrayHasKey('home', $crumbs);
        $this->assertCount(1, $crumbs);
    }

    public function testToHtmlUsesCatalogBreadcrumbsWhenAvailable(): void
    {
        $categoryPath = [
            'category_1' => ['label' => 'Gear', 'link' => 'http://localhost/gear'],
            'product' => ['label' => 'Super Test Product', 'link' => '']
        ];

        $this->catalogHelperMock->method('getBreadcrumbPath')->willReturn($categoryPath);
        $this->registryMock->expects($this->never())->method('registry');

        $this->block->toHtml();

        $crumbs = $this->block->getData('crumbs');
        $this->assertIsArray($crumbs);
        $this->assertArrayHasKey('home', $crumbs);
        $this->assertArrayHasKey('category_1', $crumbs);
        $this->assertArrayHasKey('product', $crumbs);
        $this->assertTrue($crumbs['home']['first']);
        $this->assertTrue($crumbs['product']['last']);
    }

    public function testAddCrumbAddsDataToInternalArray(): void
    {
        $crumbName = 'test_crumb';
        $crumbInfo = [
            'label' => 'Test Label',
            'title' => 'Test Title',
            'link' => '/test.html'
        ];

        $this->block->addCrumb($crumbName, $crumbInfo);

        $reflection = new ReflectionClass($this->block);
        $crumbsProperty = $reflection->getProperty('crumbs');
        $internalCrumbs = $crumbsProperty->getValue($this->block);

        $this->assertArrayHasKey($crumbName, $internalCrumbs);
        $this->assertEquals($crumbInfo['label'], $internalCrumbs[$crumbName]['label']);
        $this->assertEquals($crumbInfo['link'], $internalCrumbs[$crumbName]['link']);
        $this->assertNull($internalCrumbs[$crumbName]['last']);
    }
}
