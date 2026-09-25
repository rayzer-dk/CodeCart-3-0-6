(function($){'use strict';
$(document).on('submit','form[data-ccp-form]',function(e){
  e.preventDefault();
  var $form=$(this),$btn=$form.find('.ccp-form-submit'),$msg=$form.find('.ccp-form-message');
  if($btn.prop('disabled'))return;
  $form.find('.ccp-form-field').removeClass('has-error').find('.ccp-form-field-error').empty();
  $msg.removeClass('is-error is-success').empty();
  var invalid=false;
  $form.find('[required]').each(function(){
    if((this.type==='checkbox'&&!this.checked)||(!this.value&&this.type!=='checkbox')){
      $(this).closest('.ccp-form-field').addClass('has-error');invalid=true;
    }
  });
  if(invalid){$msg.addClass('is-error').text('');return;}
  $btn.prop('disabled',true).addClass('is-loading');
  $.ajax({url:$form.attr('action'),type:'post',data:$form.serialize(),dataType:'json'})
    .done(function(json){
      if(json&&json.fields){$.each(json.fields,function(key,text){$form.find('[data-field="'+String(key).replace(/"/g,'')+'"]').addClass('has-error').find('.ccp-form-field-error').text(text);});}
      if(json&&json.error){$msg.addClass('is-error').text(json.error);}
      if(json&&json.success){
        $msg.addClass('is-success').text(json.success);
        $form.find(':input').not(':hidden,:button,:submit').val('').prop('checked',false);
        $form.find('.ccp-form-actions').hide();
        var $modal=$form.closest('.ccp-form-modal');
        if($modal.length){
          window.setTimeout(function(){
            $modal.modal('hide');
            window.setTimeout(function(){
              $msg.removeClass('is-error is-success').empty();
              $form.find('.ccp-form-actions').show();
            },250);
          },1000);
        }
      }
    })
    .fail(function(xhr){var text='';try{var j=JSON.parse(xhr.responseText);text=j.error||'';}catch(err){}$msg.addClass('is-error').text(text||$form.attr('data-client-error')||'Error');})
    .always(function(){$btn.prop('disabled',false).removeClass('is-loading');});
});
})(jQuery);
