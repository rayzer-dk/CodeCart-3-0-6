'use strict';
/* Lightweight DOM contract tests for the storefront feedback layer; no browser deps. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const file = name => fs.readFileSync(path.join(root, name), 'utf8');
const script = file('upload/catalog/view/javascript/codecart/feedback/feedback.js');
const style = file('upload/catalog/view/javascript/codecart/feedback/feedback.css');
const adminStyle = file('upload/admin/view/stylesheet/codecart-feedback.css');
const themePaths = ['default', 'codecart'];
for (const theme of themePaths) {
    const twig = file('upload/catalog/view/theme/' + theme + '/template/common/header.twig');
    const js = twig.indexOf('codecart/feedback/feedback.js');
    const core = twig.indexOf('{{ core_script|');
    assert.ok(core >= 0 && js > core, theme + ' feedback must load after common.js');
    assert.ok(twig.includes('codecart/feedback/feedback.css'), theme + ' feedback CSS must load');
}
assert.match(style, /#ccp-toast-host\s*\{[^}]*top:\s*50%/);
assert.match(style, /#ccp-toast-host\s*\{[^}]*left:\s*50%/);
assert.match(style, /prefers-reduced-motion:\s*reduce/);
assert.match(style, /html\.ccp-theme-dark/);
assert.ok(file('upload/admin/view/template/common/header.twig').includes('codecart-feedback.css'));
assert.match(adminStyle, /\.ccp-toast-stack/);

class Element {
    constructor(tag) {
        this.tagName = tag;
        this.children = [];
        this.attributes = {};
        this.handlers = {};
        this.parentNode = null;
        this.textContent = '';
        this.classes = new Set();
        this.classList = {
            add: name => this.classes.add(name),
            remove: name => this.classes.delete(name),
            contains: name => this.classes.has(name)
        };
    }
    appendChild(child) { child.parentNode = this; this.children.push(child); return child; }
    removeChild(child) { this.children.splice(this.children.indexOf(child), 1); child.parentNode = null; }
    setAttribute(key, value) { this.attributes[key] = value; }
    addEventListener(name, callback) { this.handlers[name] = callback; }
}
const document = {
    body: new Element('body'),
    documentElement: {lang: 'uk-ua'},
    handlers: {},
    createElement: tag => new Element(tag),
    getElementById: id => document.body.children.find(x => x.id === id) || null,
    addEventListener(name, callback) { this.handlers[name] = callback; }
};
const pending = new Map();
let nextTimer = 0;
let lastDuration = 0;
const window = {
    CodeCartUI: {},
    CodeCartI18n: {ui_close: 'Закрити'},
    matchMedia: () => ({matches:false}),
    requestAnimationFrame: callback => callback(),
    setTimeout: (callback, duration) => {
        const id = ++nextTimer;
        pending.set(id, {callback, duration});
        lastDuration = duration;
        return id;
    },
    clearTimeout: id => pending.delete(id),
    DOMParser: class {
        parseFromString(html) {
            return {body: {textContent: html.replace(/<[^>]*>/g, '')}};
        }
    }
};
function $(arg) {
    if (typeof arg === 'function') { arg(); return; }
    return {
        find: () => $(null), is: () => false, add() {return this;},
        each: () => {}, closest: () => $(null)
    };
}
window.jQuery = $;
vm.runInNewContext(script, {window, document, Date, isFinite, Number, String, Object}, {filename:'feedback.js'});
const ui = window.CodeCartUI;
assert.equal(typeof ui.toast, 'function');
const toast = ui.toast('success', '<strong>Додано до кошика</strong>');
assert.ok(toast, 'success notification created');
assert.equal(toast.attributes.role, 'status');
assert.equal(toast.children[1].textContent, 'Додано до кошика');
assert.ok(toast.classes.has('is-visible'));
assert.equal(lastDuration, 1700);
const host = document.getElementById('ccp-toast-host');
assert.equal(host.children.length, 1);
ui.toast('success', '<strong>Додано до кошика</strong>');
assert.equal(host.children.length, 1, 'duplicate success is suppressed');
const error = ui.toast('danger', 'Помилка');
assert.equal(host.children.length, 1, 'new feedback replaces previous');
assert.equal(error.attributes.role, 'alert');
assert.ok(lastDuration >= 6000, 'errors stay visible for reading');
document.handlers.keydown({key:'Escape'});
assert.equal(host.children.length, 1, 'dismiss uses exit animation');
assert.equal(error.classes.has('is-visible'), false);
ui.toast('warning', 'Увага');
assert.equal(host.children.length, 1, 'previous dismissed notification is replaced');
assert.ok(lastDuration >= 6000, 'warnings stay visible for reading');
window.CodeCartI18n.ui_continue_shopping = 'Continue shopping';
window.CodeCartI18n.text_checkout = 'Checkout';
window.CodeCartRoutes = {checkout:'https://example.test/index.php?route=checkout/checkout'};
const cartNotice = ui.toast('success', 'Added to cart', {cart:true, duration:2000});
assert.ok(cartNotice.classes.has('ccp-toast-cart'), 'Cart confirmation has contextual actions');
assert.equal(cartNotice.children.length, 4, 'Cart confirmation has button row');
assert.equal(cartNotice.children[3].children[0].tagName, 'button');
assert.equal(cartNotice.children[3].children[0].textContent, 'Continue shopping');
assert.equal(cartNotice.children[3].children[1].textContent, 'Checkout');
assert.equal(cartNotice.children[3].children[1].href, window.CodeCartRoutes.checkout);
assert.equal(lastDuration, 2000, 'Cart success message stays for two seconds');
assert.match(file('upload/catalog/view/javascript/common.js'), /cart:true, duration:2000/);
for (const theme of themePaths) {
    const product = file('upload/catalog/view/theme/' + theme + '/template/product/product.twig');
    assert.match(product, /CodeCartUI.toast\('success', json\['success'\], \{cart:true, duration:2000\}\)/);
}
console.log('Feedback UI contracts: PASS (both themes, cart actions, 2s duration, generic notifications, Escape, admin styling)');
