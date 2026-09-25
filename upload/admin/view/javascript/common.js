function getURLVar(key) {
	var value = [];

	var query = String(document.location).split('?');

	if (query[1]) {
		var part = query[1].split('&');

		for (i = 0; i < part.length; i++) {
			var data = part[i].split('=');

			if (data[0] && data[1]) {
				value[data[0]] = data[1];
			}
		}

		if (value[key]) {
			return value[key];
		} else {
			return '';
		}
	}
}


function codecartFileManagerDirectoryParam() {
	try {
		var directory = window.localStorage ? window.localStorage.getItem('codecart_filemanager_directory') : '';
		return directory ? '&directory=' + encodeURIComponent(directory) : '';
	} catch (e) {
		return '';
	}
}

$(document).ready(function() {
	// CodeCart PRO invariant: the final breadcrumb is the current page, not a self-link.
	function codecartNormalizeBreadcrumbCurrent(scope) {
		var $scope = scope ? $(scope) : $(document);
		var $crumbs = $scope.find('.breadcrumb').add($scope.is && $scope.is('.breadcrumb') ? $scope : $());
		$crumbs.each(function() {
			var $last = $(this).children('li').last();
			if (!$last.length) { return; }
			var $link = $last.children('a').first();
			if ($link.length) {
				$link.replaceWith($('<span/>', {'aria-current':'page','class':'ccp-breadcrumb-current'}).html($link.html()));
			} else {
				$last.children('span').first().attr('aria-current','page').addClass('ccp-breadcrumb-current');
			}
		});
	}

	// Accessibility/UX safety net for legacy and third-party icon-only controls.
	// Explicit title/aria-label values always win; this only fills missing labels.
	function codecartPrepareIconButtonTitles(scope) {
		var lang=(document.documentElement.getAttribute('lang')||'en').toLowerCase();
		var uk=lang.indexOf('uk')===0, ru=lang.indexOf('ru')===0, ui=window.CodeCartI18n||{};
		var labels={
			'fa-calendar':['Select date','Выбрать дату','ui_select_date'],
			'fa-trash':['Delete','Удалить','ui_delete'], 'fa-times':['Close','Закрыть','ui_close'],
			'fa-plus':['Add','Добавить','ui_add'], 'fa-minus':['Remove','Убрать','ui_remove'],
			'fa-pencil':['Edit','Редактировать','ui_edit'], 'fa-edit':['Edit','Редактировать','ui_edit'],
			'fa-eye':['View','Просмотреть','ui_view'], 'fa-refresh':['Refresh','Обновить','ui_refresh'],
			'fa-upload':['Upload','Загрузить','ui_upload'], 'fa-download':['Download','Скачать','ui_download'],
			'fa-search':['Search','Поиск','ui_search'], 'fa-copy':['Copy','Копировать','ui_copy'],
			'fa-filter':['Filter','Фильтр','ui_filter'], 'fa-save':['Save','Сохранить','ui_save'],
			'fa-chevron-left':['Previous','Назад','ui_previous'], 'fa-chevron-right':['Next','Далее','ui_next'],
			'fa-chevron-up':['Collapse','Свернуть','ui_collapse'], 'fa-chevron-down':['Expand','Развернуть','ui_expand'],
			'fa-arrow-left':['Back','Назад','ui_back'], 'fa-arrow-right':['Continue','Далее','ui_continue'],
			'fa-image':['Select image','Выбрать изображение','ui_select_image'], 'fa-folder-open':['Open','Открыть','ui_open']
		};
		var generic=uk?(ui.ui_action||'Action'):(ru?'Действие':'Action');
		var $scope=scope?$(scope):$(document);
		$scope.find('button, a.btn, [role="button"]').addBack('button, a.btn, [role="button"]').each(function(){
			var $el=$(this);
			if($el.attr('title')||$el.attr('aria-label')){return;}
			if(!$el.find('i.fa, i[class*="fa-"], span.fa, span[class*="fa-"]').length){return;}
			var $clone=$el.clone(); $clone.find('i,svg,.fa,.fas,.far,.fab,.sr-only').remove();
			if($.trim($clone.text())!==''){return;}
			var label='';
			$el.find('i,span').each(function(){
				var classes=String(this.className||'').split(/\s+/);
				for(var i=0;i<classes.length;i++){
					if(labels[classes[i]]){
						var row=labels[classes[i]];
						label=uk?(ui[row[2]]||row[0]):(ru?row[1]:row[0]);
						return false;
					}
				}
				if(label){return false;}
			});
			label=label||generic; $el.attr({'title':label,'aria-label':label});
		});
	}

	codecartNormalizeBreadcrumbCurrent(document);
	codecartPrepareIconButtonTitles(document);
	function codecartPrepareAlerts(context) {
		var $alerts = context ? $(context).find('.alert').addBack('.alert') : $('.alert');
		$alerts.each(function() {
			var $alert = $(this);
			if ($alert.attr('data-no-dismiss') === '1' || $alert.find('> .close[data-dismiss="alert"]').length) { return; }
			$alert.addClass('alert-dismissible').prepend('<button type="button" class="close" data-dismiss="alert" aria-label="Close" title="Close">&times;</button>');
		});
	}
	codecartPrepareAlerts(document);
	$(document).ajaxStop(function() { codecartPrepareAlerts(document); codecartNormalizeBreadcrumbCurrent(document); codecartPrepareIconButtonTitles(document); });
	// Passive AJAX activity indicator for existing admin requests.
	if (!document.getElementById('codecart-ajax-progress')) {
		$('body').append('<div id="codecart-ajax-progress" aria-hidden="true"></div>');
	}
	$(document).ajaxStart(function() {
		$('#codecart-ajax-progress').removeClass('is-done').addClass('is-active');
	}).ajaxStop(function() {
		var bar = $('#codecart-ajax-progress');
		bar.addClass('is-done');
		setTimeout(function() { bar.removeClass('is-active is-done'); }, 260);
	});

	//Form Submit for IE Browser
	$('button[type=\'submit\']').on('click', function() {
		$("form[id*='form-']").submit();
	});

	// Highlight any found errors
	$('.text-danger').each(function() {
		var element = $(this).parent().parent();

		if (element.hasClass('form-group')) {
			element.addClass('has-error');
		}
	});

	// tooltips on hover
	$('[data-toggle=\'tooltip\']').tooltip({container: 'body', html: true});

	// Makes tooltips work on ajax generated content
	$(document).ajaxStop(function() {
		$('[data-toggle=\'tooltip\']').tooltip({container: 'body'});
	});

	// https://github.com/opencart/opencart/issues/2595
	$.event.special.remove = {
		remove: function(o) {
			if (o.handler) {
				o.handler.apply(this, arguments);
			}
		}
	}
	
	// tooltip remove
	$('[data-toggle=\'tooltip\']').on('remove', function() {
		$(this).tooltip('destroy');
	});

	// Tooltip remove fixed
	$(document).on('click', '[data-toggle=\'tooltip\']', function(e) {
		$('body > .tooltip').remove();
	});
	
	$('#button-menu').on('click', function(e) {
		e.preventDefault();
		
		$('#column-left').toggleClass('active');
	});

	// Desktop compact sidebar. Keep exactly one top-level flyout open so old
	// submenus can never overlap the menu the user is currently working with.
	function closeCompactSidebarSiblings(keepSelector) {
		if (!$('#column-left').hasClass('codecart-compact')) {
			return;
		}

		$('#menu > li > ul.collapse.in').each(function() {
			var selector = '#' + this.id;

			if (!keepSelector || selector !== keepSelector) {
				$(this).removeClass('in').attr('aria-expanded', 'false');
				$(this).prev('a.parent').addClass('collapsed').attr('aria-expanded', 'false');
			}
		});
	}

	if (window.matchMedia && window.matchMedia('(min-width: 768px)').matches) {
		if (localStorage.getItem('codecart_admin_sidebar_compact') === '1') {
			$('#column-left').addClass('codecart-compact');
		}

		$('#navigation').on('click', function(e) {
			e.preventDefault();
			var compact = !$('#column-left').hasClass('codecart-compact');
			$('#column-left').toggleClass('codecart-compact', compact);
			localStorage.setItem('codecart_admin_sidebar_compact', compact ? '1' : '0');

			if (compact) {
				closeCompactSidebarSiblings('');
			}
		});

		$(document).on('click', '#column-left.codecart-compact #menu > li > a.parent', function() {
			var keepSelector = String($(this).attr('href') || '');

			if (keepSelector.charAt(0) === '#') {
				closeCompactSidebarSiblings(keepSelector);
			}
		});
	}

	// Set last page opened on the menu
	$('#menu a[href]').on('click', function() {
		sessionStorage.setItem('menu', $(this).attr('href'));
	});

	if (!sessionStorage.getItem('menu')) {
		$('#menu #dashboard').addClass('active');
	} else {
		// Sets active and open to selected page in the left column menu.
		$('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parent().addClass('active');
	}
	
	$('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('li > a').removeClass('collapsed');
	
	$('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('ul').addClass('in');
	
	$('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('li').addClass('active');

	if ($('#column-left').hasClass('codecart-compact')) {
		var $activeTop = $('#menu > li.active').first();
		var $keepCompact = $activeTop.children('ul.collapse').first();
		closeCompactSidebarSiblings($keepCompact.length ? ('#' + $keepCompact.attr('id')) : '');
	}
	
	// Image Manager
	$(document).on('click', 'a[data-toggle=\'image\']', function(e) {
		var $element = $(this);
		var $popover = $element.data('bs.popover'); // element has bs popover?

		e.preventDefault();

		// destroy all image popovers
		$('a[data-toggle="image"]').popover('destroy');

		// remove flickering (do not re-add popover when clicking for removal)
		if ($popover) {
			return;
		}

		$element.popover({
			html: true,
			sanitize: false,
			placement: 'right',
			trigger: 'manual',
			content: function() {
				return '<button type="button" id="button-image" class="btn btn-primary"><i class="fa fa-pencil"></i></button> <button type="button" id="button-clear" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>';
			}
		});

		$element.popover('show');

		setTimeout(function(){ // fix bind events on new popover when 

			$('#button-image').on('click', function() {
				var $button = $(this);
				var $icon   = $button.find('> i');

				$('#modal-image').remove();

				$.ajax({
					url: 'index.php?route=common/filemanager&user_token=' + getURLVar('user_token') + '&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id') + codecartFileManagerDirectoryParam(),
					dataType: 'html',
					beforeSend: function() {
						$button.prop('disabled', true);
						if ($icon.length) {
							$icon.attr('class', 'fa fa-circle-o-notch fa-spin');
						}
					},
					complete: function() {
						$button.prop('disabled', false);

						if ($icon.length) {
							$icon.attr('class', 'fa fa-pencil');
						}
					},
					success: function(html) {
						$('body').append('<div id="modal-image" class="modal">' + html + '</div>');

						$('#modal-image').modal('show');
					}
				});

				$element.popover('destroy');
			});

			$('#button-clear').on('click', function() {
				$element.find('img').attr('src', $element.find('img').attr('data-placeholder'));

				$element.parent().find('input').val('');

				$element.popover('destroy');
			});
			
		}, 250); // end timeout fix
			
	});
});

// Autocomplete */
(function($) {
	$.fn.autocomplete = function(option) {
		return this.each(function() {
			var $this = $(this);
			var $dropdown = $('<ul class="dropdown-menu" />');

			this.timer = null;
			this.items = [];

			$.extend(this, option);

			$this.attr('autocomplete', 'off');

			// Focus
			$this.on('focus', function() {
				this.request();
			});

			// Blur
			$this.on('blur', function() {
				setTimeout(function(object) {
					object.hide();
				}, 200, this);
			});

			// Keydown
			$this.on('keydown', function(event) {
				switch(event.keyCode) {
					case 27: // escape
						this.hide();
						break;
					default:
						this.request();
						break;
				}
			});

			// Click
			this.click = function(event) {
				event.preventDefault();

				var value = $(event.target).parent().attr('data-value');

				if (value && this.items[value]) {
					this.select(this.items[value]);
				}
			}

			// Show
			this.show = function() {
				var pos = $this.position();

				$dropdown.css({
					top: pos.top + $this.outerHeight(),
					left: pos.left
				});

				$dropdown.show();
			}

			// Hide
			this.hide = function() {
				$dropdown.hide();
			}

			// Request
			this.request = function() {
				clearTimeout(this.timer);

				this.timer = setTimeout(function(object) {
					object.source($(object).val(), $.proxy(object.response, object));
				}, 200, this);
			}

			// Response
			this.response = function(json) {
				var html = '';
				var category = {};
				var name;
				var i = 0, j = 0;

				if (json.length) {
					for (i = 0; i < json.length; i++) {
						// update element items
						this.items[json[i]['value']] = json[i];

						if (!json[i]['category']) {
							// ungrouped items
							html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
						} else {
							// grouped items
							name = json[i]['category'];
							if (!category[name]) {
								category[name] = [];
							}

							category[name].push(json[i]);
						}
					}

					for (name in category) {
						html += '<li class="dropdown-header">' + name + '</li>';

						for (j = 0; j < category[name].length; j++) {
							html += '<li data-value="' + category[name][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[name][j]['label'] + '</a></li>';
						}
					}
				}

				if (html) {
					this.show();
				} else {
					this.hide();
				}

				$dropdown.html(html);
			}

			$dropdown.on('click', '> li > a', $.proxy(this.click, this));
			$this.after($dropdown);
		});
	}
})(window.jQuery);


/* CodeCart PRO RC79 module display settings UX. */
(function($){
'use strict';
function syncCodeCartModuleDisplaySettings(scope){
  var $scope=$(scope||document),$mode=$scope.find('#input-display-mode').first();
  if(!$mode.length){return;}
  var carousel=$mode.val()==='carousel';
  $scope.find('.ccp-carousel-only').toggle(carousel);
  var autoplay=carousel&&String($scope.find('#input-autoplay').val()||'0')==='1';
  $scope.find('.ccp-autoplay-only').toggle(autoplay);
}
$(function(){syncCodeCartModuleDisplaySettings(document);});
$(document).on('change','#input-display-mode,#input-autoplay',function(){syncCodeCartModuleDisplaySettings($(this).closest('form'));});
})(window.jQuery);
