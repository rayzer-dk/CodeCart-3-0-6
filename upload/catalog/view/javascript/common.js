window.CodeCartUI = window.CodeCartUI || (function(){
	var lastToastKey = '', lastToastAt = 0;
	function host(){
		var el=document.getElementById('ccp-toast-host');
		if(!el){el=document.createElement('div');el.id='ccp-toast-host';el.setAttribute('aria-live','polite');el.setAttribute('aria-atomic','false');document.body.appendChild(el);}
		return el;
	}
	function toast(type,html){
		var normalized = $('<div>').html(String(html||'')).text().replace(/\s+/g,' ').trim();
		var toastKey = String(type||'info') + '|' + normalized;
		var now = Date.now();
		if (toastKey === lastToastKey && now - lastToastAt < 1400) { return; }
		lastToastKey = toastKey; lastToastAt = now;
		var el=document.createElement('div');
		el.className='ccp-toast ccp-toast-'+(type||'info');
		var icon=type==='success'?'fa-check-circle':(type==='danger'?'fa-exclamation-circle':(type==='warning'?'fa-exclamation-triangle':'fa-info-circle'));
		el.innerHTML='<i class="fa '+icon+'"></i><div class="ccp-toast-text">'+String(html||'')+'</div><button type="button" class="ccp-toast-close" aria-label="Close">&times;</button>';
		host().appendChild(el);
		requestAnimationFrame(function(){el.classList.add('is-visible');});
		var timer=setTimeout(function(){remove(el);},4200);
		el.querySelector('.ccp-toast-close').addEventListener('click',function(){clearTimeout(timer);remove(el);});
	}
	function remove(el){if(!el||!el.parentNode)return;el.classList.remove('is-visible');setTimeout(function(){if(el.parentNode)el.parentNode.removeChild(el);},180);}
	function error(xhr,thrown){var text=(thrown||'Error')+(xhr&&xhr.statusText?' '+xhr.statusText:'');toast('danger',$('<div>').text(text).html());}
	function convertAlerts(scope){
		var $scope=scope?$(scope):$(document);
		var $alerts=$scope.find('.alert-dismissible');
		if($scope.is&&$scope.is('.alert-dismissible')){$alerts=$alerts.add($scope);}
		$alerts.each(function(){
			var $alert=$(this);
			if ($alert.data('ccp-toast-converted') || $alert.closest('.modal,form,#accordion .panel-body').length) { return; }
			$alert.data('ccp-toast-converted',1);
			var clone=$alert.clone();clone.find('.close').remove();
			var html=$.trim(clone.html());
			var type=$alert.hasClass('alert-success')?'success':($alert.hasClass('alert-danger')?'danger':($alert.hasClass('alert-warning')?'warning':'info'));
			if(html){toast(type,html);}
			$alert.remove();
		});
	}
	return {toast:toast,error:error,convertAlerts:convertAlerts};
})();



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

$(document).ready(function() {
	// Breadcrumb invariant: the final item represents the current page and must
	// never navigate back to itself. Core templates already render it as a span;
	// this global guard also protects AJAX fragments and third-party templates.
	function normalizeBreadcrumbCurrent(scope) {
		var $scope = scope ? $(scope) : $(document);
		var $crumbs = $scope.find('.breadcrumb').add($scope.is && $scope.is('.breadcrumb') ? $scope : $());
		$crumbs.each(function() {
			var $last = $(this).children('li').last();
			if (!$last.length) { return; }
			var $link = $last.children('a').first();
			if ($link.length) {
				var $current = $('<span/>', {'aria-current':'page','class':'ccp-breadcrumb-current'}).html($link.html());
				$link.replaceWith($current);
			} else {
				$last.children('span').first().attr('aria-current','page').addClass('ccp-breadcrumb-current');
			}
		});
	}
	normalizeBreadcrumbCurrent(document);
	function prepareIconButtonTitles(scope){
		var lang=(document.documentElement.getAttribute('lang')||'en').toLowerCase(),uk=lang.indexOf('uk')===0,ru=lang.indexOf('ru')===0,ui=window.CodeCartI18n||{};
		var labels={
			'fa-calendar':['Select date','Выбрать дату','ui_select_date'],
			'fa-heart':['Wishlist','В закладки','ui_wishlist'],
			'fa-exchange':['Compare','Сравнить','ui_compare'],
			'fa-share-alt':['Share','Поделиться','ui_share'],
			'fa-search':['Search','Поиск','ui_search'],
			'fa-chevron-left':['Previous','Назад','ui_previous'],
			'fa-chevron-right':['Next','Далее','ui_next'],
			'fa-times':['Close','Закрыть','ui_close'],
			'fa-plus':['Increase','Увеличить','ui_increase'],
			'fa-minus':['Decrease','Уменьшить','ui_decrease'],
			'fa-upload':['Upload','Загрузить','ui_upload']
		};
		var generic=uk?(ui.ui_action||'Action'):(ru?'Действие':'Action'),$scope=scope?$(scope):$(document);
		$scope.find('button,a.btn,[role="button"]').addBack('button,a.btn,[role="button"]').each(function(){
			var $el=$(this);if($el.attr('title')||$el.attr('aria-label')||!$el.find('i.fa,i[class*="fa-"],span.fa,span[class*="fa-"]').length){return;}
			var $c=$el.clone();$c.find('i,svg,.fa,.fas,.far,.fab,.sr-only').remove();if($.trim($c.text())!==''){return;}
			var label='';$el.find('i,span').each(function(){var c=String(this.className||'').split(/\s+/);for(var i=0;i<c.length;i++){if(labels[c[i]]){var row=labels[c[i]];label=uk?(ui[row[2]]||row[0]):(ru?row[1]:row[0]);return false;}}if(label){return false;}});label=label||generic;$el.attr({'title':label,'aria-label':label});
		});
	}

	prepareIconButtonTitles(document);
	function syncThemeToggle(){
		var dark=document.documentElement.classList.contains('ccp-theme-dark');
		$('.ccp-theme-toggle').toggleClass('is-dark',dark).each(function(){
			$(this).find('.ccp-theme-fa').attr('class','fa '+(dark?'fa-sun':'fa-moon')+' ccp-theme-fa');
		});
	}
	$(document).on('click','.ccp-theme-toggle',function(){
		var dark=!document.documentElement.classList.contains('ccp-theme-dark');
		document.documentElement.classList.toggle('ccp-theme-dark',dark);
		try{localStorage.setItem('ccp-theme',dark?'dark':'light');}catch(e){}
		syncThemeToggle();
	});
	syncThemeToggle();

	function prepareClickableCards(scope){
		var $scope=scope?$(scope):$(document);
		var $cards=$scope.find('.product-thumb').add($scope.is&&$scope.is('.product-thumb')?$scope:$());
		$cards.each(function(){
			var $card=$(this), href=$card.find('h4 a[href]').first().attr('href')||$card.find('.image a[href]').first().attr('href');
			if(!href){return;}
			$card.attr({'data-ccp-card-href':href,'role':'link','tabindex':'0'}).addClass('ccp-clickable-card');
		});
	}
	prepareClickableCards(document);
	$(document).on('click','.ccp-clickable-card',function(e){
		if($(e.target).closest('a,button,input,select,textarea,label,[role=button]').length){return;}
		var href=$(this).attr('data-ccp-card-href'); if(href){window.location.href=href;}
	});
	$(document).on('keydown','.ccp-clickable-card',function(e){
		if((e.key==='Enter'||e.keyCode===13) && !$(e.target).is('a,button,input,select,textarea')){var href=$(this).attr('data-ccp-card-href');if(href){e.preventDefault();window.location.href=href;}}
	});

	// Convert page-level storefront flashes to compact popups without replacing
	// validation messages inside forms, checkout accordions or modal dialogs.
	if (window.CodeCartUI) {
		window.CodeCartUI.convertAlerts(document);
		if (window.MutationObserver && document.body) {
			new MutationObserver(function(mutations){
				mutations.forEach(function(m){ Array.prototype.forEach.call(m.addedNodes||[],function(node){ if(node&&node.nodeType===1){ window.CodeCartUI.convertAlerts(node); normalizeBreadcrumbCurrent(node); prepareIconButtonTitles(node); } }); });
			}).observe(document.body,{childList:true,subtree:true});
		}
	}

	// Lightweight AJAX activity indicator. It observes existing jQuery AJAX calls
	// without changing routes, payloads or extension callbacks.
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

	// Highlight any found errors
	$('.text-danger').each(function() {
		var element = $(this).parent().parent();

		if (element.hasClass('form-group')) {
			element.addClass('has-error');
		}
	});

	// Telephone fields accept digits, spaces, hyphens, parentheses and one leading "+"
	// (international format, e.g. +380...). Also covers fields injected by AJAX.
	function cleanPhoneValue(value) {
		value = String(value || '');
		var plus = /^\s*\+/.test(value);
		value = value.replace(/[^0-9()\-\s]/g, '');
		return plus ? '+' + value.replace(/^\s+/, '') : value;
	}
	function sanitizePhoneField(input) {
		var clean = cleanPhoneValue(input.value);
		if (input.value !== clean) { input.value = clean; }
	}
	$(document).on('focus', 'input[name="telephone"], input[name*="telephone"], input[type="tel"]', function() {
		$(this).attr({'inputmode':'tel','autocomplete':'tel','pattern':'\\+?[0-9()\\s-]*'});
	}).on('input paste', 'input[name="telephone"], input[name*="telephone"], input[type="tel"]', function() {
		var input = this; window.setTimeout(function(){ sanitizePhoneField(input); }, 0);
	}).on('keydown', 'input[name="telephone"], input[name*="telephone"], input[type="tel"]', function(e) {
		if (e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) { return; }
		if (!/[0-9()\-\s]/.test(e.key) && !(e.key === '+' && this.selectionStart === 0 && String(this.value || '').indexOf('+') === -1)) { e.preventDefault(); }
	});
	$('input[name="telephone"], input[name*="telephone"], input[type="tel"]').attr({'inputmode':'tel','autocomplete':'tel','pattern':'\\+?[0-9()\\s-]*'});

	// Currency
	$('#form-currency .currency-select').on('click', function(e) {
		e.preventDefault();

		$('#form-currency input[name=\'code\']').val($(this).attr('name'));

		$('#form-currency').submit();
	});

	// Language
	$('#form-language .language-select').on('click', function(e) {
		e.preventDefault();

		$('#form-language input[name=\'code\']').val($(this).attr('name'));

		$('#form-language').submit();
	});

	/* Search */
	$('#search input[name=\'search\']').parent().find('button').on('click', function() {
		var url = $('base').attr('href') + 'index.php?route=product/search';

		var value = $('header #search input[name=\'search\']').val();

		if (value) {
			url += '&search=' + encodeURIComponent(value);
		}

		location = url;
	});

	$('#search input[name=\'search\']').on('keydown', function(e) {
		if (e.keyCode == 13) {
			$('header #search input[name=\'search\']').parent().find('button').trigger('click');
		}
	});

	// Menu: calculate overflow only when a dropdown is actually opened.
	// Avoids layout reads for every hidden dropdown during the critical render path.
	$('#menu').on('shown.bs.dropdown', '.dropdown', function() {
		if (window.matchMedia && window.matchMedia('(max-width: 767px)').matches) { return; }
		var $menu = $('#menu');
		var $dropdown = $(this).children('.dropdown-menu').first();
		if (!$dropdown.length) { return; }
		var menuOffset = $menu.offset();
		var parentOffset = $(this).offset();
		var overflow = (parentOffset.left + $dropdown.outerWidth()) - (menuOffset.left + $menu.outerWidth());
		$dropdown.css('margin-left', overflow > 0 ? '-' + (overflow + 10) + 'px' : '');
	});


	// In-content hash links must stay on the current route even when <base href> points to the store root.
	// This is global for storefront content: products, categories, manufacturers, information pages and blog.
	function codeCartNormalizeContentAnchors(scope) {
		var $scope = scope ? $(scope) : $('#main-content');
		var $links = $scope.find('a[href^="#"]');
		if ($scope.is && $scope.is('a[href^="#"]')) { $links = $links.add($scope); }
		$links.each(function() {
			var $link = $(this);
			var raw = $link.attr('href') || '';
			if (raw.length < 2 || raw === '#') { return; }
			if ($link.is('[data-toggle],[data-target],[data-slide],[role="tab"]')) { return; }
			var id = raw.substring(1);
			if (!document.getElementById(id)) { return; }
			// Rewrite the actual href too, so middle-click/new-tab/status-bar all use the current page.
			$link.attr('href', window.location.pathname + window.location.search + '#' + encodeURIComponent(id));
		});
	}
	codeCartNormalizeContentAnchors($('#main-content'));
	$(document).ajaxComplete(function() { codeCartNormalizeContentAnchors($('#main-content')); });
	$(document).on('click', '#main-content a[href*="#"]', function(e) {
		var $link = $(this);
		if ($link.is('[data-toggle],[data-target],[data-slide],[role="tab"]')) { return; }
		var href = $link.attr('href') || '';
		var hashPos = href.indexOf('#');
		if (hashPos < 0 || hashPos === href.length - 1) { return; }
		var base = href.substring(0, hashPos);
		var current = window.location.pathname + window.location.search;
		if (base && base !== current) { return; }
		var id = decodeURIComponent(href.substring(hashPos + 1));
		var target = document.getElementById(id);
		if (!target) { return; }
		e.preventDefault();
		try { window.history.pushState(null, '', current + '#' + encodeURIComponent(id)); }
		catch (ignore) { window.location.hash = id; }
		if (target.scrollIntoView) { target.scrollIntoView({behavior: 'smooth', block: 'start'}); }
	});

	// Product Grid / List / Compact. One preference is shared by category/search/special/manufacturer product lists.
	function codeCartGetProductDisplay() {
		try { return window.localStorage.getItem('ccp_product_display_v2') || 'grid'; } catch (e) { return 'grid'; }
	}
	function codeCartSetProductDisplay(value) {
		try { window.localStorage.setItem('ccp_product_display_v2', value); } catch (e) {}
	}
	window.CodeCartApplyProductDisplay = function() {
		var preference = codeCartGetProductDisplay();
		if (['grid','list','compact'].indexOf(preference) === -1) { preference = 'grid'; }
		var $items = $('#content .ccp-product-grid[data-display-switcher="product"] > .product-layout');
		$items.each(function() {
			var $item = $(this);
			$item.removeClass('product-grid product-list product-compact col-xs-6 col-xs-12');
			if (preference === 'list') {
				$item.addClass('product-list col-xs-12');
			} else if (preference === 'compact') {
				$item.addClass('product-compact col-xs-12');
			} else {
				$item.addClass('product-grid col-xs-6');
			}
		});

		$('#list-view,#grid-view,#compact-view').removeClass('active').attr('aria-pressed','false');
		var buttonId = preference === 'list' ? '#list-view' : (preference === 'compact' ? '#compact-view' : '#grid-view');
		$(buttonId).addClass('active').attr('aria-pressed','true');
	};
	$(document).on('click', '#list-view', function() {
		codeCartSetProductDisplay('list');
		window.CodeCartApplyProductDisplay();
	});
	$(document).on('click', '#grid-view', function() {
		codeCartSetProductDisplay('grid');
		window.CodeCartApplyProductDisplay();
	});
	$(document).on('click', '#compact-view', function() {
		codeCartSetProductDisplay('compact');
		window.CodeCartApplyProductDisplay();
	});
	window.CodeCartApplyProductDisplay();

	// Blog Grid / List / Compact uses its own preference and never inherits product-card mode.
	function codeCartGetArticleDisplay() {
		try { return window.localStorage.getItem('ccp_article_display_v2') || 'grid'; } catch (e) { return 'grid'; }
	}
	function codeCartSetArticleDisplay(value) {
		try { window.localStorage.setItem('ccp_article_display_v2', value); } catch (e) {}
	}
	window.CodeCartApplyArticleDisplay = function() {
		var preference = codeCartGetArticleDisplay();
		if (['grid','list','compact'].indexOf(preference) === -1) { preference = 'grid'; }
		var $items = $('#content .ccp-article-grid[data-display-switcher="article"] > .product-layout');
		$items.each(function() {
			var $item = $(this);
			$item.removeClass('article-grid article-list article-compact col-xs-6 col-xs-12');
			if (preference === 'list') { $item.addClass('article-list col-xs-12'); }
			else if (preference === 'compact') { $item.addClass('article-compact col-xs-12'); }
			else { $item.addClass('article-grid col-xs-6'); }
		});
		$('#article-list-view,#article-grid-view,#article-compact-view').removeClass('active').attr('aria-pressed','false');
		var buttonId = preference === 'list' ? '#article-list-view' : (preference === 'compact' ? '#article-compact-view' : '#article-grid-view');
		$(buttonId).addClass('active').attr('aria-pressed','true');
	};
	$(document).on('click', '#article-list-view', function() { codeCartSetArticleDisplay('list'); window.CodeCartApplyArticleDisplay(); });
	$(document).on('click', '#article-grid-view', function() { codeCartSetArticleDisplay('grid'); window.CodeCartApplyArticleDisplay(); });
	$(document).on('click', '#article-compact-view', function() { codeCartSetArticleDisplay('compact'); window.CodeCartApplyArticleDisplay(); });
	window.CodeCartApplyArticleDisplay();

	// One rating controller for product reviews, article reviews and extension review forms.
	function codeCartPaintRating($picker, score, preview) {
		score = parseInt(score, 10) || 0;
		var className = preview ? 'is-preview' : 'is-selected';
		if (!preview) { $picker.attr('data-rating', score); }
		$picker.find('label[data-score]').each(function() {
			$(this).toggleClass(className, (parseInt($(this).attr('data-score'), 10) || 0) <= score);
		});
	}
	$(document).on('change', '.ccp-rating-picker input[name="rating"]', function() {
		codeCartPaintRating($(this).closest('.ccp-rating-picker'), this.value, false);
	});
	$(document).on('mouseenter', '.ccp-rating-picker label[data-score]', function() {
		codeCartPaintRating($(this).closest('.ccp-rating-picker'), $(this).attr('data-score'), true);
	}).on('mouseleave', '.ccp-rating-picker', function() {
		$(this).find('label').removeClass('is-preview');
	});
	$('.ccp-rating-picker').each(function() {
		var $picker = $(this), checked = $picker.find('input[name="rating"]:checked').val();
		codeCartPaintRating($picker, checked, false);
	});
	window.CodeCartReviewRatingReset = function(context) {
		var $scope = context ? $(context) : $(document);
		$scope.find('.ccp-rating-picker').each(function() {
			$(this).find('input[name="rating"]:checked').prop('checked', false);
			$(this).find('label').removeClass('is-preview');
			codeCartPaintRating($(this), 0, false);
		});
	};

	// AJAX cart quantity update: keeps totals and header cart in sync without page jumps.
	$(document).on('click', '.ccp-cart-qty-update', function(e) {
		e.preventDefault();
		var key = $(this).attr('data-cart-id') || '';
		var $input = $('input.ccp-cart-qty[data-cart-id="' + key.replace(/"/g, '\\"') + '"]').first();
		if (key && $input.length) { cart.update(key, Math.max(0, parseInt($input.val(), 10) || 0)); }
	});
	$(document).on('keydown', 'input.ccp-cart-qty', function(e) {
		if (e.key === 'Enter' || e.keyCode === 13) { e.preventDefault(); $('.ccp-cart-qty-update[data-cart-id="' + String($(this).attr('data-cart-id')).replace(/"/g, '\\"') + '"]').first().trigger('click'); }
	});

	// Checkout
	$(document).on('keydown', '#collapse-checkout-option input[name=\'email\'], #collapse-checkout-option input[name=\'password\']', function(e) {
		if (e.keyCode == 13) {
			$('#collapse-checkout-option #button-login').trigger('click');
		}
	});

	// tooltips on hover
	$('[data-toggle=\'tooltip\']').tooltip({container: 'body'});

	// Makes tooltips work on ajax generated content
	$(document).ajaxStop(function() {
		$('[data-toggle=\'tooltip\']').tooltip({container: 'body'});
		if (window.CodeCartUI) { window.CodeCartUI.convertAlerts(document); }
		prepareClickableCards(document);
	});
});

// CodeCart PRO default-theme cart state: after a successful add, change the
// relevant button to "In Cart". A second click opens the cart instead of
// silently adding the same product again. Product-page option/quantity changes
// reset its button so another configuration can intentionally be added.
window.CodeCartCartUI = window.CodeCartCartUI || (function($) {
	function label() { return $('body').attr('data-ccp-in-cart') || 'In Cart'; }
	function cartUrl() { return $('body').attr('data-ccp-cart-url') || 'index.php?route=checkout/cart'; }
	function endpoint(name, fallback) { return $('body').attr('data-ccp-' + name + '-url') || fallback; }
	function remember($button) {
		if (typeof $button.data('ccp-original-html') === 'undefined') {
			$button.data('ccp-original-html', $button.is('input') ? $button.val() : $button.html());
		}
	}
	function markButton(button) {
		var $button = $(button);
		if (!$button.length) { return; }
		remember($button);
		$button.addClass('is-in-cart').attr('title', label()).attr('aria-label', label());
		if ($button.is('input')) {
			$button.val(label());
		} else {
			$button.html('<i class="fa fa-check" aria-hidden="true"></i> <span class="ccp-cart-state-label"></span>');
			$button.find('.ccp-cart-state-label').text(label());
		}
	}
	function markProduct(productId) {
		$('.ccp-cart-add[data-product-id="' + parseInt(productId, 10) + '"]').each(function() { markButton(this); });
	}
	function resetButton(button) {
		var $button = $(button), original = $button.data('ccp-original-html');
		if (typeof original === 'undefined') { return; }
		$button.removeClass('is-in-cart').removeAttr('title aria-label');
		if ($button.is('input')) { $button.val(original); } else { $button.html(original); }
	}
	function updateTotal(total) {
		if (typeof total === 'undefined' || total === null) { return; }
		var $total = $('#cart-total');
		if ($total.length) { $total.text(String(total)); }
		else { $('#cart > .ccp-cart-trigger').html('<i class="fa fa-shopping-cart"></i> <span id="cart-total"></span>').find('#cart-total').text(String(total)); }
	}
	function reloadDropdown() {
		$('#cart > ul').load(endpoint('cart-info', 'index.php?route=common/cart/info') + ' ul li');
	}
	function refreshHeaderCart() {
		$.ajax({
			url: endpoint('cart-info', 'index.php?route=common/cart/info'),
			dataType: 'html',
			cache: false,
			success: function(html) {
				var $parsed = $('<div></div>').append($.parseHTML(html, document, true));
				var $newCart = $parsed.find('#cart').first();
				if (!$newCart.length) { return; }
				var total = $.trim($newCart.find('#cart-total').text());
				if (total) { updateTotal(total); }
				var $newList = $newCart.children('ul').first();
				if ($newList.length && $('#cart > ul').length) { $('#cart > ul').html($newList.html()); }
			}
		});
	}
	function refreshCartPage() {
		var $content = $('#checkout-cart #content');
		if (!$content.length) { return; }
		var url = window.location.href.split('#')[0];
		$content.addClass('is-refreshing').load(url + ' #content > *', function(){
			$content.removeClass('is-refreshing');
			if ($.fn.tooltip) { $('[data-toggle=\'tooltip\']').tooltip({container:'body'}); }
		});
	}
	return {markButton: markButton, markProduct: markProduct, resetButton: resetButton, cartUrl: cartUrl, endpoint: endpoint, updateTotal: updateTotal, reloadDropdown: reloadDropdown, refreshHeaderCart: refreshHeaderCart, refreshCartPage: refreshCartPage};
})(jQuery);


// Modern standard-checkout mini-cart drawer. Bootstrap dropdown still owns the
// open/close state, so legacy integrations that trigger #cart remain compatible.
$(document)
	.on('shown.bs.dropdown', '#cart.ccp-cart-drawer-mode', function() {
		$('body').addClass('ccp-cart-drawer-open');
		$(this).children('.ccp-cart-trigger').attr('aria-expanded', 'true');
	})
	.on('hidden.bs.dropdown', '#cart.ccp-cart-drawer-mode', function() {
		$('body').removeClass('ccp-cart-drawer-open');
		$(this).children('.ccp-cart-trigger').attr('aria-expanded', 'false');
	})
	.on('click', '#cart.ccp-cart-drawer-mode .ccp-mini-cart-qty-btn', function(event) {
		event.preventDefault();
		event.stopPropagation();
		var key = $(this).attr('data-cart-id');
		var quantity = parseInt($(this).attr('data-quantity'), 10);
		if (!key || !quantity || quantity < 1) { return; }
		cart.update(key, quantity);
	})
	.on('click', '#cart.ccp-cart-drawer-mode .ccp-cart-drawer-close, #cart.ccp-cart-drawer-mode .ccp-cart-drawer-backdrop', function(event) {
		event.preventDefault();
		event.stopPropagation();
		var $cart = $('#cart.ccp-cart-drawer-mode');
		$cart.removeClass('open');
		$cart.children('.ccp-cart-trigger').attr('aria-expanded', 'false').focus();
		$('body').removeClass('ccp-cart-drawer-open');
	});

// Cart add remove functions
var cart = {
	'add': function(product_id, quantity) {
		var $known = $('.ccp-cart-add[data-product-id="' + parseInt(product_id, 10) + '"]').filter('.is-in-cart');
		if ($known.length && window.CodeCartCartUI) {
			window.location.href = window.CodeCartCartUI.cartUrl();
			return;
		}
		$.ajax({
			url: window.CodeCartCartUI ? window.CodeCartCartUI.endpoint('cart-add', 'index.php?route=checkout/cart/add') : 'index.php?route=checkout/cart/add',
			type: 'post',
			data: 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
			dataType: 'json',
			beforeSend: function() {
				$('#cart > .ccp-cart-trigger').addClass('is-loading').attr('aria-busy', 'true');
			},
			complete: function() {
				// Do not use Bootstrap .button('loading') here: its reset restores the
				// pre-AJAX HTML and overwrites the freshly updated cart total.
				$('#cart > .ccp-cart-trigger').removeClass('is-loading').removeAttr('aria-busy');
			},
			success: function(json) {
				$('.alert-dismissible, .text-danger').remove();

				if (json['redirect']) {
					location = json['redirect'];
				}

				if (json['success']) {
					if (window.CodeCartCartUI) { window.CodeCartCartUI.markProduct(product_id); }
					window.CodeCartUI.toast('success', json['success']);
				if (window.CodeCartCartUI) { window.CodeCartCartUI.updateTotal(json['total']); window.CodeCartCartUI.refreshHeaderCart(); }


					if (window.CodeCartCartUI) { window.CodeCartCartUI.reloadDropdown(); } else { $('#cart > ul').load('index.php?route=common/cart/info ul li'); }
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				window.CodeCartUI.error(xhr, thrownError);
			}
		});
	},
	'update': function(key, quantity) {
		$.ajax({
			url: window.CodeCartCartUI ? window.CodeCartCartUI.endpoint('cart-edit', 'index.php?route=checkout/cart/edit') : 'index.php?route=checkout/cart/edit',
			type: 'post',
			data: 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
			dataType: 'json',
			beforeSend: function() {
				$('#cart > .ccp-cart-trigger').addClass('is-loading').attr('aria-busy', 'true');
			},
			complete: function() {
				// Do not use Bootstrap .button('loading') here: its reset restores the
				// pre-AJAX HTML and overwrites the freshly updated cart total.
				$('#cart > .ccp-cart-trigger').removeClass('is-loading').removeAttr('aria-busy');
			},
			success: function(json) {
				if (json['success'] && window.CodeCartUI) { window.CodeCartUI.toast('success', json['success']); }
				if (window.CodeCartCartUI) {
					window.CodeCartCartUI.updateTotal(json['total']);
					window.CodeCartCartUI.reloadDropdown();
					if ($('#checkout-cart').length) { window.CodeCartCartUI.refreshCartPage(); }
				}
				$(document).trigger('codecart:cart-updated', [json]);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				window.CodeCartUI.error(xhr, thrownError);
			}
		});
	},
	'remove': function(key) {
		$.ajax({
			url: window.CodeCartCartUI ? window.CodeCartCartUI.endpoint('cart-remove', 'index.php?route=checkout/cart/remove') : 'index.php?route=checkout/cart/remove',
			type: 'post',
			data: 'key=' + key,
			dataType: 'json',
			beforeSend: function() {
				$('#cart > .ccp-cart-trigger').addClass('is-loading').attr('aria-busy', 'true');
			},
			complete: function() {
				// Do not use Bootstrap .button('loading') here: its reset restores the
				// pre-AJAX HTML and overwrites the freshly updated cart total.
				$('#cart > .ccp-cart-trigger').removeClass('is-loading').removeAttr('aria-busy');
			},
			success: function(json) {
				if (json['success'] && window.CodeCartUI) { window.CodeCartUI.toast('success', json['success']); }
				if (window.CodeCartCartUI) {
					window.CodeCartCartUI.updateTotal(json['total']);
					window.CodeCartCartUI.reloadDropdown();
					if ($('#checkout-cart').length) { window.CodeCartCartUI.refreshCartPage(); }
				}
				$(document).trigger('codecart:cart-updated', [json]);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				window.CodeCartUI.error(xhr, thrownError);
			}
		});
	}
}

var voucher = {
	'add': function() {

	},
	'remove': function(key) {
		$.ajax({
			url: window.CodeCartCartUI ? window.CodeCartCartUI.endpoint('cart-remove', 'index.php?route=checkout/cart/remove') : 'index.php?route=checkout/cart/remove',
			type: 'post',
			data: 'key=' + key,
			dataType: 'json',
			beforeSend: function() {
				$('#cart > .ccp-cart-trigger').addClass('is-loading').attr('aria-busy', 'true');
			},
			complete: function() {
				// Do not use Bootstrap .button('loading') here: its reset restores the
				// pre-AJAX HTML and overwrites the freshly updated cart total.
				$('#cart > .ccp-cart-trigger').removeClass('is-loading').removeAttr('aria-busy');
			},
			success: function(json) {
				if (json['success'] && window.CodeCartUI) { window.CodeCartUI.toast('success', json['success']); }
				if (window.CodeCartCartUI) {
					window.CodeCartCartUI.updateTotal(json['total']);
					window.CodeCartCartUI.reloadDropdown();
					if ($('#checkout-cart').length) { window.CodeCartCartUI.refreshCartPage(); }
				}
				$(document).trigger('codecart:cart-updated', [json]);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				window.CodeCartUI.error(xhr, thrownError);
			}
		});
	}
}

var wishlist = {
	'add': function(product_id) {
		$.ajax({
			url: window.CodeCartCartUI ? window.CodeCartCartUI.endpoint('wishlist-add', 'index.php?route=account/wishlist/add') : 'index.php?route=account/wishlist/add',
			type: 'post',
			data: 'product_id=' + product_id,
			dataType: 'json',
			success: function(json) {
				$('.alert-dismissible').remove();

				if (json['redirect']) {
					location = json['redirect'];
				}

				if (json['success']) {
					window.CodeCartUI.toast('success', json['success']);
				}

				$('#wishlist-total span').html(json['total']);
				$('#wishlist-total').attr('title', json['total']);

			},
			error: function(xhr, ajaxOptions, thrownError) {
				window.CodeCartUI.error(xhr, thrownError);
			}
		});
	},
	'remove': function() {

	}
}

var compare = {
	'add': function(product_id) {
		$.ajax({
			url: window.CodeCartCartUI ? window.CodeCartCartUI.endpoint('compare-add', 'index.php?route=product/compare/add') : 'index.php?route=product/compare/add',
			type: 'post',
			data: 'product_id=' + product_id,
			dataType: 'json',
			success: function(json) {
				$('.alert-dismissible').remove();

				if (json['success']) {
					window.CodeCartUI.toast('success', json['success']);

					$('#compare-total').html(json['total']);

				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				window.CodeCartUI.error(xhr, thrownError);
			}
		});
	},
	'remove': function() {

	}
}

/* Agree to Terms */
$(document).delegate('.agree', 'click', function(e) {
	e.preventDefault();

	$('#modal-agree').remove();

	var element = this;

	$.ajax({
		url: $(element).attr('href'),
		type: 'get',
		dataType: 'html',
		success: function(data) {
			html  = '<div id="modal-agree" class="modal">';
			html += '  <div class="modal-dialog">';
			html += '    <div class="modal-content">';
			html += '      <div class="modal-header">';
			html += '        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
			html += '        <h4 class="modal-title">' + $(element).text() + '</h4>';
			html += '      </div>';
			html += '      <div class="modal-body">' + data + '</div>';
			html += '    </div>';
			html += '  </div>';
			html += '</div>';

			$('body').append(html);

			$('#modal-agree').modal('show');
		}
	});
});

// Autocomplete */
(function($) {
	$.fn.autocomplete = function(option) {
		return this.each(function() {
			this.timer = null;
			this.items = new Array();

			$.extend(this, option);

			$(this).attr('autocomplete', 'off');

			// Focus
			$(this).on('focus', function() {
				this.request();
			});

			// Blur
			$(this).on('blur', function() {
				setTimeout(function(object) {
					object.hide();
				}, 200, this);
			});

			// Keydown
			$(this).on('keydown', function(event) {
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

				value = $(event.target).parent().attr('data-value');

				if (value && this.items[value]) {
					this.select(this.items[value]);
				}
			}

			// Show
			this.show = function() {
				var pos = $(this).position();

				$(this).siblings('ul.dropdown-menu').css({
					top: pos.top + $(this).outerHeight(),
					left: pos.left
				});

				$(this).siblings('ul.dropdown-menu').show();
			}

			// Hide
			this.hide = function() {
				$(this).siblings('ul.dropdown-menu').hide();
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
				html = '';

				if (json.length) {
					for (i = 0; i < json.length; i++) {
						this.items[json[i]['value']] = json[i];
					}

					for (i = 0; i < json.length; i++) {
						if (!json[i]['category']) {
							html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
						}
					}

					// Get all the ones with a categories
					var category = new Array();

					for (i = 0; i < json.length; i++) {
						if (json[i]['category']) {
							if (!category[json[i]['category']]) {
								category[json[i]['category']] = new Array();
								category[json[i]['category']]['name'] = json[i]['category'];
								category[json[i]['category']]['item'] = new Array();
							}

							category[json[i]['category']]['item'].push(json[i]);
						}
					}

					for (i in category) {
						html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

						for (j = 0; j < category[i]['item'].length; j++) {
							html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
						}
					}
				}

				if (html) {
					this.show();
				} else {
					this.hide();
				}

				$(this).siblings('ul.dropdown-menu').html(html);
			}

			$(this).after('<ul class="dropdown-menu"></ul>');
			$(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

		});
	}
})(window.jQuery);



// CodeCart PRO mobile footer accordion. Uses delegated native events so it also works after AJAX/theme scripts.
(function(){
    document.addEventListener('click', function(event){
        var button = event.target.closest ? event.target.closest('.ccp-footer-toggle') : null;
        if (!button) return;
        if (window.matchMedia && !window.matchMedia('(max-width: 767px)').matches) return;
        event.preventDefault();
        var section = button.closest('.ccp-footer-section');
        if (!section) return;
        var open = section.classList.contains('is-open');
        section.classList.toggle('is-open', !open);
        button.setAttribute('aria-expanded', open ? 'false' : 'true');
    }, false);
})();


/* CodeCart PRO telephone input guard: one leading "+", digits, spaces, hyphens and parentheses. */
(function($){
'use strict';
var allowed=/^\+?[0-9()\s-]*$/;
function clean(v){v=String(v||'');var plus=/^\s*\+/.test(v);v=v.replace(/[^0-9()\s-]/g,'');return plus?'+'+v.replace(/^\s+/,''):v;}
$(document).on('focus','input[type="tel"],input[name*="telephone"]',function(){$(this).attr({'inputmode':'tel','autocomplete':'tel','pattern':'\\+?[0-9()\\s-]*'});});
$(document).on('input','input[type="tel"],input[name*="telephone"]',function(){var v=String(this.value||'');if(!allowed.test(v)){this.value=clean(v);}});
$(document).on('paste','input[type="tel"],input[name*="telephone"]',function(e){var t=(e.originalEvent||e).clipboardData;if(t){var v=t.getData('text');if(!allowed.test(v)){e.preventDefault();document.execCommand('insertText',false,clean(v));}}});
})(window.jQuery);


