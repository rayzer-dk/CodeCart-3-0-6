(function($){
    'use strict';
    function fallbackCopy(value){
        var textarea=document.createElement('textarea');
        textarea.value=value;
        textarea.setAttribute('readonly','readonly');
        textarea.style.position='fixed';
        textarea.style.opacity='0';
        document.body.appendChild(textarea);
        textarea.select();
        var ok=false;
        try{ ok=document.execCommand('copy'); }catch(e){ ok=false; }
        document.body.removeChild(textarea);
        return ok;
    }
    function flashButton($button){
        var original=$button.html();
        var copied=$button.attr('data-copied') || 'Copied';
        $button.addClass('is-copied').html('<i class="fa fa-check"></i> '+copied);
        window.setTimeout(function(){ $button.removeClass('is-copied').html(original); },1400);
    }
    $(document).on('click','.ccp-copy-value',function(){
        var $button=$(this);
        var value=String($button.attr('data-copy') || '');
        if(!value){ return; }
        if(navigator.clipboard && window.isSecureContext){
            navigator.clipboard.writeText(value).then(function(){ flashButton($button); },function(){ if(fallbackCopy(value)){ flashButton($button); } });
        }else if(fallbackCopy(value)){
            flashButton($button);
        }
    });
    $(document).on('click','.ccp-show-qr',function(){
        var $button=$(this),$viewer=$('#ccp-project-qr-viewer');
        if(!$viewer.length){ return; }
        $('#ccp-project-qr-title').text(String($button.attr('data-name') || 'QR'));
        $('#ccp-project-qr-value').text(String($button.attr('data-value') || ''));
        $('#ccp-project-qr-image').attr('src',String($button.attr('data-qr') || ''));
        $viewer.prop('hidden',false).attr('aria-hidden','false');
    });
    $(document).on('click','[data-qr-close]',function(){
        $('#ccp-project-qr-viewer').prop('hidden',true).attr('aria-hidden','true');
        $('#ccp-project-qr-image').attr('src','');
    });
    $(document).on('keydown',function(e){
        if(e.key === 'Escape' && !$('#ccp-project-qr-viewer').prop('hidden')){
            $('#ccp-project-qr-viewer').prop('hidden',true).attr('aria-hidden','true');
            $('#ccp-project-qr-image').attr('src','');
        }
    });
})(window.jQuery);
