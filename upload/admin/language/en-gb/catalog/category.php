<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

// Heading
$_['heading_title']          = 'Categories';

// Text
$_['text_success']           = 'Success: You have modified categories!';
$_['text_list']              = 'Category List';
$_['text_add']               = 'Add Category';
$_['text_edit']              = 'Edit Category';
$_['text_default']           = 'Default';
$_['text_category_total']    = 'Total Categories: ';
$_['text_keyword']           = 'Do not use spaces, instead replace spaces with - and make sure the SEO URL is globally unique.';

// Column
$_['column_name']            = 'Category Name';
$_['column_sort_order']      = 'Sort Order';
$_['column_noindex']         = 'Index';
$_['column_action']          = 'Action';

// Entry
$_['entry_name']             = 'Category Name';
$_['entry_meta_h1']       	 = 'Meta Tag H1';
$_['entry_description']      = 'Description';
$_['entry_meta_title'] 	     = 'Meta Tag Title';
$_['entry_meta_keyword']     = 'Meta Tag Keywords';
$_['entry_meta_description'] = 'Meta Tag Description';
$_['entry_store']            = 'Stores';
$_['entry_keyword']          = 'Keyword';
$_['entry_parent']           = 'Parent';
$_['entry_filter']           = 'Filters';
$_['entry_image']            = 'Image';
$_['entry_top']              = 'Top';
$_['entry_column']           = 'Columns';
$_['entry_sort_order']       = 'Sort Order';
$_['entry_status']           = 'Status';
$_['entry_layout']           = 'Layout Override';
$_['entry_noindex']          = 'Index';
$_['entry_related_wb']       = 'Featured Products:';
$_['entry_related_article']  = 'Featured Articles:';

// Help
$_['help_filter']            = '(Autocomplete)';
$_['help_top']               = 'Display in the top menu bar. Only works for the top parent categories.';
$_['help_column']            = 'Number of columns to use for the bottom 3 categories. Only works for the top parent categories.';
$_['help_related']           = '(Autocomplete)';

// Error
$_['error_warning']          = 'Warning: Please check the form carefully for errors!';
$_['error_permission']       = 'Warning: You do not have permission to modify categories!';
$_['error_name']             = 'Category Name must be between 1 and 255 characters!';
$_['error_meta_title']       = 'Meta Title must be greater than 1 and less than 255 characters!';
$_['error_keyword']          = 'SEO URL already in use!';
$_['error_unique']           = 'SEO URL must be unique!';
$_['error_parent']           = 'The parent category you have chosen is a child of the current one!';
$_['entry_google_product_category_id'] = 'Google Product Category ID';
$_['help_google_product_category_id'] = 'Numeric Google Product Taxonomy ID used by Merchant feeds. Leave empty to inherit the value from the nearest parent category.';

// CodeCart PRO RC86: inherited purchase-area information blocks.
$_['tab_purchase_blocks'] = 'Content blocks';
$_['entry_purchase_block_mode'] = 'Content blocks';
$_['text_purchase_block_inherit'] = 'Inherit from parent category';
$_['text_purchase_block_custom'] = 'Custom blocks';
$_['text_purchase_block_disabled'] = 'Do not show';
$_['help_purchase_block_mode'] = 'These blocks define inherited product content. Do not show disables inheritance from this category only; explicit product blocks still render.';
$_['help_purchase_block_inheritance'] = 'The category inherits the nearest configured parent template. A root category has nothing to inherit, so no blocks are shown in this mode. A custom set becomes the template for products in this category.';
$_['text_purchase_block_builder'] = 'Block builder';
$_['text_purchase_block_info'] = 'Information / HTML block';
$_['text_purchase_block_size_table'] = 'Size table';
$_['text_purchase_block_sizes'] = 'Sizes';
$_['text_purchase_block_colors'] = 'Colours';
$_['entry_purchase_block_title'] = 'Title';
$_['entry_purchase_block_content'] = 'Content';
$_['entry_purchase_block_columns'] = 'Columns, one per line';
$_['entry_purchase_block_rows'] = 'Table rows; separate cells with |';
$_['entry_purchase_block_item_label'] = 'Label';
$_['entry_purchase_block_item_value'] = 'Value';
$_['button_purchase_block_add_item'] = 'Add row';
$_['button_purchase_block_up'] = 'Move up';
$_['button_purchase_block_down'] = 'Move down';
$_['text_purchase_block_empty'] = 'No blocks yet. Add the required block type above.';
$_['button_purchase_block_add_info'] = 'Info';
$_['button_purchase_block_add_size_table'] = 'Size table';
$_['button_purchase_block_add_sizes'] = 'Sizes';
$_['button_purchase_block_add_colors'] = 'Colours';
$_['help_purchase_block_builder'] = 'Data is saved only with the standard product/category Save button. Native OpenCart options and attributes are not changed.';

$_['text_purchase_block_form'] = 'Form / CTA';
$_['entry_purchase_block_form'] = 'Form';
$_['entry_purchase_block_form_display'] = 'Display';
$_['entry_purchase_block_form_button_text'] = 'Button text';
$_['button_purchase_block_add_form'] = 'Form';
$_['text_purchase_block_form_inline'] = 'Show form inline';
$_['text_purchase_block_form_button'] = 'Button + modal';
$_['warning_purchase_blocks_master'] = 'Blocks are saved, but storefront output is disabled globally in CodeCart Theme settings.';
$_['button_purchase_blocks_settings'] = 'Open theme settings';
$_['button_purchase_blocks_forms'] = 'Manage forms';

$_['button_generate_seo_url'] = 'Generate SEO URL';

$_['text_purchase_block_templates'] = 'Templates';
$_['text_purchase_block_preset_delivery'] = 'Delivery terms';
$_['text_purchase_block_preset_instruction'] = 'Usage instructions';
$_['text_purchase_block_preset_rules'] = 'Rules / important information';
$_['text_purchase_block_preset_shoes_eu'] = 'EU shoe size chart';
$_['text_purchase_block_preset_women_eu'] = 'EU women clothing sizes';
$_['text_purchase_block_preset_men_eu'] = 'EU men clothing sizes';

$_['button_relation_suggest'] = 'Auto pick';
$_['text_relation_button_help'] = 'Automatically picks suggestions into the standard manual fields. This is a form-filling helper, not an Auto Relation Layer write. Changes are applied only after the normal form save.';
$_['text_relation_autofill_result'] = 'Auto pick added to the manual field: %a. Selected now: %t. Limit: %m. Click Save. Auto Relation Layer is generated separately through the background queue.';
$_['text_relation_selected_count'] = 'Selected in this field: %t. Auto-selection limit: %m.';

$_['entry_purchase_block_form_icon'] = 'Button icon';
$_['entry_purchase_block_form_bg'] = 'Button background';
$_['entry_purchase_block_form_text_color'] = 'Text color';
$_['entry_purchase_block_form_hover_bg'] = 'Hover background';
$_['entry_purchase_block_enabled'] = 'Show block';
$_['button_purchase_block_add_existing_form'] = 'Add selected';
$_['text_purchase_block_choose_form'] = '— Choose an existing form / block —';
$_['text_separator'] = ' &gt; ';
$_['error_meta_h1'] = 'HTML H1 tag must be between 0 and 255 characters!';
$_['js_purchase_presets_json'] = '{}';
