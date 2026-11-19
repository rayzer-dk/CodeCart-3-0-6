'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname,'../..');
const read = file => fs.readFileSync(path.join(root,file),'utf8');
const views = [
 ['catalog/product_form.twig','ccp-main-ui-product-form'],
 ['catalog/product_list.twig','ccp-main-ui-product-list'],
 ['sale/order_list.twig','ccp-main-ui-order-list'],
 ['setting/setting.twig','ccp-main-ui-settings'],
 ['sale/order_info.twig','ccp-main-ui-order-info'],
 ['catalog/category_form.twig','ccp-main-ui-category-form'],
 ['customer/customer_list.twig','ccp-main-ui-customer-list']
];
const stylesheet=read('upload/admin/view/stylesheet/codecart-main-ui.css');
assert.match(stylesheet,/#content\[class\*="ccp-main-ui-"\]/);
assert.match(stylesheet,/@media \(max-width: 767px\)/);
for (const [filename, scope] of views) {
 const source=read('upload/admin/view/template/'+filename);
 assert.ok(source.includes('<div id="content" class="'+scope+'">'),'Missing scoped content wrapper for '+filename);
 assert.ok(stylesheet.includes('.'+scope),'Missing responsive CSS for '+scope);
}
const header=read('upload/admin/view/template/common/header.twig');
assert.ok(header.includes('view/stylesheet/codecart-main-ui.css?v={{ package_build }}'),'Main stylesheet not linked');
const sidebar=read('upload/admin/view/template/common/column_left.twig');
for(const id of ['collapse{{ i }}','collapse{{ i }}-{{ j }}','collapse-{{ i }}-{{ j }}-{{ k }}']){
 assert.ok(sidebar.includes('aria-controls="'+id+'" aria-expanded="false"'),'Sidebar level not labelled: '+id);
}
const overflow=read('upload/admin/view/javascript/codecart-header-overflow.js');
assert.ok(overflow.includes("first.focus({preventScroll:true})"),'Overflow keyboard focus is not handled');
console.log('PASS scoped main admin UI for '+views.length+' views and accessible three-level navigation');
