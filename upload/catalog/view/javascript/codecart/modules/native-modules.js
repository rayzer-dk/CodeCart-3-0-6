/* CodeCart PRO native storefront modules. Loaded only when a compatible module renders. */
/* CodeCart PRO RC91: responsive, conflict-resistant and seamless carousel engine. */
(function($){
'use strict';
var uid=0,instances=[];
function pruneCodeCartNativeModules(){
  for(var i=instances.length-1;i>=0;i--){var entry=instances[i];if(!entry||!entry.node||!document.documentElement.contains(entry.node)){if(entry&&entry.destroy){entry.destroy();}instances.splice(i,1);}}
}
function initCodeCartNativeModules(scope){
  $(scope||document).find('.ccp-native-module--carousel,.ccp-native-product-module--carousel').each(function(){
    var $module=$(this); if($module.data('ccpNativeCarousel')){return;}
    var $track=$module.find('.ccp-native-module-track').first(),$viewport=$module.find('.ccp-native-module-viewport').first(),$pagination=$module.find('.ccp-native-module-pagination').first(),$toggle=$module.find('.ccp-native-module-autoplay-toggle').first();
    if(!$track.length||!$viewport.length||!$track.children('.ccp-native-module-item').length){return;}
    $module.data('ccpNativeCarousel',1).addClass('ccp-native-module--ready');
    var originalCount=$track.children('.ccp-native-module-item').length,index=0,timer=null,userPaused=false,inViewport=true,startX=null,startY=null,deltaX=0,deltaY=0,id=++uid,intersectionObserver=null,resizeObserver=null,destroyed=false,animating=false,pendingDirection=0,pendingManual=false,resizeTimer=null,ns='.ccpNativeCarousel'+id;
    function $items(){return $track.children('.ccp-native-module-item');}
    function isCards(){return $module.hasClass('ccp-native-module--products')||$module.hasClass('ccp-native-module--articles')||$module.hasClass('ccp-native-product-module');}
    function mobileCategoryGrid(){return $module.hasClass('ccp-native-module--categories') && $module.hasClass('ccp-category-wall--mobile-grid') && (window.innerWidth||document.documentElement.clientWidth||0)<768;}
    function responsiveColumns(){
      var node=$module[0],style=getComputedStyle(node),w=Math.max(0,$viewport.width()||$module.width()||0),viewportW=Math.max(0,window.innerWidth||document.documentElement.clientWidth||0),$content=$module.closest('#content'),sidebar=$module.closest('#column-left,#column-right').length>0,inMainContent=$content.length>0,cardModule=$module.hasClass('ccp-native-module--products')||$module.hasClass('ccp-native-module--articles')||$module.hasClass('ccp-native-product-module'),v;
      if(sidebar){v=1;}
      else if(viewportW<576||(!inMainContent&&w>0&&w<576)){v=parseFloat(style.getPropertyValue('--ccp-module-cols-mobile'))||2;}
      else if(viewportW<992||(!inMainContent&&w>0&&w<992)){v=parseFloat(style.getPropertyValue('--ccp-module-cols-tablet'))||3;}
      else if(cardModule&&inMainContent&&($content.hasClass('col-sm-12')||$content.hasClass('col-md-12'))){v=5;}
      else if(cardModule&&inMainContent&&($content.hasClass('col-sm-9')||$content.hasClass('col-md-9'))){v=4;}
      else if(cardModule&&inMainContent&&($content.hasClass('col-sm-6')||$content.hasClass('col-md-6'))){v=2;}
      else{v=parseFloat(style.getPropertyValue('--ccp-module-cols-desktop'))||4;}
      // Do not clamp the column count to the number of returned products. Two sale
      // items in a five-column area must occupy two normal card slots, not stretch
      // to 50% each. Scrolling is disabled separately when count <= visible().
      var fractionalMobileCategory=$module.hasClass('ccp-native-module--categories')&&viewportW<576&&!mobileCategoryGrid();
      v=fractionalMobileCategory?Math.max(1,v):Math.max(1,Math.floor(v));node.style.setProperty('--ccp-module-cols-current',String(v));$module.toggleClass('ccp-native-module--sidebar',sidebar);return v;
    }
    function visible(){return responsiveColumns();}
    function pageSize(){return Math.max(1,Math.floor(visible()));}
    function maxIndex(){return Math.max(0,originalCount-visible());}
    function loopEnabled(){return parseInt($module.attr('data-loop'),10)===1&&originalCount>visible();}
    function stepSize(){if($module.hasClass('ccp-native-module--products')||$module.hasClass('ccp-native-product-module')){return 1;}return String($module.attr('data-step')||'item')==='page'?pageSize():1;}
    function positions(){
      var step=Math.max(1,stepSize()),out=[0],p,max;
      if(loopEnabled()){for(p=step;p<originalCount;p+=step){out.push(p);}return out;}
      max=maxIndex();if(max<=0){return out;}for(p=step;p<max;p+=step){out.push(p);}if(out[out.length-1]!==max){out.push(max);}return out;
    }
    function buildPagination(){if(!$pagination.length){return;}var pages=positions(),count=pages.length;if(count<=1){$pagination.empty().attr('hidden','hidden');return;}$pagination.removeAttr('hidden');if($pagination.children().length!==count){$pagination.empty();for(var i=0;i<count;i++){$('<button type="button" class="ccp-native-module-dot"/>').attr({'data-index':pages[i],'aria-label':(i+1)+' / '+count}).appendTo($pagination);}}else{$pagination.children('.ccp-native-module-dot').each(function(i){$(this).attr('data-index',pages[i]);});}updatePagination();}
    function cyclicDistance(a,b){var d=Math.abs(a-b);return Math.min(d,Math.max(0,originalCount-d));}
    function updatePagination(){if(!$pagination.length){return;}var $dots=$pagination.children('.ccp-native-module-dot'),nearest=-1,distance=Infinity;$dots.each(function(i){var target=parseInt($(this).attr('data-index'),10)||0,d=loopEnabled()?cyclicDistance(target,index):Math.abs(target-index);if(d<distance){distance=d;nearest=i;}});$dots.each(function(i){var active=i===nearest;$(this).toggleClass('is-active',active).attr('aria-current',active?'true':'false');});}
    function itemWidth(){var n=visible(),w=$viewport[0].getBoundingClientRect().width||$viewport.width()||0;return n>0?Math.max(1,w/n):0;}
    function updateToggle(){if(!$toggle.length){return;}var unavailable=parseInt($module.attr('data-autoplay'),10)!==1||originalCount<=visible()||(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches);$toggle.prop('hidden',unavailable);if(unavailable){return;}var label=userPaused?($module.attr('data-play-label')||'Resume autoplay'):($module.attr('data-pause-label')||'Pause autoplay');$toggle.attr({'title':label,'aria-label':label,'aria-pressed':userPaused?'true':'false'}).find('i').attr('class',userPaused?'fa fa-play':'fa fa-pause');}
    function updateControls(){var noScroll=originalCount<=visible();$module.toggleClass('ccp-native-module--not-scrollable',noScroll);$module.find('.ccp-native-module-prev').prop('hidden',noScroll).prop('disabled',!loopEnabled()&&index<=0);$module.find('.ccp-native-module-next').prop('hidden',noScroll).prop('disabled',!loopEnabled()&&index>=maxIndex());updatePagination();updateToggle();}
    function resetTrack(){var el=$track[0];$track.addClass('ccp-native-module-track--instant').css('transform','translate3d(0,0,0)');if(el){void el.offsetWidth;}$track.removeClass('ccp-native-module-track--instant');}
    function paint(animate){if(loopEnabled()){resetTrack();updateControls();return;}var max=maxIndex();index=Math.max(0,Math.min(index,max));if(animate===false){$track.addClass('ccp-native-module-track--instant');}$track.css('transform','translate3d('+(-(index*itemWidth()))+'px,0,0)');if(animate===false){if($track[0]){void $track[0].offsetWidth;}$track.removeClass('ccp-native-module-track--instant');}updateControls();}
    function rotate(dir,count,userAction){
      dir=dir<0?-1:1;
      if(animating){if(userAction){pendingDirection=dir;pendingManual=true;}return;}
      if(!loopEnabled()){return;}
      if(userAction){deferAfterInteraction();}
      count=Math.max(1,Math.min(count,originalCount-1));var unit=itemWidth();if(!unit){return;}animating=true;$module.addClass('is-animating');stop();var el=$track[0],done=false;
      function finish(){
        if(done){return;}done=true;$track.off('transitionend.ccpNativeRotate');
        if(dir>0){for(var i=0;i<count;i++){var first=el.firstElementChild;if(first){el.appendChild(first);}}}
        resetTrack();index=(index+(dir*count))%originalCount;if(index<0){index+=originalCount;}
        animating=false;$module.removeClass('is-animating');updateControls();
        if(pendingDirection){var queued=pendingDirection,manual=pendingManual;pendingDirection=0;pendingManual=false;window.requestAnimationFrame(function(){rotate(queued,Math.max(1,stepSize()),manual);});}
        else{start();}
      }
      $track.on('transitionend.ccpNativeRotate',function(e){var oe=e.originalEvent;if((!oe||oe.target===el)&&(!oe||!oe.propertyName||oe.propertyName==='transform')){finish();}});
      if(dir<0){$track.addClass('ccp-native-module-track--instant');for(var j=0;j<count;j++){var last=el.lastElementChild;if(last){el.insertBefore(last,el.firstElementChild);}}$track.css('transform','translate3d('+(-(count*unit))+'px,0,0)');if(el){void el.offsetWidth;}$track.removeClass('ccp-native-module-track--instant');window.requestAnimationFrame(function(){$track.css('transform','translate3d(0,0,0)');});}
      else{$track.css('transform','translate3d('+(-(count*unit))+'px,0,0)');}
      window.setTimeout(finish,460);
    }
    function move(dir,userAction){
      dir=dir<0?-1:1;
      if(animating){if(userAction){pendingDirection=dir;pendingManual=true;}return;}
      var step=Math.max(1,stepSize());if(originalCount<=visible()){index=0;paint(false);return;}
      if(loopEnabled()){rotate(dir,step,!!userAction);return;}
      var max=maxIndex();index=dir>0?Math.min(max,index+step):Math.max(0,index-step);paint();if(userAction){deferAfterInteraction();}
    }
    function stop(){if(timer){window.clearInterval(timer);timer=null;}}
    function hasKeyboardFocus(){var active=document.activeElement;return !!(active&&$module[0].contains(active)&&active.matches&&active.matches(':focus-visible'));}
    function start(){if(mobileCategoryGrid()){stop();return;}if(timer){window.clearInterval(timer);timer=null;}if(destroyed||animating||!inViewport||userPaused||parseInt($module.attr('data-autoplay'),10)!==1||originalCount<=visible()||(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches)||document.hidden||hasKeyboardFocus()){updateToggle();return;}var delay=Math.max(1500,parseInt($module.attr('data-autoplay-delay'),10)||5000);timer=window.setInterval(function(){move(1);},delay);updateToggle();}
    function deferAfterInteraction(){stop();if(parseInt($module.attr('data-autoplay'),10)===1){userPaused=true;}updateToggle();}
    function go(target,userAction){target=parseInt(target,10)||0;if(loopEnabled()){var forward=(target-index+originalCount)%originalCount,backward=(index-target+originalCount)%originalCount;if(forward===0){return;}rotate(forward<=backward?1:-1,Math.min(forward,backward),!!userAction);}else{index=Math.max(0,Math.min(target,maxIndex()));paint();if(userAction){deferAfterInteraction();}}}
    function toggleAutoplay(){userPaused=!userPaused;stop();updateToggle();if(!userPaused){start();}}
    $module.on('click','.ccp-native-module-prev',function(e){move(-1,true);if(e.originalEvent&&e.originalEvent.detail>0&&this.blur){this.blur();}}).on('click','.ccp-native-module-next',function(e){move(1,true);if(e.originalEvent&&e.originalEvent.detail>0&&this.blur){this.blur();}}).on('click','.ccp-native-module-dot',function(e){go($(this).attr('data-index'),true);if(e.originalEvent&&e.originalEvent.detail>0&&this.blur){this.blur();}}).on('click','.ccp-native-module-autoplay-toggle',function(e){toggleAutoplay();if(e.originalEvent&&e.originalEvent.detail>0&&this.blur){this.blur();}}).on('focusin',function(e){if(e.target&&e.target.matches&&e.target.matches(':focus-visible')){stop();}}).on('focusout',function(){window.setTimeout(start,0);}).on('keydown',function(e){if(e.key==='ArrowLeft'){e.preventDefault();move(-1,true);}else if(e.key==='ArrowRight'){e.preventDefault();move(1,true);}});
    $track.on('touchstart',function(e){var t=e.originalEvent.touches&&e.originalEvent.touches[0];startX=t?t.clientX:null;startY=t?t.clientY:null;deltaX=0;deltaY=0;stop();}).on('touchmove',function(e){if(startX===null){return;}var t=e.originalEvent.touches&&e.originalEvent.touches[0];if(t){deltaX=t.clientX-startX;deltaY=t.clientY-startY;}}).on('touchend touchcancel',function(){var moved=Math.abs(deltaX)>35&&Math.abs(deltaX)>Math.abs(deltaY)*1.15;if(moved){move(deltaX<0?1:-1,true);}startX=null;startY=null;deltaX=0;deltaY=0;if(!moved){start();}});
    function relayout(){if(destroyed){return;}window.clearTimeout(resizeTimer);resizeTimer=window.setTimeout(function(){if(!destroyed){responsiveColumns();resetTrack();buildPagination();updateControls();start();}},100);}
    if(typeof ResizeObserver!=='undefined'){resizeObserver=new ResizeObserver(relayout);resizeObserver.observe($viewport[0]);}else{$(window).on('resize'+ns,relayout);}
    $(document).on('visibilitychange'+ns,function(){if(destroyed||document.hidden){stop();}else{relayout();}});
    if(typeof IntersectionObserver!=='undefined'){intersectionObserver=new IntersectionObserver(function(entries){if(destroyed||!entries.length){return;}inViewport=!!entries[0].isIntersecting;if(inViewport){start();}else{stop();}},{rootMargin:'160px 0px'});intersectionObserver.observe($module[0]);}
    function destroy(){if(destroyed){return;}destroyed=true;stop();window.clearTimeout(resizeTimer);$(window).off(ns);$(document).off(ns);$track.off('.ccpNativeRotate');if(resizeObserver){resizeObserver.disconnect();resizeObserver=null;}if(intersectionObserver){intersectionObserver.disconnect();intersectionObserver=null;}}
    instances.push({node:$module[0],destroy:destroy});
    var label=$module.attr('aria-label')||$.trim($module.find('.ccp-native-module-heading h3').first().text())||'Carousel';
    $module.attr({'tabindex':$module.attr('tabindex')||'0','role':'region','aria-roledescription':'carousel','aria-label':label});$track.attr('role','list');$items().attr('role','listitem');$viewport.attr('aria-live','off');
    responsiveColumns();resetTrack();buildPagination();updateControls();start();
  });
}
$(function(){initCodeCartNativeModules(document);});
$(document).on('codecart:content-updated',function(e,scope){pruneCodeCartNativeModules();initCodeCartNativeModules(scope||document);});
window.CodeCartNativeModules={init:initCodeCartNativeModules};window.CodeCartNativeProductModules=window.CodeCartNativeModules;
})(window.jQuery);

/* CodeCart PRO RC79: responsive sidebar modules and filter UX. */
(function($){
'use strict';
function navigate(url){
  if(window.CodeCartCatalogAjax&&typeof window.CodeCartCatalogAjax.navigate==='function'){window.CodeCartCatalogAjax.navigate(url);}else{window.location.href=url;}
}
function applyFilter($module){
  var selected=[];
  $module.find('input[name="filter[]"]:checked').each(function(){var id=parseInt(this.value,10);if(id>0){selected.push(id);}});
  var url=String($module.attr('data-filter-action')||'');
  if(selected.length){url+=(url.indexOf('?')===-1?'?':'&')+'filter='+selected.join(',');}
  navigate(url);
}
$(document).on('click','.ccp-sidebar-module__toggle',function(e){
  e.preventDefault();
  var $module=$(this).closest('.ccp-sidebar-module');
  var open=!$module.hasClass('is-open');
  $module.toggleClass('is-open',open);
  $(this).attr('aria-expanded',open?'true':'false');
});
$(document).on('click','.ccp-filter-group__toggle',function(e){
  e.preventDefault();
  var $group=$(this).closest('.ccp-filter-group');
  var open=!$group.hasClass('is-open');
  $group.toggleClass('is-open',open);
  $(this).attr('aria-expanded',open?'true':'false');
});
$(document).on('click','.ccp-filter-module [data-filter-apply]',function(e){e.preventDefault();applyFilter($(this).closest('.ccp-filter-module'));});
var filterTimer=null;
$(document).on('change','.ccp-filter-module input[name="filter[]"]',function(){
  var $module=$(this).closest('.ccp-filter-module');
  $(this).closest('.ccp-filter-option').toggleClass('is-selected',this.checked);
  if(parseInt($module.attr('data-auto-apply'),10)!==1){return;}
  window.clearTimeout(filterTimer);
  filterTimer=window.setTimeout(function(){applyFilter($module);},250);
});
})(window.jQuery);
