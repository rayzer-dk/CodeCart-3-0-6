<?php
// OpenCart 3.0.x lines that modules search for => array(CodeCart line for the same place, usable for position="replace").
// The second value is false when the CodeCart text is only the beginning of a longer line.
// Storefront templates are keyed once for every theme: catalog/view/theme/*/template/...
return array(
	'catalog/controller/common/header.php' => array(
		'$data[\'logo\'] = $server . \'image/\' . $this->config->get(\'config_logo\');' => array('$data[\'logo\'] = $server . \'image/\' . $logo_name;', true),
		'$data[\'shopping_cart\'] = $this->url->link(\'checkout/cart\');' => array('$data[\'shopping_cart\'] = $quick_cart_link ?', false),
	),
	'catalog/controller/common/footer.php' => array(
		'return $this->load->view(\'common/footer\', $data);' => array('$output = $this->load->view(\'common/footer\', $data);', false),
	),
	'catalog/controller/product/product.php' => array(
		'$this->document->setDescription($product_info[\'meta_description\']);' => array('$this->document->setDescription($meta_description);', false),
		'$data[\'price\'] = $this->currency->format($this->tax->calculate($product_info[\'price\'], $product_info[\'tax_class_id\'], $this->config->get(\'config_tax\')), $this->session->data[\'currency\']);' => array('$data[\'price\'] = \\CodeCart\\Core\\TaxDisplay::primary($this->registry, $product_info[\'price\'],', false),
		'$data[\'special\'] = $this->currency->format($this->tax->calculate($product_info[\'special\'], $product_info[\'tax_class_id\'], $this->config->get(\'config_tax\')), $this->session->data[\'currency\']);' => array('$data[\'special\'] = \\CodeCart\\Core\\TaxDisplay::primary($this->registry, $product_info[\'special\'],', false),
		'$this->document->addScript(\'catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js\');' => array('$this->document->addScript(\'catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js', false),
		'$this->model_catalog_review->addReview($this->request->get[\'product_id\'], $this->request->post);' => array('$this->model_catalog_review->addReview($product_id, $this->request->post);', true),
	),
	'catalog/controller/product/category.php' => array(
		'\'product_id\'  => $result[\'product_id\'],' => array('\'product_id\'   => $result[\'product_id\'],', true),
	),
	'catalog/model/checkout/order.php' => array(
		'public function addOrderHistory($order_id, $order_status_id, $comment = \'\', $notify = false, $override = false) {' => array('public function addOrderHistory($order_id, $order_status_id, $comment = \'\', $notify = false, $override = false, $idempotency_context = array()) {', false),
	),
	'admin/view/template/common/header.twig' => array(
		'<script type="text/javascript" src="view/javascript/jquery/jquery-2.1.1.min.js"></script>' => array('<script type="text/javascript" src="view/javascript/jquery/jquery-3.7.1.min.js"></script>', true),
		'<script src="view/javascript/common.js" type="text/javascript"></script>' => array('<script src="view/javascript/common.js?v={{ package_build }}" type="text/javascript"></script>', true),
		'<link type="text/css" href="view/stylesheet/stylesheet.css" rel="stylesheet" media="screen" />' => array('<link type="text/css" href="view/stylesheet/stylesheet.css?v={{ package_build }}" rel="stylesheet" media="screen" />', true),
	),
	'admin/view/template/setting/setting.twig' => array(
		'<li><a href="#tab-server" data-toggle="tab">{{ tab_server }}</a></li>' => array('<li><a href="#tab-server" data-toggle="tab"><i class="fa fa-server"></i> {{ tab_server }}</a></li>', true),
	),
	'catalog/view/theme/*/template/common/header.twig' => array(
		'<title>{{ title }}</title>' => array('<title>{{ title_text|e }}</title>', true),
		'<base href="{{ base }}" />' => array('<base href="{{ base|e }}" />', true),
		'<body>' => array('<body data-ccp-cart-url=', false),
		'<script src="catalog/view/javascript/jquery/jquery-2.1.1.min.js" type="text/javascript"></script>' => array('<script src="catalog/view/javascript/jquery/jquery-3.7.1.min.js" type="text/javascript"></script>', true),
		'<script src="catalog/view/javascript/common.js" type="text/javascript"></script>' => array('<script src="catalog/view/javascript/common.js?v=', false),
		'<link href="catalog/view/theme/default/stylesheet/stylesheet.css" rel="stylesheet">' => array('stylesheet/stylesheet.css?v=', false),
	),
	'catalog/view/theme/*/template/common/footer.twig' => array(
		'<footer>' => array('<footer class="ccp-footer">', true),
	),
	'catalog/view/theme/*/template/common/cart.twig' => array(
		'<div id="cart" class="btn-group btn-block">' => array('<div id="cart" class="btn-group btn-block', false),
	),
	'catalog/view/theme/*/template/common/menu.twig' => array(
		'<ul class="nav navbar-nav">' => array('<ul class="nav navbar-nav ccp-horizontal-tabs">', true),
		'{% if categories %}' => array('{% if categories or quick_links %}', false),
	),
	'catalog/view/theme/*/template/checkout/cart.twig' => array(
		'<form action="{{ action }}" method="post" enctype="multipart/form-data">' => array('<form action="{{ action|e(\'html_attr\') }}" method="post" enctype="multipart/form-data">', true),
	),
	'catalog/view/theme/*/template/product/category.twig' => array(
		'<h2>{{ heading_title }}</h2>' => array('<h1>{{ heading_title }}</h1>', true),
		'<div class="product-layout product-list col-xs-12">' => array('<div class="product-layout product-grid col-xs-6">', true),
	),
	'catalog/view/theme/*/template/product/product.twig' => array(
		'<div id="product">' => array('<div id="product" class="ccp-product-order">', true),
		'<button type="button" id="button-cart" data-loading-text="{{ text_loading }}" class="btn btn-primary btn-lg btn-block">{{ button_cart }}</button>' => array('<button type="button" id="button-cart"', false),
		'<input type="text" name="quantity" value="{{ minimum }}" size="2" id="input-quantity" class="form-control" />' => array('<input type="text" name="quantity" value="{{ minimum }}" size="2" id="input-quantity" class="form-control text-center" inputmode="numeric" pattern="[0-9]*" />', true),
		'url: \'index.php?route=checkout/cart/add\',' => array('url: (window.CodeCartCartUI ? window.CodeCartCartUI.endpoint(\'cart-add\', \'index.php?route=checkout/cart/add\') : \'index.php?route=checkout/cart/add\'),', true),
	),
	'catalog/view/theme/*/template/extension/module/featured.twig' => array(
		'<div class="product-layout col-lg-3 col-md-3 col-sm-6 col-xs-12">' => array('<div class="product-layout ccp-native-module-item">', true),
	),
);
