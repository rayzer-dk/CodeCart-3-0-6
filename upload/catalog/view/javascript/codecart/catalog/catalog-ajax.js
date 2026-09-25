(function(window, document, $) {
    'use strict';

    if (!$ || !window.DOMParser || !window.history || !window.history.pushState) {
        return;
    }

    var request = null;
    var navigating = false;

    function isCatalogPage() {
        return !!document.querySelector('#product-category, #product-search, #product-special, #product-manufacturer');
    }

    function normalNavigate(url) {
        window.location.href = url;
    }

    function sameOrigin(url) {
        try {
            var parsed = new URL(url, window.location.href);
            return parsed.origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function syncSingle(parsed, selector, attribute) {
        var incoming = parsed.querySelector(selector);
        var current = document.querySelector(selector);

        if (!incoming) {
            if (current && current.parentNode) {
                current.parentNode.removeChild(current);
            }
            return;
        }

        if (!current) {
            current = incoming.cloneNode(true);
            document.head.appendChild(current);
            return;
        }

        if (attribute) {
            current.setAttribute(attribute, incoming.getAttribute(attribute) || '');
        } else {
            current.setAttribute('content', incoming.getAttribute('content') || '');
        }
    }

    function syncAlternates(parsed) {
        var current = document.querySelectorAll('link[rel="alternate"][hreflang]');
        Array.prototype.forEach.call(current, function(node) {
            if (node.parentNode) { node.parentNode.removeChild(node); }
        });

        var incoming = parsed.querySelectorAll('link[rel="alternate"][hreflang]');
        Array.prototype.forEach.call(incoming, function(node) {
            document.head.appendChild(node.cloneNode(true));
        });
    }

    function safeReplace(html, url, pushState) {
        var parsed = new DOMParser().parseFromString(html, 'text/html');
        var incoming = parsed.querySelector('#content');
        var current = document.querySelector('#content');

        if (!incoming || !current) {
            return false;
        }

        // CodeCart PRO module assets are intentionally loaded on demand. If AJAX
        // navigation reaches a layout that needs an engine which was not part of
        // the current page, do a normal navigation so the new document can load
        // its CSS/JS in the regular OpenCart asset pipeline.
        if (incoming.querySelector('.ccp-native-module--carousel, .ccp-native-product-module--carousel, .ccp-sidebar-module, .ccp-filter-module') && !window.CodeCartNativeModules) {
            return false;
        }
        if (incoming.querySelector('[data-ccp-slider]') && !window.CodeCartSlider) {
            return false;
        }

        // Third-party modules often inject inline scripts into catalog output.
        // Instead of attempting to re-run unknown code, fall back to a normal page load.
        if (incoming.querySelector('script')) {
            return false;
        }

        current.className = incoming.className;
        current.innerHTML = incoming.innerHTML;

        if (parsed.title) {
            document.title = parsed.title;
        }

        syncSingle(parsed, 'link[rel="canonical"]', 'href');
        syncSingle(parsed, 'meta[name="robots"]');
        syncSingle(parsed, 'meta[name="description"]');
        syncAlternates(parsed);

        if (pushState !== false) {
            window.history.pushState({codecartCatalogAjax: true}, '', url);
        }

        if (window.CodeCartApplyProductDisplay) {
            window.CodeCartApplyProductDisplay(true);
        }

        $('[data-toggle="tooltip"]').tooltip({container: 'body'});
        $(document).trigger('codecart:content-updated', [current]);
        $(document).trigger('codecart:catalogUpdated', [url]);

        var target = $('#content');
        if (target.length) {
            $('html, body').stop(true).animate({scrollTop: Math.max(0, target.offset().top - 12)}, 180);
        }

        return true;
    }

    function navigate(url, options) {
        options = options || {};

        if (!isCatalogPage() || !url || !sameOrigin(url) || navigating) {
            if (url && !navigating) { normalNavigate(url); }
            return;
        }

        navigating = true;
        $('body').addClass('ccp-catalog-loading');
        $('#content').attr('aria-busy', 'true');

        if (request && request.readyState !== 4) {
            request.abort();
        }

        request = $.ajax({
            url: url,
            type: 'get',
            dataType: 'html',
            cache: false,
            headers: {'X-CodeCart-Catalog-Ajax': '1'},
            timeout: 15000,
            success: function(html) {
                if (!safeReplace(html, url, options.pushState)) {
                    normalNavigate(url);
                }
            },
            error: function(xhr, status) {
                if (status !== 'abort') {
                    normalNavigate(url);
                }
            },
            complete: function() {
                navigating = false;
                $('body').removeClass('ccp-catalog-loading');
                $('#content').removeAttr('aria-busy');
            }
        });
    }

    $(document).on('click', '#content .pagination a', function(event) {
        if (!isCatalogPage() || event.which !== 1 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        var href = this.href;
        if (!href || !sameOrigin(href)) {
            return;
        }

        event.preventDefault();
        navigate(href);
    });

    window.addEventListener('popstate', function() {
        // Full reload on Back/Forward keeps third-party filters and side columns exact.
        window.location.reload();
    });

    window.CodeCartCatalogAjax = {
        navigate: navigate
    };
})(window, document, window.jQuery);
