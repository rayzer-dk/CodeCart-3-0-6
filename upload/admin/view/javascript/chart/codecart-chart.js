/*
 * CodeCart PRO Dashboard Chart v1.2.0
 * Dependency-free SVG renderer for the Core dashboard.
 * SVG stays crisp on HiDPI/Windows scaling and does not require Flot, jQuery,
 * Moment.js or a remote CDN. Legacy Flot files remain available for extensions
 * that explicitly depend on $.plot().
 */
(function (window, document) {
    'use strict';

    function number(value) {
        value = Number(value);
        return isFinite(value) ? value : 0;
    }

    function escapeText(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function cssVar(name, fallback) {
        var value = '';
        try { value = getComputedStyle(document.documentElement).getPropertyValue(name).trim(); } catch (e) {}
        return value || fallback;
    }

    function rgba(hex, alpha) {
        var match = /^#([0-9a-f]{6})$/i.exec(hex || '');
        if (!match) { return 'rgba(11,111,211,' + alpha + ')'; }
        var n = parseInt(match[1], 16);
        return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + alpha + ')';
    }

    function dataMap(series) {
        var map = {};
        var rows = series && Array.isArray(series.data) ? series.data : [];
        for (var i = 0; i < rows.length; i++) {
            if (Array.isArray(rows[i]) && rows[i].length > 1) {
                map[String(rows[i][0])] = number(rows[i][1]);
            }
        }
        return map;
    }

    function niceMax(value) {
        value = Math.max(1, number(value));
        var power = Math.pow(10, Math.floor(Math.log(value) / Math.LN10));
        var scaled = value / power;
        var nice = scaled <= 1 ? 1 : (scaled <= 2 ? 2 : (scaled <= 5 ? 5 : 10));
        return nice * power;
    }

    function render(element, json, seriesOptions) {
        if (!element || !json || !Array.isArray(json.xaxis)) { return; }

        var width = Math.max(520, Math.round(element.clientWidth || 900));
        var height = Math.max(220, Math.round(element.clientHeight || 260));
        var margin = {top: 16, right: 18, bottom: 48, left: 48};
        var plotW = Math.max(100, width - margin.left - margin.right);
        var plotH = Math.max(80, height - margin.top - margin.bottom);
        var axes = json.xaxis;
        var options = Array.isArray(seriesOptions) && seriesOptions.length ? seriesOptions : [{key:'order'}, {key:'customer'}];
        var maps = [];
        var max = 0;
        var i, j;

        for (i = 0; i < options.length; i++) {
            maps[i] = dataMap(json[options[i].key]);
            for (j = 0; j < axes.length; j++) {
                max = Math.max(max, number(maps[i][String(axes[j][0])]));
            }
        }
        max = niceMax(max);

        var accent = cssVar('--ccp-admin-accent', '#0b6fd3');
        var secondary = '#6aa9e9';
        var colors = [accent, secondary];
        var fills = [rgba(accent, .78), rgba(secondary, .72)];
        var grid = '#e7ecf2';
        var text = '#667085';
        var html = '<svg xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Sales analytics" width="100%" height="100%" viewBox="0 0 ' + width + ' ' + height + '">';
        html += '<rect x="0" y="0" width="' + width + '" height="' + height + '" fill="transparent"/>';

        var ticks = 5;
        for (i = 0; i <= ticks; i++) {
            var y = margin.top + plotH - (plotH * i / ticks);
            var value = max * i / ticks;
            html += '<line x1="' + margin.left + '" y1="' + y.toFixed(2) + '" x2="' + (margin.left + plotW) + '" y2="' + y.toFixed(2) + '" stroke="' + grid + '" stroke-width="1" shape-rendering="crispEdges"/>';
            html += '<text x="' + (margin.left - 8) + '" y="' + (y + 4).toFixed(2) + '" text-anchor="end" font-size="11" font-family="system-ui,-apple-system,Segoe UI,Arial,sans-serif" fill="' + text + '">' + escapeText(Math.round(value * 100) / 100) + '</text>';
        }

        var count = Math.max(1, axes.length);
        var groupW = plotW / count;
        var barGap = Math.max(1, Math.min(3, groupW * .08));
        var usable = Math.max(2, groupW * .72);
        var barW = Math.max(1, (usable - barGap * Math.max(0, options.length - 1)) / Math.max(1, options.length));
        var labelEvery = Math.max(1, Math.ceil(count / Math.max(8, Math.floor(plotW / 42))));

        for (j = 0; j < axes.length; j++) {
            var key = String(axes[j][0]);
            var center = margin.left + groupW * j + groupW / 2;
            var start = center - usable / 2;
            for (i = 0; i < options.length; i++) {
                var v = number(maps[i][key]);
                var h = max > 0 ? plotH * v / max : 0;
                var x = start + i * (barW + barGap);
                var by = margin.top + plotH - h;
                html += '<rect x="' + x.toFixed(2) + '" y="' + by.toFixed(2) + '" width="' + barW.toFixed(2) + '" height="' + Math.max(0,h).toFixed(2) + '" rx="1.5" fill="' + fills[i % fills.length] + '"><title>' + escapeText((json[options[i].key] && json[options[i].key].label ? json[options[i].key].label : options[i].key) + ': ' + v) + '</title></rect>';
            }
            if (j % labelEvery === 0 || j === axes.length - 1) {
                html += '<text x="' + center.toFixed(2) + '" y="' + (margin.top + plotH + 19) + '" text-anchor="middle" font-size="10.5" font-family="system-ui,-apple-system,Segoe UI,Arial,sans-serif" fill="' + text + '">' + escapeText(axes[j][1]) + '</text>';
            }
        }

        var legendX = margin.left;
        var legendY = height - 9;
        for (i = 0; i < options.length; i++) {
            var label = json[options[i].key] && json[options[i].key].label ? json[options[i].key].label : options[i].key;
            html += '<rect x="' + legendX + '" y="' + (legendY - 9) + '" width="12" height="8" rx="1" fill="' + fills[i % fills.length] + '"/>';
            html += '<text x="' + (legendX + 17) + '" y="' + legendY + '" font-size="11" font-family="system-ui,-apple-system,Segoe UI,Arial,sans-serif" fill="' + text + '">' + escapeText(label) + '</text>';
            legendX += Math.max(90, 28 + String(label).length * 7);
        }

        html += '</svg>';
        element.innerHTML = html;
    }

    window.CodeCartChart = {version: '1.2.0', render: render};
})(window, document);
