'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('@playwright/test');
const root = path.resolve(__dirname, '../..');
const asset = name => path.join(root, 'upload/admin/view/javascript', name);
async function run() {
 const executable=process.env.CCP_CHROME_PATH||
  ['/usr/bin/google-chrome','/usr/bin/google-chrome-stable','/usr/bin/chromium','/usr/bin/chromium-browser'].find(fs.existsSync);
 const browser=await chromium.launch({headless:true,args:['--no-sandbox'],...(executable?{executablePath:executable}:{})});
 try{
  for(const width of [375,768,1280]){
   const page=await browser.newPage({viewport:{width,height:840},reducedMotion:'reduce'});
   const errors=[];page.on('pageerror',e=>errors.push(String(e)));
   await page.setContent('<!doctype html><html><body>'+
     '<nav id="column-left"><ul id="menu"><li><a href="#collapse0" class="parent collapsed" data-toggle="collapse" aria-controls="collapse0" aria-expanded="false">Catalog</a>'+
     '<ul id="collapse0" class="collapse"><li><a href="#collapse0-0" class="parent collapsed" data-toggle="collapse" aria-controls="collapse0-0" aria-expanded="false">Subgroup</a>'+
     '<ul id="collapse0-0" class="collapse"><li><a href="#collapse-0-0-0" class="parent collapsed" data-toggle="collapse" aria-controls="collapse-0-0-0" aria-expanded="false">Products</a>'+
     '<ul id="collapse-0-0-0" class="collapse"><li><a href="#item">Edit</a></li></ul></li></ul></li></ul></li></ul></nav>'+
     '</body></html>');
   await page.addStyleTag({path:path.join(root,'upload/admin/view/stylesheet/bootstrap.css')});
   await page.addScriptTag({path:asset('jquery/jquery-3.7.1.min.js')});
   await page.addScriptTag({path:asset('bootstrap/js/bootstrap.min.js')});
   for(const id of ['collapse0','collapse0-0','collapse-0-0-0']){
    const link=page.locator('[aria-controls="'+id+'"]');
    await link.click();
    await page.waitForFunction(id=>document.getElementById(id).classList.contains('in'),id);
    assert.equal(await link.getAttribute('aria-expanded'),'true',id+' expanded');
   }
   await page.locator('[aria-controls="collapse-0-0-0"]').click();
   await page.waitForFunction(()=>!document.getElementById('collapse-0-0-0').classList.contains('in'));
   assert.equal(await page.locator('[aria-controls="collapse-0-0-0"]').getAttribute('aria-expanded'),'false');
   assert.deepEqual(errors,[],errors.join('; '));
   await page.close();
   console.log('PASS main Bootstrap 3 nested sidebar '+width+'px');
  }
  const page=await browser.newPage({viewport:{width:375,height:800}});
  await page.setContent('<!doctype html><html><head><style>'+
   '#header>.container-fluid{display:flex;width:100%;padding:0}'+
   '#header .brand{flex:0 0 80px}'+
   '#header .navbar-right{display:flex;flex-wrap:nowrap;list-style:none;min-width:0;padding:0;margin:0}'+
   '#header .navbar-right>li{flex:0 0 52px;width:52px}'+
   '#header .ccp-header-overflow-panel[hidden],#header .ccp-header-more[hidden]{display:none}'+
   '</style></head><body><header id="header"><div class="container-fluid">'+
   '<div class="brand">CodeCart</div><ul class="navbar-right">'+
   Array.from({length:12},(_,i)=>'<li class="extension-action"><a href="#action'+i+'" title="Action '+i+'">Action</a></li>').join('')+
   '<li class="ccp-header-more" hidden><button type="button" id="ccp-header-more-toggle" aria-expanded="false">More</button></li></ul>'+
   '<div id="ccp-header-overflow-panel" class="ccp-header-overflow-panel" hidden><ul class="ccp-header-overflow-items"></ul></div>'+
   '</div></header></body></html>');
  await page.addScriptTag({path:asset('codecart-header-overflow.js')});
  await page.waitForFunction(()=>document.querySelectorAll('.ccp-header-overflow-items>li').length>0);
  await page.locator('#ccp-header-more-toggle').click();
  const focused=await page.evaluate(()=>!!document.activeElement.closest('.ccp-header-overflow-items'));
  assert.equal(focused,true,'Overflow must focus the first accessible action');
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('#ccp-header-overflow-panel').getAttribute('hidden'),'');
  await page.setViewportSize({width:1280,height:800});
  await page.waitForFunction(()=>document.querySelectorAll('.navbar-right>.extension-action').length===12);
  assert.equal(await page.locator('.ccp-header-overflow-items>.extension-action').count(),0);
  await page.close();
  console.log('PASS main mobile header overflow focus and resize');
 } finally { await browser.close(); }
}
run().catch(e=>{console.error(e);process.exitCode=1;});
