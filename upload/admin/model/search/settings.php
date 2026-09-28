<?php
// CodeCart PRO: lightweight, pre-generated admin settings search index.
// Runtime search never scans Twig/PHP files or database metadata.

class ModelSearchSettings extends Model {
    public function getCoreEntries() {
        return array(
            array('label' => 'entry_meta_title', 'key' => 'config_meta_title', 'focus' => 'input-meta-title', 'tab' => 'general', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_meta_description', 'key' => 'config_meta_description', 'focus' => 'input-meta-description', 'tab' => 'general', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_meta_keyword', 'key' => 'config_meta_keyword', 'focus' => 'input-meta-keyword', 'tab' => 'general', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_theme', 'key' => 'config_theme', 'focus' => 'input-theme', 'tab' => 'general', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_layout', 'key' => 'config_layout_id', 'focus' => 'input-layout', 'tab' => 'general', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_name', 'key' => 'config_name', 'focus' => 'input-name', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_owner', 'key' => 'config_owner', 'focus' => 'input-owner', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_address', 'key' => 'config_address', 'focus' => 'input-address', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_geocode', 'key' => 'config_geocode', 'focus' => 'input-geocode', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_map_url', 'key' => 'config_map_url', 'focus' => 'input-map-url', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_email', 'key' => 'config_email', 'focus' => 'input-email', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_telephone', 'key' => 'config_telephone', 'focus' => 'input-telephone', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_fax', 'key' => 'config_fax', 'focus' => 'input-fax', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_image', 'key' => 'config_image', 'focus' => 'input-image', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_open', 'key' => 'config_open', 'focus' => 'input-open', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_comment', 'key' => 'config_comment', 'focus' => 'input-comment', 'tab' => 'store', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_country', 'key' => 'config_country_id', 'focus' => 'input-country', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_zone', 'key' => 'config_zone_id', 'focus' => 'input-zone', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_timezone', 'key' => 'config_timezone', 'focus' => 'input-timezone', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_language', 'key' => 'config_language', 'focus' => 'input-language', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_admin_language', 'key' => 'config_admin_language', 'focus' => 'input-admin-language', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_currency', 'key' => 'config_currency', 'focus' => 'input-currency', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_currency_engine', 'key' => 'config_currency_engine', 'focus' => 'input-currency-engine', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_length_class', 'key' => 'config_length_class_id', 'focus' => 'input-length-class', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_weight_class', 'key' => 'config_weight_class_id', 'focus' => 'input-weight-class', 'tab' => 'local', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_cookie_consent', 'key' => 'config_cookie_consent_status', 'focus' => 'input-cookie-consent', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_cookie_days', 'key' => 'config_cookie_consent_days', 'focus' => 'input-cookie-days', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_cookie_information', 'key' => 'config_cookie_consent_information_id', 'focus' => 'input-cookie-info', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_cookie_privacy_information', 'key' => 'config_cookie_consent_privacy_information_id', 'focus' => 'input-cookie-privacy-info', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_cookie_accent', 'key' => 'config_cookie_consent_accent_color', 'focus' => 'input-cookie-accent-picker', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_cookie_icon', 'key' => 'config_cookie_consent_icon', 'focus' => 'input-cookie-icon', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_cookie_custom_icon', 'key' => 'config_cookie_consent_custom_icon', 'focus' => 'input-cookie-custom-icon', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_limit_admin', 'key' => 'config_limit_admin', 'focus' => 'input-admin-limit', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_limit_autocomplete', 'key' => 'config_limit_autocomplete', 'focus' => 'input-autocomplete-limit', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_voucher_min', 'key' => 'config_voucher_min', 'focus' => 'input-voucher-min', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_voucher_max', 'key' => 'config_voucher_max', 'focus' => 'input-voucher-max', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_tax_display', 'key' => 'config_tax_display', 'focus' => 'input-tax-display', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_currency_trim_zeros', 'key' => 'config_currency_trim_zeros', 'focus' => 'input-currency-trim-zeros', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_tax_default', 'key' => 'config_tax_default', 'focus' => 'input-tax-default', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_tax_customer', 'key' => 'config_tax_customer', 'focus' => 'input-tax-customer', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_customer_group', 'key' => 'config_customer_group_id', 'focus' => 'input-customer-group', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_login_attempts', 'key' => 'config_login_attempts', 'focus' => 'input-login-attempts', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_account', 'key' => 'config_account_id', 'focus' => 'input-account', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_invoice_prefix', 'key' => 'config_invoice_prefix', 'focus' => 'input-invoice-prefix', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_checkout_fields', 'key' => 'config_quick_checkout_status', 'focus' => 'input-checkout-email-fallback', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_checkout', 'key' => 'config_checkout_id', 'focus' => 'input-checkout', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_order_status', 'key' => 'config_order_status_id', 'focus' => 'input-order-status', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_fraud_status', 'key' => 'config_fraud_status_id', 'focus' => 'input-fraud-status', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_api', 'key' => 'config_api_id', 'focus' => 'input-api', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_affiliate_group', 'key' => 'config_affiliate_group_id', 'focus' => 'input-affiliate-group', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_affiliate_commission', 'key' => 'config_affiliate_commission', 'focus' => 'input-affiliate-commission', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_affiliate', 'key' => 'config_affiliate_id', 'focus' => 'input-affiliate', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_return', 'key' => 'config_return_id', 'focus' => 'input-return', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_return_status', 'key' => 'config_return_status_id', 'focus' => 'input-return-status', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_captcha', 'key' => 'config_captcha', 'focus' => 'input-captcha', 'tab' => 'option', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_logo', 'key' => 'config_logo', 'focus' => 'input-logo', 'tab' => 'image', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_icon', 'key' => 'config_icon', 'focus' => 'input-icon', 'tab' => 'image', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_email_logo', 'key' => 'config_email_logo', 'focus' => 'input-email-logo', 'tab' => 'image', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_apple_touch_icon', 'key' => 'config_apple_touch_icon', 'focus' => 'input-apple-touch-icon', 'tab' => 'image', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_social_preview_image', 'key' => 'config_social_preview_image', 'focus' => 'input-social-preview-image', 'tab' => 'image', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_catalog_fallback_image', 'key' => 'config_catalog_fallback_image', 'focus' => 'input-catalog-fallback-image', 'tab' => 'image', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_image_webp_quality', 'key' => 'config_image_webp_quality', 'focus' => 'input-image-webp-quality', 'tab' => 'image', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_mail_engine', 'key' => 'config_mail_engine', 'focus' => 'input-mail-engine', 'tab' => 'mail', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_mail_parameter', 'key' => 'config_mail_parameter', 'focus' => 'input-mail-parameter', 'tab' => 'mail', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_mail_smtp_hostname', 'key' => 'config_mail_smtp_hostname', 'focus' => 'input-mail-smtp-hostname', 'tab' => 'mail', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_mail_smtp_username', 'key' => 'config_mail_smtp_username', 'focus' => 'input-mail-smtp-username', 'tab' => 'mail', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_mail_smtp_password', 'key' => 'config_mail_smtp_password', 'focus' => 'input-mail-smtp-password', 'tab' => 'mail', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_mail_smtp_port', 'key' => 'config_mail_smtp_port', 'focus' => 'input-mail-smtp-port', 'tab' => 'mail', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_mail_smtp_timeout', 'key' => 'config_mail_smtp_timeout', 'focus' => 'input-mail-smtp-timeout', 'tab' => 'mail', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_mail_alert_email', 'key' => 'config_mail_alert_email', 'focus' => 'input-alert-email', 'tab' => 'mail', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_admin_accent_color', 'key' => 'config_admin_accent_color', 'focus' => 'input-admin-accent-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_admin_sidebar_color', 'key' => 'config_admin_sidebar_color', 'focus' => 'input-admin-sidebar-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_admin_submenu_color', 'key' => 'config_admin_submenu_color', 'focus' => 'input-admin-submenu-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_admin_surface_color', 'key' => 'config_admin_surface_color', 'focus' => 'input-admin-surface-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_accent_color', 'key' => 'theme_codecart_accent_color', 'focus' => 'input-storefront-accent-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_accent_hover', 'key' => 'theme_codecart_accent_hover', 'focus' => 'input-storefront-hover-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_button_color', 'key' => 'theme_codecart_button_color', 'focus' => 'input-storefront-button-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_button_hover', 'key' => 'theme_codecart_button_hover', 'focus' => 'input-storefront-button-hover-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_button_text_color', 'key' => 'theme_codecart_button_text_color', 'focus' => 'input-storefront-button-text-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_buy_button_color', 'key' => 'theme_codecart_buy_button_color', 'focus' => 'input-storefront-buy-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_buy_button_hover', 'key' => 'theme_codecart_buy_button_hover', 'focus' => 'input-storefront-buy-hover-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_buy_button_text_color', 'key' => 'theme_codecart_buy_button_text_color', 'focus' => 'input-storefront-buy-text-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_sale_price_color', 'key' => 'theme_codecart_sale_price_color', 'focus' => 'input-storefront-sale-price-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_cart_button_color', 'key' => 'theme_codecart_cart_button_color', 'focus' => 'input-storefront-cart-button-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_cart_button_hover', 'key' => 'theme_codecart_cart_button_hover', 'focus' => 'input-storefront-cart-hover-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_cart_button_text_color', 'key' => 'theme_codecart_cart_button_text_color', 'focus' => 'input-storefront-cart-text-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_text_color', 'key' => 'theme_codecart_text_color', 'focus' => 'input-storefront-text-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_heading_color', 'key' => 'theme_codecart_heading_color', 'focus' => 'input-storefront-heading-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_background_color', 'key' => 'theme_codecart_background_color', 'focus' => 'input-storefront-background-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_surface_color', 'key' => 'theme_codecart_surface_color', 'focus' => 'input-storefront-surface-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_border_color', 'key' => 'theme_codecart_border_color', 'focus' => 'input-storefront-border-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_footer_background_color', 'key' => 'theme_codecart_footer_background_color', 'focus' => 'input-storefront-footer-background-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_footer_text_color', 'key' => 'theme_codecart_footer_text_color', 'focus' => 'input-storefront-footer-text-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_footer_link_color', 'key' => 'theme_codecart_footer_link_color', 'focus' => 'input-storefront-footer-link-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_storefront_footer_heading_color', 'key' => 'theme_codecart_footer_heading_color', 'focus' => 'input-storefront-footer-heading-picker', 'tab' => 'appearance', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_robots', 'key' => 'config_robots', 'focus' => 'input-robots', 'tab' => 'server', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_cache_engine', 'key' => 'codecart_cache_engine', 'focus' => 'input-cache-engine', 'tab' => 'server', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_compression', 'key' => 'config_compression', 'focus' => 'input-compression', 'tab' => 'server', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_encryption', 'key' => 'config_encryption', 'focus' => 'input-encryption', 'tab' => 'server', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_file_max_size', 'key' => 'config_file_max_size', 'focus' => 'input-file-max-size', 'tab' => 'server', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_file_ext_allowed', 'key' => 'config_file_ext_allowed', 'focus' => 'input-file-ext-allowed', 'tab' => 'server', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_file_mime_allowed', 'key' => 'config_file_mime_allowed', 'focus' => 'input-file-mime-allowed', 'tab' => 'server', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_error_filename', 'key' => 'config_error_filename', 'focus' => 'input-error-filename', 'tab' => 'server', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_page_postfix', 'key' => 'config_page_postfix', 'focus' => 'input-page-postfix', 'tab' => 'seopro', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_valide_params', 'key' => 'config_valide_params', 'focus' => 'input-valide-params', 'tab' => 'seopro', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_seo_filter_index_mode', 'key' => 'config_seo_filter_index_mode', 'focus' => 'input-seo-filter-index-mode', 'tab' => 'seopro', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_seo_filter_allowlist', 'key' => 'config_seo_filter_allowlist', 'focus' => 'input-seo-filter-allowlist', 'tab' => 'seopro', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
            array('label' => 'entry_noindex_disallow_params', 'key' => 'config_noindex_disallow_params', 'focus' => 'input-noindex-disallow-params', 'tab' => 'seopro', 'route' => 'setting/setting', 'permission' => 'setting/setting'),
        );
    }

    public function getThemeEntries() {
        return array(
            array('label' => 'entry_directory', 'key' => 'theme_codecart_directory', 'focus' => 'input-directory', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_status', 'key' => 'theme_codecart_status', 'focus' => 'input-status', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_commerce_style', 'key' => 'theme_codecart_commerce_style', 'focus' => 'input-commerce-style', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_catalog_ajax', 'key' => 'theme_codecart_catalog_ajax_status', 'focus' => 'input-catalog-ajax', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_font_family', 'key' => 'theme_codecart_font_family', 'focus' => 'input-font-family', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_icon_mode', 'key' => 'theme_codecart_icon_mode', 'focus' => 'input-icon-mode', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_accent_color', 'key' => 'theme_codecart_accent_color', 'focus' => 'input-accent-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_accent_hover', 'key' => 'theme_codecart_accent_hover', 'focus' => 'input-accent-hover-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_button_color', 'key' => 'theme_codecart_button_color', 'focus' => 'input-button-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_button_hover', 'key' => 'theme_codecart_button_hover', 'focus' => 'input-button-hover-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_button_text_color', 'key' => 'theme_codecart_button_text_color', 'focus' => 'input-button-text-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_buy_button_color', 'key' => 'theme_codecart_buy_button_color', 'focus' => 'input-buy-button-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_buy_button_hover', 'key' => 'theme_codecart_buy_button_hover', 'focus' => 'input-buy-button-hover-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_buy_button_text_color', 'key' => 'theme_codecart_buy_button_text_color', 'focus' => 'input-buy-button-text-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_sale_price_color', 'key' => 'theme_codecart_sale_price_color', 'focus' => 'input-sale-price-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_cart_button_color', 'key' => 'theme_codecart_cart_button_color', 'focus' => 'input-cart-button-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_cart_button_hover', 'key' => 'theme_codecart_cart_button_hover', 'focus' => 'input-cart-button-hover-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_cart_button_text_color', 'key' => 'theme_codecart_cart_button_text_color', 'focus' => 'input-cart-button-text-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_text_color', 'key' => 'theme_codecart_text_color', 'focus' => 'input-text-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_heading_color', 'key' => 'theme_codecart_heading_color', 'focus' => 'input-heading-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_h1_color', 'key' => 'theme_codecart_h1_color', 'focus' => 'input-h1-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_h2_color', 'key' => 'theme_codecart_h2_color', 'focus' => 'input-h2-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_background_color', 'key' => 'theme_codecart_background_color', 'focus' => 'input-background-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_surface_color', 'key' => 'theme_codecart_surface_color', 'focus' => 'input-surface-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_border_color', 'key' => 'theme_codecart_border_color', 'focus' => 'input-border-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_footer_background_color', 'key' => 'theme_codecart_footer_background_color', 'focus' => 'input-footer-background-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_footer_text_color', 'key' => 'theme_codecart_footer_text_color', 'focus' => 'input-footer-text-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_footer_link_color', 'key' => 'theme_codecart_footer_link_color', 'focus' => 'input-footer-link-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_footer_heading_color', 'key' => 'theme_codecart_footer_heading_color', 'focus' => 'input-footer-heading-color-picker', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_border_radius', 'key' => 'theme_codecart_border_radius', 'focus' => 'input-border-radius', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_dark_mode', 'key' => 'theme_codecart_dark_mode_status', 'focus' => 'input-dark-mode-status', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_dark_mode_default', 'key' => 'theme_codecart_dark_mode_default', 'focus' => 'input-dark-mode-default', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_datetimepicker_theme', 'key' => 'theme_codecart_datetimepicker_theme', 'focus' => 'input-datetimepicker-theme', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_gallery_engine', 'key' => 'theme_codecart_gallery_engine', 'focus' => 'input-gallery-engine', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_header_phone', 'key' => 'theme_codecart_header_phone_status', 'focus' => 'input-header-phone', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_header_email', 'key' => 'theme_codecart_header_email_status', 'focus' => 'input-header-email', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_header_menu_mode', 'key' => 'theme_codecart_header_menu_mode', 'focus' => 'input-header-menu-mode', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_header_products', 'key' => 'theme_codecart_header_product_ids[]', 'focus' => 'input-header-product', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_custom_css', 'key' => 'theme_codecart_custom_css', 'focus' => 'input-custom-css', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_product_limit', 'key' => 'theme_codecart_product_limit', 'focus' => 'input-catalog-limit', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_product_description_length', 'key' => 'theme_codecart_product_description_length', 'focus' => 'input-description-limit', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_product_card_content', 'key' => 'theme_codecart_product_card_content', 'focus' => 'input-product-card-content', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_product_card_attribute_limit', 'key' => 'theme_codecart_product_card_attribute_limit', 'focus' => 'input-product-card-attribute-limit', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_option_image_switch_status', 'key' => 'theme_codecart_option_image_switch_status', 'focus' => 'input-option-image-switch', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_purchase_blocks_status', 'key' => 'theme_codecart_purchase_blocks_status', 'focus' => 'input-purchase-blocks-status', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_product_extra_tab_status', 'key' => 'theme_codecart_product_extra_tab_status', 'focus' => 'input-product-extra-tab-status', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_category', 'key' => 'theme_codecart_image_category_width', 'focus' => 'input-image-category-width', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_manufacturer', 'key' => 'theme_codecart_image_manufacturer_width', 'focus' => 'input-image-manufacturer-width', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_thumb', 'key' => 'theme_codecart_image_thumb_width', 'focus' => 'input-image-thumb-width', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_popup', 'key' => 'theme_codecart_image_popup_width', 'focus' => 'input-image-popup-width', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_product', 'key' => 'theme_codecart_image_product_width', 'focus' => 'input-image-product-width', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_additional', 'key' => 'theme_codecart_image_additional_width', 'focus' => 'input-image-additional-width', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_related', 'key' => 'theme_codecart_image_related_width', 'focus' => 'input-image-related', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_compare', 'key' => 'theme_codecart_image_compare_width', 'focus' => 'input-image-compare', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_wishlist', 'key' => 'theme_codecart_image_wishlist_width', 'focus' => 'input-image-wishlist', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_cart', 'key' => 'theme_codecart_image_cart_width', 'focus' => 'input-image-cart', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
            array('label' => 'entry_image_location', 'key' => 'theme_codecart_image_location_width', 'focus' => 'input-image-location', 'tab' => '', 'route' => 'extension/theme/codecart', 'permission' => 'extension/theme/codecart'),
        );
    }

    public function getAdminPageEntries() {
        return array(
            array('language'=>'tool/codecart_core','label'=>'text_runtime','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'runtime','icon'=>'fa-heartbeat','keywords'=>'environment runtime php server diagnostics preflight середовище среда сервер діагностика диагностика'),
            array('language'=>'tool/codecart_core','label'=>'text_performance','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'performance','icon'=>'fa-tachometer','keywords'=>'performance minify css javascript продуктивність производительность оптимізація оптимизация'),
            array('language'=>'tool/codecart_core','label'=>'text_architecture','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'architecture','icon'=>'fa-sitemap','keywords'=>'architecture api service extensions adapter архітектура архитектура modern extension'),
            array('language'=>'tool/codecart_core','label'=>'text_security','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'security','icon'=>'fa-shield','keywords'=>'security totp login headers csp hsts безпека безопасность захист защита'),
            array('language'=>'tool/codecart_core','label'=>'text_privacy','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'privacy','icon'=>'fa-user-secret','keywords'=>'privacy gdpr cookies конфіденційність конфиденциальность cookie'),
            array('language'=>'tool/codecart_core','label'=>'text_schema','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'schema','icon'=>'fa-database','keywords'=>'database schema mysql mariadb indexes база данных схема бд індекси индексы'),
            array('language'=>'tool/codecart_core','label'=>'text_extensions','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'extensions','icon'=>'fa-puzzle-piece','keywords'=>'modern extensions registry adapters сучасні розширення современные расширения'),
            array('language'=>'tool/codecart_core','label'=>'text_icons','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'icons','icon'=>'fa-font','keywords'=>'icons font awesome fa іконки иконки значки icon scanner'),
            array('language'=>'tool/codecart_core','label'=>'text_about','route'=>'tool/codecart_core','permission'=>'tool/codecart_core','tab'=>'about','icon'=>'fa-info-circle','keywords'=>'about system version compatibility про систему о системе codecart'),
            array('language'=>'tool/notification','label'=>'heading_title','route'=>'tool/notification','permission'=>'tool/notification','tab'=>'','icon'=>'fa-bell','keywords'=>'system notifications alerts notices системні сповіщення системные уведомления повідомлення сообщения'),
            array('language'=>'tool/scheduler','label'=>'heading_title','route'=>'tool/scheduler','permission'=>'tool/scheduler','tab'=>'','icon'=>'fa-clock-o','keywords'=>'scheduler cron queue jobs планувальник планировщик очередь черга cron'),
            array('language'=>'tool/backup','label'=>'heading_title','route'=>'tool/backup','permission'=>'tool/backup','tab'=>'','icon'=>'fa-database','keywords'=>'backup restore database резервна копія відновлення резервная копия восстановление'),
            array('language'=>'tool/upload','label'=>'heading_title','route'=>'tool/upload','permission'=>'tool/upload','tab'=>'','icon'=>'fa-upload','keywords'=>'uploads upload files завантаження загрузки файлы файли'),
            array('language'=>'common/developer','label'=>'heading_title','route'=>'common/developer','permission'=>'common/developer','tab'=>'','icon'=>'fa-code','keywords'=>'developer cache theme sass modification розробник разработчик кеш cache ocmod'),
            array('language'=>'setting/setting','label'=>'heading_title','route'=>'setting/setting','permission'=>'setting/setting','tab'=>'','icon'=>'fa-cogs','keywords'=>'store settings system settings налаштування настройки магазин система'),
            array('language'=>'setting/store','label'=>'heading_title','route'=>'setting/store','permission'=>'setting/store','tab'=>'','icon'=>'fa-shopping-bag','keywords'=>'stores multistore магазини магазины multistore'),
            array('language'=>'blog/setting','label'=>'heading_title','route'=>'blog/setting','permission'=>'blog/setting','tab'=>'','icon'=>'fa-newspaper-o','keywords'=>'blog settings блог налаштування настройки'),
            array('language'=>'extension/theme/codecart','label'=>'heading_title','route'=>'extension/theme/codecart','permission'=>'extension/theme/codecart','tab'=>'','icon'=>'fa-paint-brush','keywords'=>'theme storefront appearance design codecart шаблон тема оформлення оформление витрина'),
            array('language'=>'marketplace/extension','label'=>'heading_title','route'=>'marketplace/extension','permission'=>'marketplace/extension','tab'=>'','icon'=>'fa-puzzle-piece','keywords'=>'extensions modules payments shipping доповнення расширения модули модулі'),
            array('language'=>'marketplace/modification','label'=>'heading_title','route'=>'marketplace/modification','permission'=>'marketplace/modification','tab'=>'','icon'=>'fa-code','keywords'=>'modifications ocmod модифікатори модификаторы ocmod'),
            array('language'=>'design/layout','label'=>'heading_title','route'=>'design/layout','permission'=>'design/layout','tab'=>'','icon'=>'fa-th-large','keywords'=>'layouts positions макети макеты layout позиції позиции'),
            array('language'=>'design/seo_url','label'=>'heading_title','route'=>'design/seo_url','permission'=>'design/seo_url','tab'=>'','icon'=>'fa-link','keywords'=>'seo url seo urls чпу keyword urls посилання ссылки'),
            array('language'=>'design/theme','label'=>'heading_title','route'=>'design/theme','permission'=>'design/theme','tab'=>'','icon'=>'fa-paint-brush','keywords'=>'theme editor шаблон редактор тема'),
            array('language'=>'design/translation','label'=>'heading_title','route'=>'design/translation','permission'=>'design/translation','tab'=>'','icon'=>'fa-language','keywords'=>'translation language переклад перевод мова язык'),
            array('language'=>'design/banner','label'=>'heading_title','route'=>'design/banner','permission'=>'design/banner','tab'=>'','icon'=>'fa-picture-o','keywords'=>'banners банери баннеры реклама'),
            array('language'=>'user/user','label'=>'heading_title','route'=>'user/user','permission'=>'user/user','tab'=>'','icon'=>'fa-user','keywords'=>'users administrators користувачі пользователи администраторы'),
            array('language'=>'user/user_group','label'=>'heading_title','route'=>'user/user_permission','permission'=>'user/user_permission','tab'=>'','icon'=>'fa-users','keywords'=>'user groups permissions права групи группы доступ access'),
            array('language'=>'user/api','label'=>'heading_title','route'=>'user/api','permission'=>'user/api','tab'=>'','icon'=>'fa-key','keywords'=>'api users api keys api користувачі ключі пользователи ключи'),
            array('language'=>'localisation/language','label'=>'heading_title','route'=>'localisation/language','permission'=>'localisation/language','tab'=>'','icon'=>'fa-language','keywords'=>'languages мови языки locale localization локалізація локализация'),
            array('language'=>'localisation/currency','label'=>'heading_title','route'=>'localisation/currency','permission'=>'localisation/currency','tab'=>'','icon'=>'fa-money','keywords'=>'currencies currency валюти валюты курс'),
            array('language'=>'localisation/country','label'=>'heading_title','route'=>'localisation/country','permission'=>'localisation/country','tab'=>'','icon'=>'fa-globe','keywords'=>'countries країни страны'),
            array('language'=>'localisation/zone','label'=>'heading_title','route'=>'localisation/zone','permission'=>'localisation/zone','tab'=>'','icon'=>'fa-map-marker','keywords'=>'zones regions області регионы зоны'),
            array('language'=>'localisation/geo_zone','label'=>'heading_title','route'=>'localisation/geo_zone','permission'=>'localisation/geo_zone','tab'=>'','icon'=>'fa-map','keywords'=>'geo zones geozone геозони геозоны доставка shipping'),
            array('language'=>'localisation/order_status','label'=>'heading_title','route'=>'localisation/order_status','permission'=>'localisation/order_status','tab'=>'','icon'=>'fa-list-alt','keywords'=>'order statuses статус замовлення статусы заказа'),
            array('language'=>'localisation/stock_status','label'=>'heading_title','route'=>'localisation/stock_status','permission'=>'localisation/stock_status','tab'=>'','icon'=>'fa-cubes','keywords'=>'stock status склад статус наявності наличия'),
            array('language'=>'localisation/tax_class','label'=>'heading_title','route'=>'localisation/tax_class','permission'=>'localisation/tax_class','tab'=>'','icon'=>'fa-percent','keywords'=>'tax classes taxes податки налоги налоговые классы'),
            array('language'=>'localisation/tax_rate','label'=>'heading_title','route'=>'localisation/tax_rate','permission'=>'localisation/tax_rate','tab'=>'','icon'=>'fa-percent','keywords'=>'tax rates taxes ставки податку налога'),
            array('language'=>'localisation/length_class','label'=>'heading_title','route'=>'localisation/length_class','permission'=>'localisation/length_class','tab'=>'','icon'=>'fa-arrows-h','keywords'=>'length units довжина длина одиниці единицы'),
            array('language'=>'localisation/weight_class','label'=>'heading_title','route'=>'localisation/weight_class','permission'=>'localisation/weight_class','tab'=>'','icon'=>'fa-balance-scale','keywords'=>'weight units вага вес одиниці единицы'),
            array('language'=>'localisation/location','label'=>'heading_title','route'=>'localisation/location','permission'=>'localisation/location','tab'=>'','icon'=>'fa-map-marker','keywords'=>'locations місцезнаходження местонахождение адрес stores'),
            array('language'=>'tool/log','label'=>'heading_title','route'=>'tool/log','permission'=>'tool/log','tab'=>'','icon'=>'fa-file-text-o','keywords'=>'error log logs журнал помилок ошибок лог логи'),
            array('language'=>'report/statistics','label'=>'heading_title','route'=>'report/statistics','permission'=>'report/statistics','tab'=>'','icon'=>'fa-bar-chart','keywords'=>'statistics reports статистика звіти отчеты')
        );
    }

    public function match(array $entry, $query) {
        $needle = $this->normalize($query);
        if ($needle === '') return 0;

        $title = $this->normalize(isset($entry['title']) ? $entry['title'] : '');
        $path = $this->normalize(isset($entry['path']) ? $entry['path'] : '');
        $key = $this->normalize(isset($entry['key']) ? $entry['key'] : '');
        $keywords = $this->normalize(isset($entry['keywords']) ? $entry['keywords'] : '');

        if ($title === $needle) return 100;
        if ($title !== '' && strpos($title, $needle) === 0) return 90;
        if ($title !== '' && strpos($title, $needle) !== false) return 80;
        if ($keywords !== '' && strpos($keywords, $needle) !== false) return 70;
        if ($key !== '' && strpos($key, $needle) !== false) return 60;
        if ($path !== '' && strpos($path, $needle) !== false) return 50;

        $haystack = trim($title . ' ' . $path . ' ' . $key . ' ' . $keywords);
        $tokens = preg_split('/\s+/u', $needle, -1, PREG_SPLIT_NO_EMPTY);
        if (count($tokens) > 1) {
            foreach ($tokens as $token) {
                if (strpos($haystack, $token) === false) return 0;
            }
            return 40;
        }

        return 0;
    }

    private function normalize($value) {
        $value = trim((string)$value);
        if ($value === '') return '';
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = str_replace(array('_', '-', '/', '\\', '.', ':', ';', ',', '(', ')', '[', ']'), ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim((string)$value);
    }
}
