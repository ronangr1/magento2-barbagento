/**
 * Copyright © Ronangr1. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'barbajs',
    'Magento_Customer/js/customer-data',
    'mage/cookies'
], function ($, barba, customerData) {
    'use strict';

    return {
        config: null,

        init: function (config) {
            if (!this.config) {
                this.config = config;
            }

            this.preventOnExcludedLinks();

            if (barba.prefetch && typeof barba.prefetch.install === 'function') {
                barba.use(barba.prefetch);
            }

            barba.init({
                debug: Boolean(this.config.debug),
                timeout: Number(this.config.timeout) || 7000,
                prevent: ({ el, href }) => {
                    if (!href) {
                        return false;
                    }
                    if (!this.config || !Array.isArray(this.config.excludedPages)) {
                        return false;
                    }
                    const lowerHref = href.toLowerCase();
                    return this.config.excludedPages.some(function (page) {
                        if (!page || typeof page !== 'string') {
                            return false;
                        }
                        const trimmed = page.trim().toLowerCase();
                        return trimmed !== '' && lowerHref.indexOf(trimmed) !== -1;
                    });
                },
                transitions: [{
                    name: 'default-transition',
                    leave: function (data) {
                        return $(data.current.container).animate({opacity: 0}, 250).promise();
                    },
                    enter: function (data) {
                        window.scrollTo(0, 0);
                        $(data.next.container).css({opacity: 0, visibility: 'hidden'});
                    },
                    after: (data) => {
                        this.updatePageSpecifics(data);
                        $(data.next.container).css('visibility', 'visible').animate({opacity: 1}, 250);
                    }
                }]
            });

            if (this.config.gtagEnable) {
                barba.hooks.after(function () {
                    if (typeof window.gtag === 'function') {
                        window.gtag('event', 'page_view', {
                            'page_title': document.title,
                            'page_location': window.location.href,
                            'page_path': window.location.pathname
                        });
                    }
                });
            }

            $(document).on('contentUpdated', () => this.preventOnExcludedLinks());
        },

        updatePageSpecifics: function (data) {
            this.updateHead(data);
            this.updateBodyClass(data);
            this.updateBreadcrumbs(data);
            this.updateFormKeys();
            this.reapplyMagentoScripts(data);
        },

        updateHead: function (data) {
            const head = document.head;
            if (!data.next || !data.next.html) {
                return;
            }

            const newPageRawHead = data.next.html.match(/<head[^>]*>([\s\S]*)<\/head>/i);
            if (!newPageRawHead || !newPageRawHead[0]) {
                return;
            }

            const newPageHead = document.createElement('head');
            newPageHead.innerHTML = newPageRawHead[0];

            const newTitle = newPageHead.querySelector('title');
            if (newTitle) {
                document.title = newTitle.innerText || '';
            }

            const currentStyles = Array.from(head.querySelectorAll('link[rel="stylesheet"], style'));
            const newStyles = Array.from(newPageHead.querySelectorAll('link[rel="stylesheet"], style'));

            const currentHrefs = new Set(currentStyles.map(function (s) {
                return s.href || s.innerText;
            }));

            newStyles.forEach(function (newStyle) {
                const identifier = newStyle.href || newStyle.innerText;
                if (!currentHrefs.has(identifier)) {
                    head.appendChild(newStyle.cloneNode(true));
                }
            });
        },

        updateBreadcrumbs: function (data) {
            const breadcrumbsSelector = '.breadcrumbs';
            const mainContentAnchor = 'main#maincontent';

            if (!data.next || !data.next.html) {
                return;
            }

            const newBreadcrumbsNode = $(data.next.html).find(breadcrumbsSelector);
            const existingContainer = $(breadcrumbsSelector);

            if (newBreadcrumbsNode.length) {
                const newContent = newBreadcrumbsNode.html();

                if (existingContainer.length) {
                    existingContainer.html(newContent).show();
                } else {
                    $(mainContentAnchor).before(newBreadcrumbsNode);
                }
            } else if (existingContainer.length) {
                existingContainer.remove();
            }
        },

        reapplyMagentoScripts: function (data) {
            if (data.next && data.next.container) {
                $(data.next.container).trigger('contentUpdated');
            }
        },

        preventOnExcludedLinks: function () {
            if (!this.config || !Array.isArray(this.config.excludedPages)) {
                return;
            }

            this.config.excludedPages.forEach(function (path) {
                if (typeof path === 'string' && path.trim() !== '') {
                    const cleanPath = path.trim().toLowerCase();
                    $('a[href*="' + cleanPath + '"], button[data-action*="' + cleanPath + '"]').attr('data-barba-prevent', 'all');
                }
            });
        },

        updateBodyClass: function (data) {
            if (!data.next || !data.next.html) {
                return;
            }

            const bodyClasses = data.next.html.match(/<body[^>]*class="([^"]*)"/);
            document.body.className = (bodyClasses && bodyClasses[1]) ? bodyClasses[1] : '';
        },

        updateFormKeys: function () {
            if ($.mage && $.mage.cookies) {
                const newFormKey = $.mage.cookies.get('form_key');
                if (newFormKey) {
                    $('input[name="form_key"]').val(newFormKey);
                }
            }
        },

        reloadPrivateContent: function () {
            // Maintained for backward compatibility; Magento automatically invalidates
            // customerData sections via sections.xml based on actions and cookies.
        }
    };
});
