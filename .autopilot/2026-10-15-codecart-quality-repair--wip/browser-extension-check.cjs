const {chromium} = require('C:/Users/Владимир/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs = require('fs');
const path = require('path');
(async () => {
  const browser = await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
  const results = [];
  const types = ['advertise','analytics','captcha','currency','dashboard','feed','fraud','menu','module','payment','report','shipping','theme','total'];
  for (const width of [390,1280]) for (const mode of ['light','dark']) for (const type of types) {
    const page = await browser.newPage({viewport:{width,height:900}});
    const errors=[]; page.on('pageerror', e=>errors.push(e.message));
    await page.goto(`http://127.0.0.1:52604/.autopilot/2026-10-15-codecart-quality-repair--wip/evidence/browser/${type}.html`);
    if (mode==='dark') await page.evaluate(()=>document.documentElement.classList.add('ccp-admin-dark'));
    const initial = await page.locator('#extension').innerHTML();
    if (!(await page.locator('th:last-child').innerText()).trim()) throw Error('action label missing from fixture');
    const geometry = await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth,align:getComputedStyle(document.querySelector('th:last-child')).textAlign,tableLayout:getComputedStyle(document.querySelector('.ccp-extension-table')).tableLayout,overflow:getComputedStyle(document.querySelector('#extension .table-responsive')).overflowX}));
    if (geometry.scroll > width+1 || geometry.align!=='center' || geometry.tableLayout!=='fixed' || geometry.overflow!=='auto') throw Error(JSON.stringify({type,width,mode,geometry}));
    for (const key of ['name','status','installed','upload','date']) {
      const button = page.locator(`button[data-ccp-sort="${key}"]`);
      for (let n=0;n<2;n++) {
        await button.click();
        const sort = await page.evaluate(key=>{
          const th=document.querySelector(`button[data-ccp-sort="${key}"]`).closest('th');
          const rows=[...document.querySelectorAll('.ccp-extension-main')];
          return {direction:th.getAttribute('aria-sort'),values:rows.map(r=>r.getAttribute('data-sort-'+key)),groups:rows.every(r=>!r.nextElementSibling?.classList.contains('ccp-extension-child') || r.nextElementSibling.textContent.includes(r.querySelector('b')?.textContent||r.children[0].textContent.trim()))};
        },key);
        const sign=sort.direction==='ascending'?1:-1;
        for(let i=1;i<sort.values.length;i++) {
          const a=sort.values[i-1]||'', b=sort.values[i]||'';
          if ((key==='date'||key==='upload') && (!a||!b)) { if (!a&&b) throw Error('unknown date before known'); continue; }
          const order=key==='installed'?Number(a)-Number(b):a.localeCompare(b,'en',{sensitivity:'base',numeric:true});
          if (order*sign>0) throw Error('bad sort '+JSON.stringify({type,key,sort}));
        }
        if (!sort.groups) throw Error('child detached '+type);
      }
    }
    await page.locator('#extension').evaluate((element,html)=>{element.innerHTML=html;},initial);
    const name=page.locator('button[data-ccp-sort="name"]'); await name.focus(); await name.press('Enter');
    if (await name.getAttribute('aria-pressed')!=='true') throw Error('keyboard/replaced DOM failed');
    if (errors.length) throw Error(errors.join(';'));
    results.push({type,width,mode,geometry,status:'PASS'});
    if (type==='module') await page.screenshot({path:path.join(__dirname,`evidence/browser/module-${width}-${mode}.png`),fullPage:true});
    await page.close();
  }
  await browser.close();
  fs.writeFileSync(path.join(__dirname,'evidence/browser/results.json'),JSON.stringify(results,null,2));
  console.log('PASS '+results.length+' native template/CSS/JS fixture combinations; 5 keys both directions; AJAX DOM replacement; keyboard. Store/database integration NOT RUN.');
})().catch(error=>{console.error(error);process.exit(1);});
