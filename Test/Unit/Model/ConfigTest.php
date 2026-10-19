<?php
/**
 * Copyright © Ronangr1, All rights reserved.
 * See LICENSE bundled with this library for license details.
 */
declare(strict_types=1);

namespace Ronangr1\Barbagento\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ronangr1\Barbagento\Model\Config;

class ConfigTest extends TestCase
{
    /**
     * @var MockObject&ScopeConfigInterface
     */
    private MockObject $scopeConfigMock;

    /**
     * @var MockObject&SerializerInterface
     */
    private MockObject $serializerMock;

    /**
     * @var MockObject&DataObjectFactory
     */
    private MockObject $dataObjectFactoryMock;

    /**
     * @var MockObject&HttpRequest
     */
    private MockObject $requestMock;

    /**
     * @var Config
     */
    private Config $configModel;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->dataObjectFactoryMock = $this->createMock(DataObjectFactory::class);
        $this->requestMock = $this->createMock(HttpRequest::class);

        $this->configModel = new Config(
            $this->scopeConfigMock,
            $this->serializerMock,
            $this->dataObjectFactoryMock,
            $this->requestMock
        );
    }

    public function testIsActiveReturnsTrueWhenConfigIsEnabled(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->with(Config::XML_PATH_IS_ACTIVE, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->assertTrue($this->configModel->isActive());
    }

    public function testIsActiveReturnsFalseWhenConfigIsDisabled(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->with(Config::XML_PATH_IS_ACTIVE, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);

        $this->assertFalse($this->configModel->isActive());
    }

    public function testGetSettingsReturnsCorrectDataObject(): void
    {
        $timeoutValue = 5000;
        $excludedPagesString = 'cart, checkout, customer/account';
        $serializedPages = '["cart","checkout","customer\/account"]';
        $gtagId = 'GTM-TEST123';

        $this->scopeConfigMock->method('isSetFlag')
            ->willReturnMap([
                [Config::XML_PATH_DEBUG, ScopeInterface::SCOPE_STORE, true],
                [Config::XML_PATH_GTAG_ENABLE, ScopeInterface::SCOPE_STORE, true],
            ]);

        $this->scopeConfigMock->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_TIMEOUT, ScopeInterface::SCOPE_STORE, $timeoutValue],
                [Config::XML_PATH_EXCLUDED_PAGES, ScopeInterface::SCOPE_STORE, $excludedPagesString],
                [Config::XML_PATH_GTAG_ID, ScopeInterface::SCOPE_STORE, $gtagId],
            ]);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with(['cart', 'checkout', 'customer/account'])
            ->willReturn($serializedPages);

        $dataObject = new DataObject([
            'debug' => true,
            'timeout' => $timeoutValue,
            'excluded_pages' => $serializedPages,
            'gtag_enable' => true,
            'gtag_id' => $gtagId,
        ]);

        $this->dataObjectFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => [
                'debug' => true,
                'timeout' => $timeoutValue,
                'excluded_pages' => $serializedPages,
                'gtag_enable' => true,
                'gtag_id' => $gtagId,
            ]])
            ->willReturn($dataObject);

        $result = $this->configModel->getSettings();
        $this->assertSame($dataObject, $result);
        $this->assertTrue($result->getData('debug'));
        $this->assertEquals($timeoutValue, $result->getData('timeout'));
        $this->assertEquals($serializedPages, $result->getData('excluded_pages'));
        $this->assertTrue($result->getData('gtag_enable'));
        $this->assertEquals($gtagId, $result->getData('gtag_id'));
    }

    public function testIsCurrentPageExcludedReturnsTrueForCheckoutModule(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(Config::XML_PATH_EXCLUDED_PAGES, ScopeInterface::SCOPE_STORE)
            ->willReturn('checkout, cart, customer/account');

        $this->requestMock->method('getPathInfo')->willReturn('/checkout/');
        $this->requestMock->method('getModuleName')->willReturn('checkout');
        $this->requestMock->method('getFullActionName')->willReturn('checkout_index_index');

        $this->assertTrue($this->configModel->isCurrentPageExcluded());
    }

    public function testIsCurrentPageExcludedReturnsTrueForCartPage(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(Config::XML_PATH_EXCLUDED_PAGES, ScopeInterface::SCOPE_STORE)
            ->willReturn('checkout, cart, customer/account');

        $this->requestMock->method('getPathInfo')->willReturn('/checkout/cart/');
        $this->requestMock->method('getModuleName')->willReturn('checkout');
        $this->requestMock->method('getFullActionName')->willReturn('checkout_cart_index');

        $this->assertTrue($this->configModel->isCurrentPageExcluded());
    }

    public function testIsCurrentPageExcludedReturnsTrueForCustomerAccount(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(Config::XML_PATH_EXCLUDED_PAGES, ScopeInterface::SCOPE_STORE)
            ->willReturn('checkout, cart, customer/account');

        $this->requestMock->method('getPathInfo')->willReturn('/customer/account/login/');
        $this->requestMock->method('getModuleName')->willReturn('customer');
        $this->requestMock->method('getFullActionName')->willReturn('customer_account_login');

        $this->assertTrue($this->configModel->isCurrentPageExcluded());
    }

    public function testIsCurrentPageExcludedReturnsFalseForCatalogProduct(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(Config::XML_PATH_EXCLUDED_PAGES, ScopeInterface::SCOPE_STORE)
            ->willReturn('checkout, cart, customer/account');

        $this->requestMock->method('getPathInfo')->willReturn('/women/tops-women.html');
        $this->requestMock->method('getModuleName')->willReturn('catalog');
        $this->requestMock->method('getFullActionName')->willReturn('catalog_product_view');

        $this->assertFalse($this->configModel->isCurrentPageExcluded());
    }

    public function testIsCurrentPageExcludedReturnsFalseWhenConfigIsEmpty(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(Config::XML_PATH_EXCLUDED_PAGES, ScopeInterface::SCOPE_STORE)
            ->willReturn('');

        $this->assertFalse($this->configModel->isCurrentPageExcluded());
    }

    public function testIsApplicableReturnsTrueWhenActiveAndNotExcluded(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(Config::XML_PATH_IS_ACTIVE, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with(Config::XML_PATH_EXCLUDED_PAGES, ScopeInterface::SCOPE_STORE)
            ->willReturn('checkout, cart');

        $this->requestMock->method('getPathInfo')->willReturn('/catalog/category/view/');
        $this->requestMock->method('getModuleName')->willReturn('catalog');

        $this->assertTrue($this->configModel->isApplicable());
    }

    public function testIsApplicableReturnsFalseWhenInactive(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(Config::XML_PATH_IS_ACTIVE, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);

        $this->assertFalse($this->configModel->isApplicable());
    }

    public function testIsApplicableReturnsFalseWhenExcluded(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(Config::XML_PATH_IS_ACTIVE, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with(Config::XML_PATH_EXCLUDED_PAGES, ScopeInterface::SCOPE_STORE)
            ->willReturn('checkout, cart');

        $this->requestMock->method('getPathInfo')->willReturn('/checkout/');
        $this->requestMock->method('getModuleName')->willReturn('checkout');

        $this->assertFalse($this->configModel->isApplicable());
    }
}
