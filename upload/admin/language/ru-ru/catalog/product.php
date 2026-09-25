<?php
$_['entry_shipping'] = 'Необходима доставка';

$_['help_shipping'] = 'Для цифрового товара выберите «Нет» и добавьте защищённый файл во вкладке «Связи → Файлы для загрузки». Этапы доставки пропускаются автоматически. Покупателю требуется аккаунт, а файл становится доступен только после статуса заказа «Завершено».';

// CodeCart PRO product delivery type
$_['text_shipping_required'] = 'Физический / гибридный — нужна доставка';
$_['text_shipping_not_required'] = 'Цифровой / услуга — без доставки';

$_['help_download_updates'] = 'Завершённая покупка даёт право на файлы, которые сейчас привязаны к этому товару. Для новой версии можно заменить файл в существующей загрузке или привязать новую загрузку — прежние покупатели увидят текущие файлы. Снимок на момент покупки остаётся резервом, если все текущие файлы будут удалены.';
$_['button_image_multiple'] = 'Выбрать несколько изображений';
$_['button_image_drag'] = 'Перетащить для сортировки';
$_['button_image_main'] = 'Сделать главным изображением';

$_['entry_google_product_category_id'] = 'Google Product Category ID';
$_['help_google_product_category_id'] = 'Числовой ID таксономии Google для Merchant-фидов. Оставьте пустым, чтобы унаследовать значение из главной или ближайшей родительской категории товара.';

// CodeCart PRO RC86: compatibility-safe product taxonomy shortcuts.
$_['text_attribute_quick_tools'] = 'Быстрая работа с характеристиками';
$_['help_attribute_quick_tools'] = 'Создайте или откройте штатный справочник в новой вкладке, затем добавьте характеристику товару через стандартное автодополнение. Сохранение товара остаётся штатным.';
$_['button_attribute_create'] = 'Создать характеристику';
$_['button_attribute_manage'] = 'Справочник характеристик';
$_['text_option_quick_tools'] = 'Быстрая работа с опциями';
$_['help_option_quick_tools'] = 'Создайте опцию в штатном справочнике в новой вкладке, затем найдите её в этом товаре. Структура БД и стандартное сохранение OpenCart не меняются.';
$_['button_option_create'] = 'Создать опцию';
$_['button_option_manage'] = 'Справочник опций';

$_['entry_tax_display_mode'] = "Отображение НДС для товара";
$_['help_tax_display_mode'] = "Переопределяет глобальный режим показа НДС только для этого товара. Расчёт налогов и оформление заказа не изменяются.";
$_['text_tax_display_inherit'] = "Использовать общую настройку";
$_['text_tax_display_native'] = "Штатное отображение OpenCart";
$_['text_tax_display_none'] = "Только основная цена";
$_['text_tax_display_gross_net'] = "Цена с НДС + без НДС";
$_['text_tax_display_gross_tax'] = "Цена с НДС + сумма НДС";
$_['text_tax_display_net_gross'] = "Цена без НДС + с НДС";

// CodeCart PRO RC86: inherited purchase-area information blocks.
$_['tab_purchase_blocks'] = 'Контентные блоки';
$_['entry_purchase_block_mode'] = 'Контентные блоки';
$_['text_purchase_block_inherit'] = 'Наследовать';
$_['text_purchase_block_custom'] = 'Собственные блоки';
$_['text_purchase_block_disabled'] = 'Не показывать';
$_['help_purchase_block_mode'] = 'Выберите наследование, собственный набор или полное отключение блоков для этой сущности.';
$_['help_purchase_block_inheritance'] = 'Для товара наследование берет ближайшую настройку главной категории. Собственный режим товара имеет приоритет.';
$_['text_purchase_block_builder'] = 'Конструктор блоков';
$_['text_purchase_block_info'] = 'Информационный / HTML-блок';
$_['text_purchase_block_size_table'] = 'Таблица размеров';
$_['text_purchase_block_sizes'] = 'Размеры';
$_['text_purchase_block_colors'] = 'Цвета';
$_['entry_purchase_block_title'] = 'Заголовок';
$_['entry_purchase_block_content'] = 'Содержимое';
$_['entry_purchase_block_columns'] = 'Столбцы, по одному в строке';
$_['entry_purchase_block_rows'] = 'Строки таблицы; ячейки разделяйте символом |';
$_['entry_purchase_block_item_label'] = 'Название';
$_['entry_purchase_block_item_value'] = 'Значение';
$_['button_purchase_block_add_item'] = 'Добавить строку';
$_['button_purchase_block_up'] = 'Переместить выше';
$_['button_purchase_block_down'] = 'Переместить ниже';
$_['text_purchase_block_empty'] = 'Блоков пока нет. Добавьте нужный тип кнопкой выше.';
$_['button_purchase_block_add_info'] = 'Инфо';
$_['button_purchase_block_add_size_table'] = 'Таблица размеров';
$_['button_purchase_block_add_sizes'] = 'Размеры';
$_['button_purchase_block_add_colors'] = 'Цвета';
$_['help_purchase_block_builder'] = 'Сохранение выполняется только штатной кнопкой сохранения товара/категории. Штатные опции и характеристики OpenCart не изменяются.';

$_['text_purchase_block_form'] = 'Форма / CTA';
$_['entry_purchase_block_form'] = 'Форма';
$_['entry_purchase_block_form_display'] = 'Отображение';
$_['entry_purchase_block_form_button_text'] = 'Текст кнопки';
$_['button_purchase_block_add_form'] = 'Форма';
$_['text_purchase_block_form_inline'] = 'Показать форму сразу';
$_['text_purchase_block_form_button'] = 'Кнопка + всплывающее окно';
$_['warning_purchase_blocks_master'] = 'Блоки сохраняются, но их вывод на витрине глобально выключен в настройках стандартной темы.';
$_['button_purchase_blocks_settings'] = 'Открыть настройки темы';

$_['button_generate_seo_url'] = 'Сгенерировать SEO URL';

$_['text_purchase_block_templates'] = 'Шаблоны';
$_['text_purchase_block_preset_delivery'] = 'Условия доставки';
$_['text_purchase_block_preset_instruction'] = 'Инструкция использования';
$_['text_purchase_block_preset_rules'] = 'Правила / важная информация';
$_['text_purchase_block_preset_shoes_eu'] = 'Таблица размеров обуви EU';
$_['text_purchase_block_preset_women_eu'] = 'Женские размеры одежды EU';
$_['text_purchase_block_preset_men_eu'] = 'Мужские размеры одежды EU';

// CodeCart PRO product list filters
$_['entry_seo_url'] = 'SEO URL';

$_['button_relation_suggest'] = 'Автоподбор';
$_['button_relation_selected'] = 'Сгенерировать автосвязи для выбранных';
$_['button_add_selected'] = 'Добавить выбранное в форму';
$_['text_relation_products'] = 'Предложенные товары';
$_['text_relation_articles'] = 'Предложенные статьи';
$_['text_relation_score'] = 'Релевантность';
$_['text_relation_added'] = 'Предложения добавлены в форму. Для сохранения нажмите стандартную кнопку «Сохранить».';

$_['text_relation_button_help'] = 'Автоматически подбирает варианты в стандартные ручные поля. Это помощник заполнения формы, а не запись в Auto Relation Layer. Изменения применятся только после обычного сохранения формы.';
$_['text_relation_autofill_result'] = 'Автоподбор в ручное поле добавил: %a. Сейчас выбрано: %t. Лимит: %m. Нажмите «Сохранить». Auto Relation Layer генерируется отдельно через фоновую очередь.';
$_['text_relation_selected_count'] = 'Сейчас выбрано в поле: %t. Лимит автоподбора: %m.';
$_['text_none_category'] = ' --- Без категории --- ';
$_['text_none_manufacturer'] = ' --- Без производителя --- ';

$_['text_stock_notify_waiting'] = 'Ждут наличия';
