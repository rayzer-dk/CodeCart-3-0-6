'use strict';

const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { JSDOM } = require('jsdom');
const root = path.resolve(__dirname, '..');
const jquery = fs.readFileSync(path.join(root, 'upload/catalog/view/javascript/jquery/jquery-3.7.1.min.js'), 'utf8');
const files = process.argv.slice(2);
if (!files.length) throw new Error('Pass Bootstrap JS files to verify');
let checks = 0;
function check(value, message) { assert.ok(value, message); checks++; }
const tick = window => new Promise(resolve => window.setTimeout(resolve, 5));

async function verify(file) {
  const before = checks;
  const dom = new JSDOM('<!doctype html><html><body></body></html>', { runScripts: 'outside-only', url: 'https://store.example.invalid/' });
  const window = dom.window;
  window.eval(jquery);
  window.eval(fs.readFileSync(path.resolve(file), 'utf8'));
  const $ = window.jQuery;
  $.support.transition = false;
  try {
    const $tip = $('<button title="Tip"></button>').appendTo('body').tooltip({ html: true, animation: false });
    const tooltip = $tip.data('bs.tooltip');
    const safe = '<b>Bold</b><a href="https://example.invalid/">Link</a>';
    const unsafe = '<img src="x" onerror="void(0)"><script type="text/plain">marker</script><a href="javascript:void(0)">Link</a>';
    check(tooltip.sanitizeHtml(safe).includes('<b>Bold</b>'), 'Safe tooltip HTML retained');
    const clean = tooltip.sanitizeHtml(unsafe);
    check(!/onerror|<script|javascript:/i.test(clean), 'Tooltip removes active attributes, scripts and unsafe URLs');
    const parser = window.DOMParser;
    const implementation = window.document.implementation;
    let fakeCalls = 0;
    Object.defineProperty(window.document, 'implementation', { configurable: true, value: { createHTMLDocument() { fakeCalls++; throw new Error('Clobbered implementation invoked'); } } });
    check(!/onerror|<script|javascript:/i.test(tooltip.sanitizeHtml(unsafe)), 'DOMParser survives a clobbered implementation');
    check(fakeCalls === 0, 'Clobbered implementation never called');
    window.DOMParser = undefined;
    check(tooltip.sanitizeHtml(unsafe) === '', 'No parser and clobbered implementation fail closed');
    Object.defineProperty(window.document, 'implementation', { configurable: true, value: implementation });
    check(tooltip.sanitizeHtml(safe).includes('<b>Bold</b>'), 'Native DOMImplementation fallback retains safe markup');
    check(!/onerror|<script|javascript:/i.test(tooltip.sanitizeHtml(unsafe)), 'Native fallback sanitizes unsafe markup');
    Object.defineProperty(window.document, 'implementation', { configurable: true, value: null });
    check(tooltip.sanitizeHtml(unsafe) === '', 'No DOM parser fails closed');
    window.DOMParser = parser;
    Object.defineProperty(window.document, 'implementation', { configurable: true, value: implementation });
    const $popover = $('<button></button>').appendTo('body').popover({ html: true, title: safe, content: safe + unsafe, animation: false });
    const popover = $popover.data('bs.popover');
    popover.setContent();
    check(popover.tip().find('.popover-title b').text() === 'Bold' && popover.tip().find('.popover-content b').text() === 'Bold', 'Popover title and body retain safe HTML');
    check(!/onerror|<script|javascript:/i.test(popover.tip().html()), 'Popover title and body sanitize unsafe markup');
    const $button = $('<button data-loading-text="&lt;img src=x onerror=void(0)&gt;"><i class="fa fa-save"></i> Save</button>').appendTo('body');
    const original = $button.html();
    $button.button('loading');
    check(!$button.prop('disabled'), 'Button disable remains asynchronous');
    await tick(window);
    check($button.find('img').length === 0 && $button.text() === '<img src=x onerror=void(0)>', 'Button data-loading-text treated as text');
    check($button.prop('disabled'), 'Loading disables the button');
    $button.button('reset'); await tick(window);
    check($button.html() === original && !$button.prop('disabled'), 'Reset restores original icon markup and enables button');
    const $reset = $('<button data-reset-text="&lt;b&gt;Reset&lt;/b&gt;">Original</button>').appendTo('body');
    $reset.button('loading'); await tick(window); $reset.button('reset'); await tick(window);
    check($reset.find('b').length === 0 && $reset.text() === '<b>Reset</b>', 'Explicit data-reset-text treated as text');
    const $trusted = $('<button>Original</button>').appendTo('body').button({ loadingText: '<i class="fa fa-spinner"></i> Working', resetText: '<b>Ready</b>' });
    $trusted.button('loading'); await tick(window);
    check($trusted.find('i.fa-spinner').length === 1, 'Trusted JavaScript loading option retains HTML contract');
    $trusted.button('reset'); await tick(window);
    check($trusted.find('b').text() === 'Ready', 'Trusted JavaScript reset option retains HTML contract');
    const $input = $('<input type="submit" value="Save" data-loading-text="&lt;b&gt;Wait&lt;/b&gt;">').appendTo('body');
    $input.button('loading'); await tick(window);
    check($input.val() === '<b>Wait</b>' && $input.prop('disabled'), 'Input loading uses value and disables');
    $input.button('reset'); await tick(window);
    check($input.val() === 'Save' && !$input.prop('disabled'), 'Input reset restores value');
    const $toggle = $('<button aria-pressed="false">Toggle</button>').appendTo('body');
    $toggle.button('toggle'); check($toggle.hasClass('active') && $toggle.attr('aria-pressed') === 'true', 'Button toggle API preserved');
    $.support.transition = false;
    const $collapse = $('<div class="collapse">Panel</div>').appendTo('body').collapse({ toggle: false });
    $collapse.collapse('show'); check($collapse.hasClass('in'), 'Collapse show API preserved');
    $collapse.collapse('hide'); check(!$collapse.hasClass('in'), 'Collapse hide API preserved');
    const $modal = $('<div class="modal"><div class="modal-dialog"><div class="modal-content">Dialog</div></div></div>').appendTo('body').modal({ show: false });
    $modal.modal('show'); check($modal.hasClass('in') && $('body').hasClass('modal-open'), 'Modal show API preserved');
    $modal.modal('hide'); check(!$modal.hasClass('in') && !$('body').hasClass('modal-open'), 'Modal hide API preserved');
    console.log('PASS ' + file + ' (' + (checks - before) + ' checks)');
  } finally { window.close(); }
}
(async () => {
  for (const file of files) await verify(file);
  console.log('RESULT ' + checks + ' checks, 0 failures');
})().catch(error => { console.error(error); process.exitCode = 1; });
