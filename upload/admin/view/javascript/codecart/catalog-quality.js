(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var tables = document.querySelectorAll('.cc-quality-table[data-quality-url]');
    if (!tables.length || !window.fetch) { return; }

    var enabled = true;
    try { enabled = window.localStorage.getItem('ccp-quality-indicators') !== '0'; } catch (ignore) {}

    function setEnabled(value) {
      enabled = value;
      try { window.localStorage.setItem('ccp-quality-indicators', enabled ? '1' : '0'); } catch (ignore) {}
      Array.prototype.forEach.call(tables, function (table) {
        table.classList.toggle('cc-quality-off', !enabled);
        var button = table.closest('.panel').querySelector('.cc-quality-toggle');
        if (button) {
          button.classList.toggle('active', enabled);
          button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        }
      });
      if (enabled) {
        Array.prototype.forEach.call(tables, function (table) {
          if (table.ccQualityQueueVisible) { table.ccQualityQueueVisible(); }
        });
      }
    }

    Array.prototype.forEach.call(tables, function (table) {
      var url = table.getAttribute('data-quality-url');
      var countLabel = table.getAttribute('data-quality-count') || '';
      var chips = table.querySelectorAll('[data-quality-id]');
      var pending = [];
      var busy = false;
      var scheduled = null;
      var observer = null;

      function add(chip) {
        if (!enabled || chip.dataset.qualityLoaded === '1' || (chip.dataset.qualityQueued === '1' || chip.dataset.qualityQueued === '2')) { return; }
        if (!/^[1-9][0-9]*$/.test(chip.getAttribute('data-quality-id'))) { return; }
        chip.dataset.qualityQueued = '1';
        pending.push(chip);
        if (!scheduled) {
          scheduled = window.setTimeout(next, 60);
        }
      }

      function next() {
        scheduled = null;
        if (!enabled || busy || !pending.length) { return; }
        var batch = pending.splice(0, 40);
        batch.forEach(function (chip) { chip.dataset.qualityQueued = '2'; });
        var ids = batch.map(function (chip) { return chip.getAttribute('data-quality-id'); });
        busy = true;
        var join = url.indexOf('?') === -1 ? '?' : '&';
        fetch(url + join + 'ids=' + encodeURIComponent(ids.join(',')), {
          method: 'GET',
          credentials: 'same-origin',
          cache: 'no-store',
          headers: {'Accept': 'application/json'}
        }).then(function (response) {
          if (!response.ok) { throw new Error('quality unavailable'); }
          return response.json();
        }).then(function (body) {
          batch.forEach(function (chip) {
            var info = body.items && body.items[chip.getAttribute('data-quality-id')];
            if (!info || typeof info.percent !== 'number') {
              chip.dataset.qualityLoaded = '1';
              return;
            }
            var percentage = Math.max(0, Math.min(100, Math.round(info.percent)));
            var badge = chip.querySelector('.cc-quality-score');
            badge.textContent = percentage + '%';
            badge.className = 'cc-quality-score ' + (percentage >= 100 ? 'is-complete' : percentage >= 70 ? 'is-partial' : 'is-low');
            var missing = Array.isArray(info.missing) ? info.missing : [];
            var message = percentage + '% (' + info.complete + '/' + info.total + ')';
            if (missing.length) { message += '\n' + missing.join('\n'); }
            chip.title = message;
            chip.setAttribute('aria-label', message.replace(/\n/g, '; '));
            if (typeof info.count === 'number') {
              var counter = chip.querySelector('.cc-quality-count');
              if (counter) {
                counter.textContent = info.count.toLocaleString();
                counter.title = countLabel;
                counter.hidden = false;
              }
            }
            chip.dataset.qualityLoaded = '1';
            if (observer) { observer.unobserve(chip); }
          });
        }).catch(function () {
          batch.forEach(function (chip) {
            chip.dataset.qualityLoaded = '1';
            chip.title = '—';
            var badge = chip.querySelector('.cc-quality-score');
            if (badge) { badge.textContent = '—'; }
          });
        }).then(function () {
          busy = false;
          if (pending.length && enabled) { scheduled = window.setTimeout(next, 80); }
        });
      }

      function queueVisible() {
        Array.prototype.forEach.call(chips, function (chip) {
          if (chip.dataset.qualityLoaded === '1') { return; }
          var rect = chip.getBoundingClientRect();
          if (rect.top < window.innerHeight + 200 && rect.bottom > -200) {
            add(chip);
          }
        });
      }
      table.ccQualityQueueVisible = queueVisible;

      if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) { add(entry.target); }
          });
        }, {rootMargin: '150px 0px'});
        Array.prototype.forEach.call(chips, function (chip) { observer.observe(chip); });
      } else {
        // On older browsers only the rows visible in the viewport are fetched.
        window.addEventListener('scroll', queueVisible, {passive: true});
        window.addEventListener('resize', queueVisible);
      }
      var toggle = table.closest('.panel').querySelector('.cc-quality-toggle');
      if (toggle) {
        toggle.addEventListener('click', function () { setEnabled(!enabled); });
      }
    });

    setEnabled(enabled);
  });
}());
