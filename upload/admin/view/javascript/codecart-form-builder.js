(function($){'use strict';
function safe(v){try{var x=JSON.parse(v||'[]');return Array.isArray(x)?x:[];}catch(e){return[];}}
function el(tag,cls){var n=document.createElement(tag);if(cls)n.className=cls;return n;}
function valInput(label,value,type){var g=el('div','form-group ccp-form-field-control'),l=el('label','control-label'),i=el(type==='textarea'?'textarea':'input','form-control');l.textContent=label;if(type!=='textarea')i.type=type||'text';i.value=value||'';g.appendChild(l);g.appendChild(i);return {wrap:g,input:i};}
function presetData(key,lang){
lang=String(lang||'en').toLowerCase();var uk=lang.indexOf('uk')===0||lang==='ua';
var source=uk?(window.CodeCartFormBuilderUkPresets||{}):{"callback":{"title":"Request a callback","description":"Leave your contact details and we will contact you.","submit":"Request callback","success":"Thank you. We received your callback request.","fields":[{"type":"text","key":"name","label":"Name","placeholder":"Your name","required":1},{"type":"tel","key":"phone","label":"Phone","placeholder":"+…","required":1},{"type":"text","key":"time","label":"Preferred callback time","placeholder":"For example, after 15:00","required":0},{"type":"textarea","key":"message","label":"Comment","placeholder":"What should we clarify?","required":0}],"kind":"request"},"question":{"title":"Ask a question","description":"Send your question and we will reply using the contact details provided.","submit":"Send question","success":"Thank you. Your question has been sent.","fields":[{"type":"text","key":"name","label":"Name","placeholder":"Your name","required":1},{"type":"email","key":"email","label":"Email","placeholder":"you@example.com","required":0},{"type":"tel","key":"phone","label":"Phone","placeholder":"+…","required":0},{"type":"textarea","key":"message","label":"Question","placeholder":"Describe what you would like to know","required":1}],"kind":"request"},"price":{"title":"Check price and availability","description":"Leave your details to confirm current price, stock or delivery time.","submit":"Send request","success":"Thank you. We received your price and availability request.","fields":[{"type":"text","key":"name","label":"Name","placeholder":"Your name","required":1},{"type":"tel","key":"phone","label":"Phone","placeholder":"+…","required":0},{"type":"email","key":"email","label":"Email","placeholder":"you@example.com","required":0},{"type":"textarea","key":"message","label":"Comment","placeholder":"Quantity or other requirements","required":0}],"kind":"request"},"selection":{"title":"Help choose a product","description":"Describe the task, budget and key requirements and we will suggest suitable options.","submit":"Get recommendations","success":"Thank you. We received your product selection request.","fields":[{"type":"text","key":"name","label":"Name","placeholder":"Your name","required":1},{"type":"tel","key":"phone","label":"Phone","placeholder":"+…","required":0},{"type":"email","key":"email","label":"Email","placeholder":"you@example.com","required":0},{"type":"text","key":"budget","label":"Budget","placeholder":"Approximate budget","required":0},{"type":"textarea","key":"message","label":"What do you need?","placeholder":"Use case, specifications and requirements","required":1}],"kind":"request"},"part":{"title":"Find a spare part","description":"Enter the machine model and any known part details.","submit":"Find spare part","success":"Thank you. We received your spare part request.","fields":[{"type":"text","key":"name","label":"Name","placeholder":"Your name","required":1},{"type":"tel","key":"phone","label":"Phone","placeholder":"+…","required":0},{"type":"email","key":"email","label":"Email","placeholder":"you@example.com","required":0},{"type":"text","key":"model","label":"Machine model","placeholder":"Brand and model","required":1},{"type":"text","key":"part_number","label":"Part number / SKU","placeholder":"If known","required":0},{"type":"textarea","key":"message","label":"Comment","placeholder":"Description, dimensions or other details","required":0}],"kind":"request"},"restock":{"title":"Back-in-stock notification","description":"Leave your details and we will notify you when the product is available.","submit":"Notify me","success":"Done. We received your back-in-stock request.","fields":[{"type":"text","key":"name","label":"Name","placeholder":"Your name","required":0},{"type":"email","key":"email","label":"Email","placeholder":"you@example.com","required":1},{"type":"tel","key":"phone","label":"Phone","placeholder":"+…","required":0}],"kind":"request"},"quote":{"title":"Request a quotation","description":"For wholesale or business purchases, provide your contact details and required quantity.","submit":"Request quote","success":"Thank you. We received your quotation request.","fields":[{"type":"text","key":"company","label":"Company","placeholder":"Company name","required":0},{"type":"text","key":"name","label":"Contact person","placeholder":"Name and surname","required":1},{"type":"email","key":"email","label":"Email","placeholder":"you@example.com","required":1},{"type":"tel","key":"phone","label":"Phone","placeholder":"+…","required":0},{"type":"text","key":"quantity","label":"Quantity","placeholder":"Required quantity","required":0},{"type":"textarea","key":"message","label":"Requirements","placeholder":"Configuration, timing, delivery and other terms","required":0}],"kind":"request"},"size":{"kind":"request","title":"Help choose a size","description":"If the size chart is not enough, send your measurements and we will help choose the right size.","submit":"Choose my size","success":"Thank you. We received your size selection request.","fields":[{"type":"text","key":"name","label":"Name","placeholder":"Your name","required":1},{"type":"tel","key":"phone","label":"Phone","placeholder":"+…","required":0},{"type":"email","key":"email","label":"Email","placeholder":"you@example.com","required":0},{"type":"text","key":"height","label":"Height","placeholder":"cm","required":0},{"type":"text","key":"weight","label":"Weight","placeholder":"kg","required":0},{"type":"text","key":"measurements","label":"Measurements","placeholder":"For example: chest / waist / hips","required":0},{"type":"textarea","key":"message","label":"Comment","placeholder":"Product model or other preferences","required":0}]},"delivery_info":{"kind":"info","title":"Delivery terms","description":"<p><strong>Delivery terms</strong></p><ul><li>List available delivery methods and regions.</li><li>Add dispatch and estimated transit times.</li><li>Describe cost, free-delivery thresholds and restrictions.</li></ul><p>Replace this template with your current store terms.</p>","submit":"","success":"","fields":[]},"instruction_info":{"kind":"info","title":"How to use","description":"<p><strong>Before use</strong> check the contents and follow the manufacturer requirements.</p><ol><li>Prepare the product.</li><li>Configure it according to the manufacturer instructions.</li><li>Clean and store it safely after use.</li></ol><p>Edit the steps for the specific product.</p>","submit":"","success":"","fields":[]},"rules_info":{"kind":"info","title":"Important terms and notes","description":"<p><strong>Please note:</strong></p><ul><li>Specifications and package contents may be updated by the manufacturer.</li><li>Check compatibility with your model or use case before ordering.</li><li>Add warranty, service or other important rules here.</li></ul>","submit":"","success":"","fields":[]},"shoes_eu":{"kind":"info","title":"EU shoe size chart","description":"<div class=\"table-responsive\"><table class=\"table table-bordered table-striped\"><thead><tr><th>EU</th><th>Foot length, cm</th></tr></thead><tbody><tr><td>35</td><td>22.5</td></tr><tr><td>36</td><td>23.0</td></tr><tr><td>37</td><td>23.5</td></tr><tr><td>38</td><td>24.0</td></tr><tr><td>39</td><td>25.0</td></tr><tr><td>40</td><td>25.5</td></tr><tr><td>41</td><td>26.0</td></tr><tr><td>42</td><td>27.0</td></tr><tr><td>43</td><td>27.5</td></tr><tr><td>44</td><td>28.0</td></tr><tr><td>45</td><td>29.0</td></tr><tr><td>46</td><td>29.5</td></tr></tbody></table></div><p><small>Values are approximate. Verify against the specific manufacturer size chart before publishing.</small></p>","submit":"","success":"","fields":[]},"clothing_women_eu":{"kind":"info","title":"EU women size chart","description":"<div class=\"table-responsive\"><table class=\"table table-bordered table-striped\"><thead><tr><th>EU</th><th>Chest, cm</th><th>Waist, cm</th><th>Hips, cm</th></tr></thead><tbody><tr><td>34</td><td>80</td><td>62</td><td>86</td></tr><tr><td>36</td><td>84</td><td>66</td><td>90</td></tr><tr><td>38</td><td>88</td><td>70</td><td>94</td></tr><tr><td>40</td><td>92</td><td>74</td><td>98</td></tr><tr><td>42</td><td>96</td><td>78</td><td>102</td></tr><tr><td>44</td><td>100</td><td>82</td><td>106</td></tr><tr><td>46</td><td>104</td><td>86</td><td>110</td></tr></tbody></table></div><p><small>Approximate EU chart. Brand sizing varies; verify with the manufacturer.</small></p>","submit":"","success":"","fields":[]},"clothing_men_eu":{"kind":"info","title":"EU men size chart","description":"<div class=\"table-responsive\"><table class=\"table table-bordered table-striped\"><thead><tr><th>EU</th><th>Chest, cm</th><th>Waist, cm</th><th>Hips, cm</th></tr></thead><tbody><tr><td>44</td><td>88</td><td>76</td><td>92</td></tr><tr><td>46</td><td>92</td><td>80</td><td>96</td></tr><tr><td>48</td><td>96</td><td>84</td><td>100</td></tr><tr><td>50</td><td>100</td><td>88</td><td>104</td></tr><tr><td>52</td><td>104</td><td>92</td><td>108</td></tr><tr><td>54</td><td>108</td><td>96</td><td>112</td></tr><tr><td>56</td><td>112</td><td>100</td><td>116</td></tr></tbody></table></div><p><small>Approximate EU chart. Brand sizing varies; verify with the manufacturer.</small></p>","submit":"","success":"","fields":[]}};
var p=source[key]||source.question||null;if(!p)return null;p=JSON.parse(JSON.stringify(p));p.kind=p.kind||'request';p.fields=(p.fields||[]).map(function(f){return Array.isArray(f)?{type:f[0],key:f[1],label:f[2],placeholder:f[3],required:f[4],options:f[0]==='select'?[]:undefined}:f;});return p;}
function init(root){var $root=$(root),labels=$root.data(),paneStates=[],$kind=$root.find('#input-kind');function updateKind(){var info=$kind.val()==='info';$root.toggleClass('is-info-kind',info);$root.find('.ccp-request-only').toggle(!info);}$kind.on('change',updateKind);updateKind();$root.find('.ccp-form-fields-pane').each(function(){var pane=this,ta=$(pane).find('.ccp-form-fields-json')[0],list=$(pane).find('.ccp-form-fields-list')[0],fields=safe(ta.value);
function sync(){ta.value=JSON.stringify(fields);}
function render(){list.innerHTML='';if(!fields.length){var em=el('div','ccp-form-fields-empty');em.textContent=labels.empty||'No fields';list.appendChild(em);return;}
fields.forEach(function(f,idx){if(!f||typeof f!=='object')f=fields[idx]={type:'text',key:'field_'+(idx+1),label:'',placeholder:'',required:0};
var card=el('div','ccp-form-field-card'),head=el('div','ccp-form-field-head'),type=el('select','form-control ccp-form-field-type');[['text','Text'],['email','Email'],['tel','Phone'],['textarea','Textarea'],['select','Select'],['checkbox','Checkbox']].forEach(function(o){var op=el('option');op.value=o[0];op.textContent=o[1];op.selected=f.type===o[0];type.appendChild(op);});type.onchange=function(){f.type=type.value;if(f.type!=='select')delete f.options;else if(!Array.isArray(f.options))f.options=[];sync();render();};head.appendChild(type);
var acts=el('div','ccp-form-field-actions');function btn(icon,title){var b=el('button','btn btn-default btn-sm');b.type='button';b.title=title;b.innerHTML='<i class="fa '+icon+'"></i>';return b;}var up=btn('fa-arrow-up',labels.up||'Up'),dn=btn('fa-arrow-down',labels.down||'Down'),rm=btn('fa-trash',labels.remove||'Remove');rm.className='btn btn-danger btn-sm';up.disabled=idx===0;dn.disabled=idx===fields.length-1;up.onclick=function(){if(idx){var x=fields[idx-1];fields[idx-1]=fields[idx];fields[idx]=x;sync();render();}};dn.onclick=function(){if(idx<fields.length-1){var x=fields[idx+1];fields[idx+1]=fields[idx];fields[idx]=x;sync();render();}};rm.onclick=function(){fields.splice(idx,1);sync();render();};acts.append(up,dn,rm);head.appendChild(acts);card.appendChild(head);
var body=el('div','ccp-form-field-body'),grid=el('div','ccp-form-field-grid'),a=valInput(labels.label||'Label',f.label),b=valInput(labels.placeholder||'Placeholder',f.placeholder),c=valInput(labels.key||'Key',f.key);a.input.oninput=function(){f.label=this.value;sync();};b.input.oninput=function(){f.placeholder=this.value;sync();};c.input.oninput=function(){f.key=this.value;sync();};grid.append(a.wrap,b.wrap,c.wrap);body.appendChild(grid);
var req=el('label','ccp-form-required'),ck=el('input');ck.type='checkbox';ck.checked=!!f.required;ck.onchange=function(){f.required=this.checked?1:0;sync();};req.appendChild(ck);req.appendChild(document.createTextNode(' '+(labels.required||'Required')));body.appendChild(req);
if(f.type==='select'){var o=valInput(labels.options||'Options',Array.isArray(f.options)?f.options.join('\n'):'','textarea');o.input.rows=4;o.input.oninput=function(){f.options=this.value.split(/\r?\n/).map(function(x){return x.trim();}).filter(Boolean);sync();};body.appendChild(o.wrap);}card.appendChild(body);list.appendChild(card);});}
$(pane).find('[data-add-field]').on('click',function(){var type=$(this).data('add-field'),n=fields.length+1;fields.push({type:type,key:'field_'+n,label:'',placeholder:'',required:0,options:type==='select'?[]:undefined});sync();render();});render();paneStates.push({pane:pane,setFields:function(next){fields=Array.isArray(next)?next:[];sync();render();}});});
$root.find('[data-form-preset]').on('click',function(){var key=$(this).data('form-preset'),confirmText=$root.find('.ccp-form-presets').data('confirm')||'';if(confirmText&&!window.confirm(confirmText)){return;}var firstPreset=null;paneStates.forEach(function(state){var $pane=$(state.pane).closest('.tab-pane'),lang=$pane.data('language-code')||'en',preset=presetData(key,lang);if(!firstPreset){firstPreset=preset;}$pane.find('input[name$="[title]"]').val(preset.title);$pane.find('textarea[name$="[description]"]').val(preset.description);$pane.find('input[name$="[submit_text]"]').val(preset.submit);$pane.find('input[name$="[success_text]"]').val(preset.success);state.setFields(preset.fields);});if(firstPreset){$kind.val(firstPreset.kind||'request').trigger('change');}if(!$.trim($root.find('#input-name').val())){$root.find('#input-name').val($(this).text().trim());}});
}
$(function(){$('.ccp-reusable-form-builder').each(function(){init(this);});});
})(jQuery);


(function($){
  'use strict';
  var cache=null;
  function esc(s){return $('<div>').text(String(s||'')).html();}
  function normalizeIconClass(v){v=String(v||'').trim();if(!v)return 'fa fa-envelope-o';if(v.indexOf(' ')<0&&/^fa-[a-z0-9-]+$/i.test(v))return 'fa '+v;return v;}
  function updatePreview(){var v=$('#input-button-icon').val();$('.ccp-icon-preview').html('<i class="'+esc(normalizeIconClass(v))+'"></i>');}
  function fallbackIcons(){return [
    {c:'fa-solid fa-envelope',n:'envelope',s:'email envelope mail message contact'},
    {c:'fa-solid fa-phone',n:'phone',s:'phone call callback contact'},
    {c:'fa-solid fa-comment',n:'comment',s:'comment question message chat'},
    {c:'fa-solid fa-circle-question',n:'circle-question',s:'question help info'},
    {c:'fa-solid fa-cart-shopping',n:'cart-shopping',s:'cart shopping buy purchase'},
    {c:'fa-solid fa-bag-shopping',n:'bag-shopping',s:'bag shopping order'},
    {c:'fa-solid fa-check',n:'check',s:'check yes ok'},
    {c:'fa-solid fa-circle-info',n:'circle-info',s:'info information'},
    {c:'fa-solid fa-truck',n:'truck',s:'truck delivery shipping'},
    {c:'fa-solid fa-ruler',n:'ruler',s:'ruler size dimensions'},
    {c:'fa-solid fa-wrench',n:'wrench',s:'wrench service repair'},
    {c:'fa-solid fa-gear',n:'gear',s:'gear settings'},
    {c:'fa-solid fa-heart',n:'heart',s:'heart favorite'},
    {c:'fa-solid fa-star',n:'star',s:'star rating'},
    {c:'fa-solid fa-user',n:'user',s:'user person customer'},
    {c:'fa-solid fa-location-dot',n:'location-dot',s:'location map address'}
  ];}
  function normalizeRows(json){
    var rows=json&&Array.isArray(json.icons)?json.icons:[];
    return rows.map(function(r){return {c:r.c||r['class']||'',n:r.n||r.name||'',s:r.s||r.search||((r.name||'')+' '+(r.label||''))};}).filter(function(r){return r.c;});
  }
  function render(q){
    var grid=$('#ccp-icon-picker-grid'),term=String(q||'').toLowerCase().trim(),current=normalizeIconClass($('#input-button-icon').val());
    grid.empty();
    var rows=(cache||[]).filter(function(r){return !term||String((r.s||'')+' '+(r.n||'')).toLowerCase().indexOf(term)!==-1;});
    if(!rows.length){grid.append('<div class="text-muted text-center" style="grid-column:1/-1">—</div>');return;}
    rows.forEach(function(r){
      var b=$('<button type="button" class="ccp-icon-choice" aria-label=""></button>');
      b.attr('title',r.n||'');b.attr('aria-label',r.n||'');b.attr('data-icon',r.c);
      if(normalizeIconClass(r.c)===current)b.addClass('is-selected');
      b.append($('<i aria-hidden="true"></i>').attr('class',r.c));grid.append(b);
    });
  }
  function loadIcons(btn){
    var d=$.Deferred(),catalogUrl=String(btn.attr('data-catalog-url')||''),url=String(btn.attr('data-icons-url')||'');
    if(Array.isArray(window.CodeCartIconCatalog)&&window.CodeCartIconCatalog.length){
      cache=window.CodeCartIconCatalog.map(function(r){return {c:r[0]||'',n:r[1]||'',s:r[2]||''};}).filter(function(r){return r.c;});
      d.resolve(cache);return d.promise();
    }
    function useAjax(){
      if(!url){cache=fallbackIcons();d.resolve(cache);return;}
      $.ajax({url:url,type:'GET',dataType:'json',cache:false,timeout:15000})
        .done(function(json){cache=normalizeRows(json);if(!cache.length)cache=fallbackIcons();d.resolve(cache);})
        .fail(function(){cache=fallbackIcons();d.resolve(cache);});
    }
    if(catalogUrl){
      $.getScript(catalogUrl).done(function(){
        if(Array.isArray(window.CodeCartIconCatalog)&&window.CodeCartIconCatalog.length){
          cache=window.CodeCartIconCatalog.map(function(r){return {c:r[0]||'',n:r[1]||'',s:r[2]||''};}).filter(function(r){return r.c;});
          d.resolve(cache);
        }else{useAjax();}
      }).fail(useAjax);
    }else{useAjax();}
    return d.promise();
  }
  $(document).on('click','#button-icon-picker',function(){
    var btn=$(this),modal=$('#ccp-icon-picker-modal');modal.modal('show');$('#ccp-icon-search').val('');
    if(cache){render('');return;}
    $('#ccp-icon-picker-grid').html('<div class="text-muted text-center" style="grid-column:1/-1"><i class="fa fa-spinner fa-spin"></i></div>');
    loadIcons(btn).done(function(){render('');});
  });
  $(document).on('shown.bs.modal','#ccp-icon-picker-modal',function(){$('#ccp-icon-search').trigger('focus');});
  $(document).on('input','#ccp-icon-search',function(){render(this.value);});
  $(document).on('click','#ccp-icon-picker-grid .ccp-icon-choice',function(e){
    e.preventDefault();e.stopPropagation();var v=String($(this).attr('data-icon')||'').trim();if(!v)return;
    $('#input-button-icon').val(v).trigger('change');updatePreview();$('#ccp-icon-picker-modal').modal('hide');
  });
  $(document).on('click','#button-icon-clear',function(e){e.preventDefault();$('#input-button-icon').val('').trigger('change');updatePreview();});
  $(updatePreview);
})(jQuery);
