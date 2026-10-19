<?php
/**
 * Copyright © Ronangr1. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ronangr1\Barbagento\Test\Unit\Observer;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ronangr1\Barbagento\Model\Config;
use Ronangr1\Barbagento\Observer\LayoutSchemaAttributeObserver;

class LayoutSchemaAttributeObserverTest extends TestCase
{
    /**
     * @var MockObject&HttpRequest
     */
    private MockObject $requestMock;

    /**
     * @var MockObject&Config
     */
    private MockObject $configMock;

    /**
     * @var Escaper
     */
    private Escaper $escaper;

    /**
     * @var LayoutSchemaAttributeObserver
     */
    private LayoutSchemaAttributeObserver $observerInstance;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(HttpRequest::class);
        $this->configMock = $this->createMock(Config::class);
        $this->escaper = new Escaper();

        $this->observerInstance = new LayoutSchemaAttributeObserver(
            $this->requestMock,
            $this->configMock,
            $this->escaper
        );
    }

    public function testExecuteDoesNothingWhenModuleIsDisabled(): void
    {
        $this->configMock->method('isApplicable')->willReturn(false);

        $observer = new Observer();
        $this->observerInstance->execute($observer);
        $this->assertNull($observer->getEvent());
    }

    public function testExecuteDoesNothingForIncorrectElementName(): void
    {
        $this->configMock->method('isApplicable')->willReturn(true);

        $transport = new DataObject(['output' => '<main id="maincontent">']);
        $event = new Event([
            'element_name' => 'some_other_element',
            'transport' => $transport
        ]);
        $observer = new Observer(['event' => $event]);

        $this->observerInstance->execute($observer);
        $this->assertEquals('<main id="maincontent">', $transport->getData('output'));
    }

    /**
     * @dataProvider invalidOutputDataProvider
     * @param mixed $output
     */
    #[DataProvider('invalidOutputDataProvider')]
    public function testExecuteDoesNothingForEmptyOrInvalidOutput(mixed $output): void
    {
        $this->configMock->method('isApplicable')->willReturn(true);

        $transport = new DataObject(['output' => $output]);
        $event = new Event([
            'element_name' => 'page.wrapper',
            'transport' => $transport
        ]);
        $observer = new Observer(['event' => $event]);

        $this->observerInstance->execute($observer);
        $this->assertEquals($output, $transport->getData('output'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function invalidOutputDataProvider(): array
    {
        return [
            'empty string' => [''],
            'null value' => [null],
            'integer value' => [12345],
        ];
    }

    public function testExecuteDoesNothingWhenMainContentTagIsMissing(): void
    {
        $htmlWithoutMainTag = '<div><p>Some content without the main tag.</p></div>';

        $this->configMock->method('isApplicable')->willReturn(true);

        $transport = new DataObject(['output' => $htmlWithoutMainTag]);
        $event = new Event([
            'element_name' => 'page.wrapper',
            'transport' => $transport
        ]);
        $observer = new Observer(['event' => $event]);

        $this->observerInstance->execute($observer);
        $this->assertEquals($htmlWithoutMainTag, $transport->getData('output'));
    }

    public function testExecuteModifiesOutputOnHappyPath(): void
    {
        $routeName = 'catalog_product_view';
        $initialHtml = '<div class="page-wrapper"><main id="maincontent" class="page-main">';
        $expectedHtml = sprintf(
            '<div class="page-wrapper">' .
            '<main id="maincontent" data-barba="container" data-barba-namespace="%s" class="page-main">',
            $routeName
        );

        $this->configMock->method('isApplicable')->willReturn(true);
        $this->requestMock->method('getRouteName')->willReturn($routeName);

        $transport = new DataObject(['output' => $initialHtml]);
        $event = new Event([
            'element_name' => 'page.wrapper',
            'transport' => $transport
        ]);
        $observer = new Observer(['event' => $event]);

        $this->observerInstance->execute($observer);
        $this->assertEquals($expectedHtml, $transport->getData('output'));
    }
}
