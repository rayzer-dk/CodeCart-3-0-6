(function($) {
    'use strict';

    if (!$ || !$.fn || !$.fn.summernote) {
        return;
    }

    var languageLoads = {};

    function loadLanguage(lang) {
        if (!lang || lang === 'en-US' || ($.summernote.lang && $.summernote.lang[lang])) {
            return $.Deferred().resolve().promise();
        }
        if (!languageLoads[lang]) {
            languageLoads[lang] = $.getScript('view/javascript/summernote/lang/summernote-' + encodeURIComponent(lang) + '.min.js?v=3.0.6.0');
        }
        return languageLoads[lang];
    }

    function isDeferredTab(element) {
        var $tab = $(element).closest('.tab-pane');
        return $tab.length && !$tab.hasClass('active');
    }

    function openImageManager(element) {
        var eventName = 'codecart:editor-image-selected';
        $('#modal-image').remove();
        $(document).off(eventName + '.summernote').one(eventName + '.summernote', function(e, images) {
            var item = images && images.length ? images[0] : null;
            if (item && item.href) {
                $(element).summernote('insertImage', item.href);
            }
        });
        $.get('index.php?route=common/filemanager&user_token=' + encodeURIComponent(getURLVar('user_token')) + '&select_event=' + encodeURIComponent(eventName) + (typeof codecartFileManagerDirectoryParam === 'function' ? codecartFileManagerDirectoryParam() : ''), function(html) {
            $('body').append('<div id="modal-image" class="modal">' + html + '</div>');
            $('#modal-image').modal('show');
        });
    }

    var codeMirrorThemes = ['default', '3024-day', '3024-night', 'abcdef', 'ambiance', 'ambiance-mobile', 'base16-dark', 'base16-light', 'bespin', 'blackboard', 'cobalt', 'colorforth', 'dracula', 'eclipse', 'elegant', 'erlang-dark', 'hopscotch', 'icecoder', 'isotope', 'lesser-dark', 'liquibyte', 'material', 'mbo', 'mdn-like', 'midnight', 'monokai', 'neat', 'neo', 'night', 'paraiso-dark', 'paraiso-light', 'pastel-on-dark', 'railscasts', 'rubyblue', 'seti', 'solarized', 'the-matrix', 'tomorrow-night-bright', 'tomorrow-night-eighties', 'ttcn', 'twilight', 'vibrant-ink', 'xq-dark', 'xq-light', 'yeti', 'zenburn'];

    function getCodeMirrorTheme() {
        var theme = 'default';
        try {
            var saved = window.localStorage ? window.localStorage.getItem('codecart_codemirror_theme') : '';
            if (saved && codeMirrorThemes.indexOf(saved) !== -1) {
                theme = saved;
            }
        } catch (e) {}
        ensureCodeMirrorThemeCss(theme);
        return theme;
    }

    function ensureCodeMirrorThemeCss(theme) {
        if (codeMirrorThemes.indexOf(theme) === -1 || theme === 'default') {
            return;
        }
        var id = 'ccp-codemirror-theme-' + theme;
        if (document.getElementById(id)) {
            return;
        }
        var link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = 'view/javascript/codemirror/theme/' + encodeURIComponent(theme) + '.css?v=5.65.21';
        document.head.appendChild(link);
    }

    function applyCodeMirrorTheme($element, theme) {
        if (codeMirrorThemes.indexOf(theme) === -1) {
            return;
        }
        ensureCodeMirrorThemeCss(theme);
        try {
            if (window.localStorage) {
                window.localStorage.setItem('codecart_codemirror_theme', theme);
            }
        } catch (e) {}

        var context = $element.data('summernote');
        if (context && context.options && context.options.codemirror) {
            context.options.codemirror.theme = theme;
        }
        if (context && context.layoutInfo && context.layoutInfo.codable) {
            var cm = context.layoutInfo.codable.data('cmEditor');
            if (cm && typeof cm.setOption === 'function') {
                cm.setOption('theme', theme);
                if (typeof cm.refresh === 'function') {
                    cm.refresh();
                }
            }
        }
    }

    function attachCodeMirrorThemeSelector($element) {
        var context = $element.data('summernote');
        if (!context || !context.layoutInfo || !context.layoutInfo.toolbar) {
            return;
        }
        var $toolbar = context.layoutInfo.toolbar;
        if ($toolbar.find('.ccp-codemirror-theme-wrap').length) {
            return;
        }

        var current = getCodeMirrorTheme();
        var $wrap = $('<div class="note-btn-group btn-group ccp-codemirror-theme-wrap"></div>');
        var $select = $('<select class="form-control input-sm ccp-codemirror-theme" title="CodeMirror theme" aria-label="CodeMirror theme"></select>');
        $.each(codeMirrorThemes, function(_, theme) {
            var label = theme === 'default' ? 'Default' : theme;
            $('<option></option>').attr('value', theme).text(label).prop('selected', theme === current).appendTo($select);
        });
        $select.on('change', function() {
            applyCodeMirrorTheme($element, String(this.value || 'default'));
        });
        $wrap.append($select).appendTo($toolbar);

        $element.off('summernote.codeview.toggled.codecartTheme').on('summernote.codeview.toggled.codecartTheme', function() {
            applyCodeMirrorTheme($element, String($select.val() || current));
        });
    }

    function ccpAnchorSlug(value) {
        value = String(value || '').trim().toLowerCase();
        try { value = value.normalize('NFKD').replace(/[\u0300-\u036f]/g, ''); } catch (e) {}
        value = value.replace(/[^0-9A-Za-z\u00C0-\u024F\u0400-\u052F\u1E00-\u1EFF]+/g, '-').replace(/^-+|-+$/g, '');
        return value || 'section';
    }

    function ccpUniqueAnchorId($editable, seed) {
        var base = ccpAnchorSlug(seed), candidate = base, index = 2;
        while ($editable.find('[id]').filter(function() { return String(this.id || '') === candidate; }).length) {
            candidate = base + '-' + index++;
        }
        return candidate;
    }

    function initialize(element) {
        var $element = $(element);
        if ($element.data('codecart-summernote-ready')) {
            return;
        }
        $element.data('codecart-summernote-ready', true);
        var lang = $element.attr('data-lang') || 'en-US';
        loadLanguage(lang).always(function() {
            var effectiveLang = ($.summernote.lang && $.summernote.lang[lang]) ? lang : 'en-US';
            var langInfo = $.summernote.lang[effectiveLang] || $.summernote.lang['en-US'];
            if (langInfo && langInfo.video) {
                if (effectiveLang.indexOf('uk') === 0) { langInfo.video.providers = '(YouTube, Vimeo, Facebook Video, Instagram, Dailymotion, Google Drive, MP4/M4V/WebM/Ogg)'; }
                else if (effectiveLang.indexOf('ru') === 0) { langInfo.video.providers = '(YouTube, Vimeo, Facebook Video, Instagram, Dailymotion, Google Drive, MP4/M4V/WebM/Ogg)'; }
                else { langInfo.video.providers = '(YouTube, Vimeo, Facebook Video, Instagram, Dailymotion, Google Drive, MP4/M4V/WebM/Ogg)'; }
            }
            $element.summernote({
                lang: effectiveLang,
                disableDragAndDrop: true,
                codeviewFilter: true,
                codeviewFilterRegex: /<\/*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|ilayer|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|t(?:itle|extarea)|xml)[^>]*?>|(?:\s|\/)+on[a-z0-9_:-]+\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)|(?:\s|\/)+srcdoc\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)|(?:\s|\/)+(?:href|src|xlink:href|action|formaction)\s*=\s*(?:"\s*(?:javascript|vbscript|data\s*:\s*(?:text\/html|image\/svg\+xml)):[^"]*"|'\s*(?:javascript|vbscript|data\s*:\s*(?:text\/html|image\/svg\+xml)):[^']*'|(?:javascript|vbscript|data\s*:\s*(?:text\/html|image\/svg\+xml)):[^\s>]*)/gi,
                codeviewIframeFilter: true,
                height: 320,
                emptyPara: '',
                codemirror: {
                    mode: 'text/html',
                    htmlMode: true,
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 2,
                    tabSize: 2,
                    theme: getCodeMirrorTheme()
                },
                fontSizes: ['8', '9', '10', '11', '12', '13', '14', '16', '18', '20', '24', '30', '36', '48', '64'],
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'italic', 'clear']],
                    ['script', ['subscript', 'superscript']],
                    ['fontname', ['fontname']], ['fontsize', ['fontsize']], ['height', ['height']], ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']], ['table', ['table']],
                    ['insert', ['link', 'image', 'video', 'hr']],
                    ['codecart', ['ccpAnchor', 'ccpToc']],
                    ['history', ['undo', 'redo']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                popover: {
                    image: [['custom', ['imageAttributes']], ['resize', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']], ['float', ['floatLeft', 'floatRight', 'floatNone']], ['remove', ['removeMedia']]],
                    link: [['link', ['linkDialogShow', 'unlink']]],
                    table: [['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']], ['delete', ['deleteRow', 'deleteCol', 'deleteTable']]]
                },
                buttons: {
                    ccpAnchor: function() {
                        var ui = $.summernote.ui;
                        return ui.button({
                            contents: '<i class="fa fa-anchor"></i>',
                            tooltip: effectiveLang.indexOf('uk') === 0 ? ((window.CodeCartI18n||{}).ui_anchor_insert||'Insert anchor') : (effectiveLang.indexOf('ru') === 0 ? 'Вставить якорь' : 'Insert anchor'),
                            click: function() {
                                var label = window.prompt(effectiveLang.indexOf('uk') === 0 ? ((window.CodeCartI18n||{}).ui_anchor_name||'Anchor name') : (effectiveLang.indexOf('ru') === 0 ? 'Имя якоря' : 'Anchor name'), 'section');
                                if (label === null) return;
                                var context = $element.data('summernote');
                                var $editable = context && context.layoutInfo ? context.layoutInfo.editable : $();
                                var id = ccpUniqueAnchorId($editable, label);
                                $element.summernote('pasteHTML', '<span id="' + id + '" class="ccp-article-anchor"></span>');
                            }
                        }).render();
                    },
                    ccpToc: function() {
                        var ui = $.summernote.ui;
                        return ui.button({
                            contents: '<i class="fa fa-list"></i>',
                            tooltip: effectiveLang.indexOf('uk') === 0 ? ((window.CodeCartI18n||{}).ui_toc_build||'Build contents from H2/H3') : (effectiveLang.indexOf('ru') === 0 ? 'Создать оглавление из H2/H3' : 'Build contents from H2/H3'),
                            click: function() {
                                var context = $element.data('summernote');
                                var $editable = context && context.layoutInfo ? context.layoutInfo.editable : $();
                                if (!$editable.length) return;
                                var items = [];
                                $editable.find('h2,h3').each(function() {
                                    var $h = $(this), text = $.trim($h.text());
                                    if (!text) return;
                                    var id = String($h.attr('id') || '');
                                    if (!id) { id = ccpUniqueAnchorId($editable, text); $h.attr('id', id); }
                                    items.push({id:id,text:text,level:this.tagName.toLowerCase()});
                                });
                                if (!items.length) {
                                    window.alert(effectiveLang.indexOf('uk') === 0 ? ((window.CodeCartI18n||{}).ui_toc_need_headings||'The contents list requires H2 or H3 headings.') : (effectiveLang.indexOf('ru') === 0 ? 'Для оглавления нужны заголовки H2 или H3.' : 'The contents list requires H2 or H3 headings.'));
                                    return;
                                }
                                $editable.find('nav.ccp-article-toc').remove();
                                var title = effectiveLang.indexOf('uk') === 0 ? ((window.CodeCartI18n||{}).ui_toc_title||'Contents') : (effectiveLang.indexOf('ru') === 0 ? 'Оглавление' : 'Contents');
                                var $nav = $('<nav class="ccp-article-toc"></nav>').append($('<strong></strong>').text(title));
                                var $ul = $('<ul></ul>').appendTo($nav);
                                $.each(items, function(_, item) { $('<li></li>').addClass('ccp-toc-' + item.level).append($('<a></a>').attr('href','#' + item.id).text(item.text)).appendTo($ul); });
                                $editable.prepend($nav);
                                var updatedHtml = $editable.html();
                                $element.summernote('code', updatedHtml);
                                $element.trigger('change').trigger('summernote.change', [updatedHtml]);
                            }
                        }).render();
                    },
                    image: function() {
                        var ui = $.summernote.ui;
                        return ui.button({
                            contents: '<i class="note-icon-picture"></i>',
                            tooltip: ($.summernote.lang[effectiveLang] || $.summernote.lang['en-US']).image.image,
                            click: function() { openImageManager(element); }
                        }).render();
                    }
                }
                });
            attachCodeMirrorThemeSelector($element);
            (function(){
                var context = $element.data('summernote');
                if (!context || !context.layoutInfo) return;
                var scope = context.options.dialogsInBody ? $(document.body) : context.layoutInfo.editor.parent();
                scope.off('shown.bs.modal.codecartLinkRel').on('shown.bs.modal.codecartLinkRel', '.note-modal', function(){
                    var $dialog=$(this);
                    if ($dialog.find('.note-link-url').length && !$dialog.find('.ccp-link-rel').length) {
                        var uk=effectiveLang.indexOf('uk')===0, ru=effectiveLang.indexOf('ru')===0;
                        var label=uk?((window.CodeCartI18n||{}).ui_link_type||'Link type'):(ru?'Тип ссылки':'Link type');
                        var help=uk?((window.CodeCartI18n||{}).ui_link_help||'URL: https://, http://, mailto:, tel:, a relative path or #anchor-name.'):(ru?'URL: https://, http://, mailto:, tel:, относительный путь или #имя-якоря.':'URL: https://, http://, mailto:, tel:, a relative path or #anchor-name.');
                        var html='<div class="form-group note-form-group ccp-link-rel"><label class="note-form-label">'+label+'</label><select class="form-control ccp-link-rel-select"><option value="">Dofollow</option><option value="nofollow">Nofollow</option><option value="sponsored nofollow">Sponsored</option><option value="ugc nofollow">UGC</option></select><small class="help-block">'+help+'</small></div>';
                        $dialog.find('.note-link-url').closest('.form-group').after(html);
                        try {
                            var sel=window.getSelection&&window.getSelection();
                            var n=sel&&sel.anchorNode?sel.anchorNode:null;
                            if(n&&n.nodeType===3)n=n.parentNode;
                            var a=n&&n.closest?n.closest('a'):null;
                            var currentRel=a?String(a.getAttribute('rel')||''):'';
                            var value=currentRel.indexOf('sponsored')!==-1?'sponsored nofollow':(currentRel.indexOf('ugc')!==-1?'ugc nofollow':(currentRel.indexOf('nofollow')!==-1?'nofollow':''));
                            $dialog.find('.ccp-link-rel-select').val(value);
                        } catch(e) {}
                    }

                    if ($dialog.find('.note-link-url').length) {
                        $dialog.find('.ccp-link-anchor').remove();
                        var $editableForAnchors = context.layoutInfo.editable;
                        var anchors = [];
                        $editableForAnchors.find('[id]').each(function(){
                            var id = String(this.id || '').trim();
                            if (!id) return;
                            var labelText = $.trim($(this).text());
                            if (!labelText && /^(H2|H3|H4)$/i.test(this.tagName || '')) labelText = $.trim($(this).text());
                            anchors.push({id:id, text:labelText || id});
                        });
                        if (anchors.length) {
                            var ukAnchor=effectiveLang.indexOf('uk')===0, ruAnchor=effectiveLang.indexOf('ru')===0;
                            var anchorLabel=ukAnchor?((window.CodeCartI18n||{}).ui_anchor_on_page||'Anchor on this page'):(ruAnchor?'Якорь на этой странице':'Anchor on this page');
                            var chooseLabel=ukAnchor?((window.CodeCartI18n||{}).ui_anchor_choose||'— choose anchor —'):(ruAnchor?'— выберите якорь —':'— choose anchor —');
                            var $anchorGroup=$('<div class="form-group note-form-group ccp-link-anchor"><label class="note-form-label"></label><select class="form-control ccp-link-anchor-select"></select><small class="help-block"></small></div>');
                            $anchorGroup.find('label').text(anchorLabel);
                            $anchorGroup.find('select').append($('<option value=""></option>').text(chooseLabel));
                            $.each(anchors,function(_,item){$anchorGroup.find('select').append($('<option></option>').attr('value','#'+item.id).text(item.text+' (#'+item.id+')'));});
                            $anchorGroup.find('small').text(ukAnchor?((window.CodeCartI18n||{}).ui_anchor_help||'The URL is filled automatically. Unicode letters and digits are supported.'):(ruAnchor?'После выбора URL заполнится автоматически. Кириллица, латиница и цифры поддерживаются.':'The URL is filled automatically. Unicode letters and digits are supported.'));
                            $anchorGroup.find('select').on('change',function(){if(this.value){$dialog.find('.note-link-url').val(this.value).trigger('input');}});
                            $dialog.find('.note-link-url').closest('.form-group').after($anchorGroup);
                        }
                    }
                    if ($dialog.find('.note-video-url').length && !$dialog.find('.ccp-video-help').length) {
                        var msg=effectiveLang.indexOf('uk')===0?((window.CodeCartI18n||{}).ui_video_help||'Supported video URLs.'):(effectiveLang.indexOf('ru')===0?'Поддерживаются: YouTube (watch, youtu.be, Shorts, Live), Vimeo, Facebook Video (/videos/ID), Instagram post/reel, Dailymotion, Google Drive /file/d/.../view и прямые MP4/M4V/WebM/Ogg URL.':'Supported: YouTube (watch, youtu.be, Shorts, Live), Vimeo, Facebook Video (/videos/ID), Instagram post/reel, Dailymotion, Google Drive /file/d/.../view and direct MP4/M4V/WebM/Ogg URLs.');
                        $dialog.find('.note-video-url').after('<small class="help-block ccp-video-help">'+msg+'</small>');
                    }
                });
                scope.off('click.codecartLinkRel', '.note-link-btn').on('click.codecartLinkRel', '.note-link-btn', function(){
                    var $dialog=$(this).closest('.note-modal');
                    var rel=String($dialog.find('.ccp-link-rel-select').val()||'');
                    var href=String($dialog.find('.note-link-url').val()||'');
                    window.setTimeout(function(){
                        var $editable=context.layoutInfo.editable;
                        var $links=$editable.find('a');
                        var $target=$links.filter(function(){return String($(this).attr('href')||'')===href;}).last();
                        if (!$target.length) $target=$links.last();
                        if ($target.length) { if(rel){$target.attr('rel',rel);}else{$target.removeAttr('rel');} }
                    },0);
                });
            })();
        });
    }

    window.CodeCartSummernoteInit = function(root) { $(root || document).find('[data-toggle=\"summernote\"]').addBack('[data-toggle=\"summernote\"]').each(function(){ initialize(this); }); };
    $(function() { window.CodeCartSummernoteInit(document); });

    $(document).off('submit.codecartSummernote', 'form').on('submit.codecartSummernote', 'form', function() {
        $('[data-toggle="summernote"]').each(function() {
            if ($(this).data('codecart-summernote-ready') && $(this).summernote('codeview.isActivated')) {
                $(this).summernote('codeview.deactivate');
            }
        });
    });
})(window.jQuery);
