(function () {
    'use strict';

    const button = document.getElementById('button-product-photo-scan');
    if (!button) {
        return;
    }

    const status = document.getElementById('product-photo-scan-progress');
    const result = document.querySelector('#stats [data-stat="small_image"]');
    const link = result.querySelector('a');
    const bar = result.querySelector('.progress');

    function format(message, values) {
        return message.replace(/%([1-3])/g, function (token, position) {
            return values[Number(position) - 1];
        });
    }

    async function request(action) {
        const controller = new AbortController();
        const timeout = setTimeout(function () { controller.abort(); }, 30000);
        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: action }),
                signal: controller.signal
            });
            const data = await response.json();
            if (!response.ok || typeof data.done !== 'boolean') {
                throw new Error(button.dataset.error);
            }
            return data;
        } finally {
            clearTimeout(timeout);
        }
    }

    button.addEventListener('click', async function () {
        button.disabled = true;
        status.textContent = format(button.dataset.progress, [0, '…']);

        try {
            let data = await request('start');
            while (!data.done) {
                status.textContent = format(button.dataset.progress, [data.checked, data.total]);
                data = await request('batch');
            }

            status.textContent = format(button.dataset.complete, [data.checked, data.matched, data.skipped]);
            const percent = data.result_total ? Math.round(100 * data.result_count / data.result_total) : 0;
            link.querySelector('.stats-count').textContent = data.result_count + '/' + data.result_total + ' (' + percent + '%)';
            bar.firstElementChild.style.width = percent + '%';
            link.hidden = false;
            bar.hidden = false;
            const placeholder = result.querySelector('span.stats-link');
            if (placeholder) {
                placeholder.hidden = true;
            }
        } catch {
            status.textContent = button.dataset.error;
        } finally {
            button.disabled = false;
        }
    });
}());
