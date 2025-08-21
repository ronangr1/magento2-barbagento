/**
 * Copyright © Ronangr1. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'barbajs',
    'Magento_Customer/js/customer-data',
    'mage/cookies',
], function ($, barba, customerData) {
    'use strict'

    return {
        config: null,

        init: function (config) {
            if (!this.config) {
                this.config = config;
            }

            this.preventOnExcludedLinks();

            if (barba.prefetch) {
                barba.use(barba.prefetch);
            }

            barba.init({
                debug: this.config.debug,
                timeout: config.timeout,
                transitions: [{
                    name: 'default-transition',
                    leave: (data) => {
                        return $(data.current.container).animate({opacity: 0}, 250).promise();
                    },
                    enter: (data) => {
                        window.scrollTo(0, 0);
                        $(data.next.container).css({opacity: 0, visibility: 'hidden'});
                    },
                    after: (data) => {
                        this.updatePageSpecifics(data);
                        $(data.next.container).css('visibility', 'visible').animate({opacity: 1}, 250);
                    }
                }]
            });

            const gtagEnable = config.gtagEnable;
            if (gtagEnable == 1) {
                barba.hooks.after(() => {
                    gtag('event', 'page_view', {
                        'page_title': document.title,
                        'page_location': location.href,
                        'page_path': location.pathname,
                    });
                });
            }


            $(document).on('contentUpdated', () => this.preventOnExcludedLinks());
        },

        updatePageSpecifics: function (data) {
            this.updateHead(data);
            this.updateBodyClass(data);
            this.updateBreadcrumbs(data);
            this.updateFormKeys();
            this.reloadPrivateContent();
            this.reapplyMagentoScripts(data);
        },

        updateHead: function (data) {
            const head = document.head;
            const newPageRawHead = data.next.html.match(/<head[^>]*>([\s\S]*)<\/head>/i);
            if (!newPageRawHead || !newPageRawHead[0]) return;

            const newPageHead = document.createElement('head');
            newPageHead.innerHTML = newPageRawHead[0];

            document.title = newPageHead.querySelector('title')?.innerText || '';

            const currentStyles = Array.from(head.querySelectorAll('link[rel="stylesheet"], style'));
            const newStyles = Array.from(newPageHead.querySelectorAll('link[rel="stylesheet"], style'));

            const currentHrefs = new Set(currentStyles.map(s => s.href || s.innerText));
            newStyles.forEach(newStyle => {
                if (!currentHrefs.has(newStyle.href || newStyle.innerText)) {
                    head.appendChild(newStyle.cloneNode(true));
                }
            })
        },

        updateBreadcrumbs: function (data) {
            const breadcrumbsSelector = '.breadcrumbs';
            const mainContentAnchor = 'main#maincontent';

            const newBreadcrumbsNode = $(data.next.html).find(breadcrumbsSelector);
            const existingContainer = $(breadcrumbsSelector);

            if (newBreadcrumbsNode.length) {
                const newContent = newBreadcrumbsNode.html();

                if (existingContainer.length) {
                    existingContainer.html(newContent).show();
                } else {
                    $(mainContentAnchor).before(newBreadcrumbsNode);
                }
            } else {
                if (existingContainer.length) {
                    existingContainer.remove();
                }
            }
        },

        reapplyMagentoScripts: function (data) {
            const container = data.next.container;

            $(container).trigger('contentUpdated');
        },

        preventOnExcludedLinks: function () {
            this.config.excludedPages.forEach(path => {
                $(`a[href*="${path}"]`).attr('data-barba-prevent', 'all');
            })
        },

        updateBodyClass: function (data) {
            const bodyClasses = data.next.html.match(/<body[^>]*class="([^"]*)"/);
            document.body.className = (bodyClasses && bodyClasses[1]) ? bodyClasses[1] : '';
        },

        updateFormKeys: function () {
            const newFormKey = $.mage.cookies.get('form_key');
            if (newFormKey) {
                $('input[name="form_key"]').val(newFormKey);
            }
        },

        reloadPrivateContent: function () {
            customerData.reload(['cart', 'customer'], false);
        }
    }
})
