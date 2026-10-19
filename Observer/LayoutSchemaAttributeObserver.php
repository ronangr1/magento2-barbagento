<?php
/**
 * Copyright © Ronangr1. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ronangr1\Barbagento\Observer;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Escaper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Ronangr1\Barbagento\Model\Config;

class LayoutSchemaAttributeObserver implements ObserverInterface
{
    /**
     * @param HttpRequest $request
     * @param Config $config
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly HttpRequest $request,
        private readonly Config $config,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * Add data-barba attributes to main container
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->config->isApplicable()) {
            return;
        }

        $event = $observer->getEvent();
        $elementName = $event->getData('element_name');

        if ('page.wrapper' !== $elementName) {
            return;
        }

        /** @var \Magento\Framework\DataObject|null $transport */
        $transport = $event->getData('transport');
        if (!$transport instanceof \Magento\Framework\DataObject) {
            return;
        }

        $output = $transport->getData('output');

        if (!is_string($output) || '' === $output) {
            return;
        }

        $pattern = '/(<main\b[^>]*\bid="maincontent")/i';

        if (!preg_match($pattern, $output)) {
            return;
        }

        $routeName = (string)($this->request->getRouteName() ?? 'default');
        $namespace = $this->escaper->escapeHtml($routeName);

        $attributesToAdd = sprintf(
            'data-barba="container" data-barba-namespace="%s"',
            $namespace
        );

        $replacement = sprintf('$1 %s', $attributesToAdd);

        $newOutput = preg_replace($pattern, $replacement, $output, 1);

        if (null !== $newOutput) {
            $transport->setData('output', $newOutput);
        }
    }
}
