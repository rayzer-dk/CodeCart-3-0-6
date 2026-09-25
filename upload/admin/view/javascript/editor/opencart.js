(function(window, document) {
    'use strict';

    var loadedScripts = {};
    var loadedStyles = {};
    var VERSION = '3.0.6.0';

    function editorNodes(root) {
        return (root || document).querySelectorAll('[data-toggle="summernote"]');
    }

    function hasEditors() {
        return editorNodes(document).length > 0;
    }

    function versioned(url) {
        return url + (url.indexOf('?') < 0 ? '?' : '&') + 'v=' + VERSION;
    }

    function loadScript(src, test) {
        if (typeof test === 'function' && test()) {
            return Promise.resolve();
        }
        if (loadedScripts[src]) {
            return loadedScripts[src];
        }
        loadedScripts[src] = new Promise(function(resolve, reject) {
            var existing = document.querySelector('script[data-ccp-src="' + src.replace(/"/g, '\\"') + '"]');
            if (existing) {
                if (existing.getAttribute('data-loaded') === '1' || (typeof test === 'function' && test())) {
                    resolve();
                    return;
                }
                existing.addEventListener('load', resolve, {once:true});
                existing.addEventListener('error', reject, {once:true});
                return;
            }
            var script = document.createElement('script');
            script.src = versioned(src);
            script.async = false;
            script.setAttribute('data-ccp-src', src);
            script.onload = function() {
                script.setAttribute('data-loaded', '1');
                resolve();
            };
            script.onerror = function() {
                reject(new Error('Unable to load ' + src));
            };
            document.head.appendChild(script);
        });
        return loadedScripts[src];
    }

    function loadStyle(href) {
        if (loadedStyles[href] || document.querySelector('link[data-ccp-href="' + href.replace(/"/g, '\\"') + '"]')) {
            return;
        }
        loadedStyles[href] = true;
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = versioned(href);
        link.setAttribute('data-ccp-href', href);
        document.head.appendChild(link);
    }

    function initSummernote() {
        if (!hasEditors()) {
            return Promise.resolve();
        }

        // CodeMirror 5 stays completely lazy: it is requested only on admin pages
        // that actually contain a Summernote editor. XML mode provides HTML tag/attribute
        // highlighting without adding a second editor stack to every admin request.
        loadStyle('view/javascript/codemirror/lib/codemirror.css');
        loadStyle('view/stylesheet/codecart-editor.css');
        loadStyle('view/javascript/summernote/summernote.min.css');

        return loadScript('view/javascript/codemirror/lib/codemirror.js', function(){ return !!window.CodeMirror; })
            .then(function(){ return loadScript('view/javascript/codemirror/lib/xml.js', function(){ return !!(window.CodeMirror && window.CodeMirror.modes && window.CodeMirror.modes.xml); }); })
            .then(function(){ return loadScript('view/javascript/summernote/summernote.min.js', function(){ return !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.summernote); }); })
            .then(function(){ return loadScript('view/javascript/summernote/summernote-image-attributes.js'); })
            .then(function(){ return loadScript('view/javascript/summernote/opencart.js?v=3.0.6.0', function(){ return typeof window.CodeCartSummernoteInit === 'function'; }); })
            .then(function(){
                if (typeof window.CodeCartSummernoteInit === 'function') {
                    window.CodeCartSummernoteInit(document);
                }
            });
    }

    function initialize() {
        if (!hasEditors()) {
            return;
        }
        initSummernote().catch(function(error) {
            if (window.console && console.error) {
                console.error('CodeCart PRO Editor initialization failed.', error);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, {once:true});
    } else {
        initialize();
    }

    var observer = new MutationObserver(function(mutations) {
        for (var i = 0; i < mutations.length; i++) {
            for (var n = 0; n < mutations[i].addedNodes.length; n++) {
                var node = mutations[i].addedNodes[n];
                if (node.nodeType === 1 && (node.matches && node.matches('[data-toggle="summernote"]') || node.querySelector && node.querySelector('[data-toggle="summernote"]'))) {
                    initialize();
                    return;
                }
            }
        }
    });
    if (document.documentElement) {
        observer.observe(document.documentElement, {childList:true, subtree:true});
    }
})(window, document);
