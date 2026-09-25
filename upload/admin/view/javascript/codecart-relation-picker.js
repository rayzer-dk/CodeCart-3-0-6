(function($){
  'use strict';

  function escapeText(value){ return value == null ? '' : String(value); }

  function appendItem(target, idPrefix, inputName, row){
    var id = parseInt(row.id, 10);
    if (!id || !target || !inputName) { return false; }
    var $target = $(target);
    if (!$target.length || $('#' + idPrefix + id).length) { return false; }

    var $wrap = $('<div>', {id: idPrefix + id});
    $('<i>', {'class':'fa fa-minus-circle'}).appendTo($wrap);
    $wrap.append(document.createTextNode(' ' + escapeText(row.name)));
    $('<input>', {type:'hidden', name:inputName, value:id}).appendTo($wrap);
    $target.append($wrap);
    return true;
  }

  function countItems(target, inputName){
    if (!target || !inputName) { return 0; }
    var $target=$(target);
    return $target.length ? $target.find('input[type="hidden"][name="'+String(inputName).replace(/"/g,'\\"')+'"]' ).length : 0;
  }

  function updateInitialStatus($button){
    var $status=$($button.data('status-target')||'');
    if(!$status.length){return;}
    var target=$button.data('product-target')||$button.data('article-target')||'';
    var name=$button.data('product-name')||$button.data('article-name')||'';
    var total=countItems(target,name);
    var max=parseInt($button.data('limit'),10)||0;
    var template=String($button.data('count-template')||'Selected: %t. Auto selection limit: %m.');
    $status.removeClass('text-danger text-success').text(template.replace('%t',total).replace('%m',max));
  }

  $(function(){
    $('[data-codecart-relation-picker]').each(function(){ updateInitialStatus($(this)); });
  });

  $(document).on('click', '[data-codecart-relation-picker]', function(){
    var $button = $(this);
    var url = String($button.data('url') || '');
    if (!url || $button.prop('disabled')) { return; }

    var originalHtml = $button.html();
    var $status = $($button.data('status-target') || '');
    $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
    if ($status.length) { $status.removeClass('text-danger text-success').text(''); }

    $.getJSON(url).done(function(json){
      if (json && json.error) {
        if ($status.length) { $status.addClass('text-danger').text(json.error); }
        return;
      }
      var addedProducts = 0, addedArticles = 0;
      $.each((json && json.products) || [], function(_, row){
        if (appendItem($button.data('product-target'), String($button.data('product-prefix') || 'product-related'), String($button.data('product-name') || ''), row)) { addedProducts++; }
      });
      $.each((json && json.articles) || [], function(_, row){
        if (appendItem($button.data('article-target'), String($button.data('article-prefix') || 'article-related'), String($button.data('article-name') || ''), row)) { addedArticles++; }
      });
      if ($status.length) {
        var target=$button.data('product-target')||$button.data('article-target')||'';
        var name=$button.data('product-name')||$button.data('article-name')||'';
        var total=countItems(target,name);
        var added=$button.data('product-target') ? addedProducts : addedArticles;
        var max=parseInt($button.data('limit'),10)||0;
        var template = String($button.data('success') || 'Auto selection added: %a. Selected now: %t. Limit: %m. Save the form to apply.');
        $status.addClass('text-success').text(template.replace('%a',added).replace('%t',total).replace('%m',max).replace('%p',addedProducts).replace('%r',addedArticles));
      }
    }).fail(function(xhr){
      if ($status.length) { $status.addClass('text-danger').text('HTTP ' + xhr.status); }
    }).always(function(){
      $button.prop('disabled', false).html(originalHtml);
    });
  });

  $(document).on('click', '#product-related .fa-minus-circle, #article-related .fa-minus-circle', function(){
    var $container=$(this).closest('#product-related, #article-related');
    window.setTimeout(function(){
      $('[data-codecart-relation-picker]').each(function(){
        var $button=$(this), target=$button.data('product-target')||$button.data('article-target')||'';
        if(target && $container.is(target)){ updateInitialStatus($button); }
      });
    },0);
  });
})(jQuery);
