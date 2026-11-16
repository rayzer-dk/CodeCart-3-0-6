(function () {
    'use strict';

    const context = document.modelContext;
    const script = document.currentScript;
    if (!window.isSecureContext || window.top !== window || !context ||
        typeof context.registerTool !== 'function' || !script) {
        return;
    }

    const address = new URL(script.src).searchParams.get('endpoint');
    if (!address) {
        return;
    }
    const endpoint = new URL(address, window.location.href);
    if (endpoint.origin !== window.location.origin) {
        return;
    }

    const tool = {
        name: 'codecart_search_public_catalog',
        description: 'Find publicly listed products in this store and return their names and product page links.',
        inputSchema: {
            type: 'object',
            properties: {
                query: {
                    type: 'string',
                    minLength: 2,
                    maxLength: 64,
                    description: 'A product name or model.'
                }
            },
            required: ['query'],
            additionalProperties: false
        },
        annotations: { readOnlyHint: true, untrustedContentHint: true },
        async execute(input, { signal } = {}) {
            const query = input && typeof input.query === 'string' ? input.query.trim().replace(/\s+/g, ' ') : '';
            const length = Array.from(query).length;
            if (length < 2 || length > 64 || !/[\p{L}\p{N}]/u.test(query)) {
                return { error: 'invalid_query' };
            }

            const url = new URL(endpoint);
            url.searchParams.set('q', query);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                mode: 'same-origin',
                redirect: 'error',
                signal
            });

            if (response.status === 429) {
                return { error: 'rate_limited' };
            }
            if (!response.ok) {
                return { error: 'unavailable' };
            }

            return response.json();
        }
    };

    // A browser or page policy may reject optional tool registration.
    Promise.resolve().then(() => context.registerTool(tool)).catch(() => {});
})();
