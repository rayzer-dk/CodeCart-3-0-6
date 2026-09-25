/*!
 * 
 * Super simple WYSIWYG editor v0.9.1
 * https://summernote.org
 *
 * Copyright 2013~ Hackerwins and contributors
 * Summernote may be freely distributed under the MIT license.
 *
 * Date: 2024-10-09T10:22Z
 *
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else {
		var a = factory();
		for(var i in a) (typeof exports === 'object' ? exports : root)[i] = a[i];
	}
})(self, () => {
return /******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
(function ($) {
  $.extend(true, $.summernote.lang, {
    'uk-UA': {
      font: {
        bold: 'Напівжирний',
        italic: 'Курсив',
        underline: 'Підкреслений',
        clear: 'Прибрати стилі шрифту',
        height: 'Висота лінії',
        name: 'Шрифт',
        strikethrough: 'Закреслений',
        subscript: 'Нижній індекс',
        superscript: 'Верхній індекс',
        size: 'Розмір шрифту'
      },
      image: {
        image: 'Картинка',
        insert: 'Вставити картинку',
        resizeFull: 'Відновити розмір',
        resizeHalf: 'Зменшити до 50%',
        resizeQuarter: 'Зменшити до 25%',
        floatLeft: 'Розташувати ліворуч',
        floatRight: 'Розташувати праворуч',
        floatNone: 'Початкове розташування',
        shapeRounded: 'Форма: Заокруглена',
        shapeCircle: 'Форма: Коло',
        shapeThumbnail: 'Форма: Мініатюра',
        shapeNone: 'Форма: Немає',
        dragImageHere: 'Перетягніть сюди картинку',
        dropImage: 'Перетягніть картинку',
        selectFromFiles: 'Вибрати з файлів',
        maximumFileSize: 'Максимальний розмір файлу',
        maximumFileSizeError: 'Перевищено максимальний розмір файлу.',
        url: 'URL картинки',
        remove: 'Видалити картинку',
        original: 'Оригінал'
      },
      video: {
        video: 'Відео',
        videoLink: 'Посилання на відео',
        insert: 'Вставити відео',
        url: 'URL відео',
        providers: '(YouTube, Vimeo, Facebook Video, Instagram, Dailymotion, Google Drive, MP4/M4V/WebM/Ogg)'
      },
      link: {
        link: 'Посилання',
        insert: 'Вставити посилання',
        unlink: 'Прибрати посилання',
        edit: 'Редагувати',
        textToDisplay: 'Текст, що відображається',
        url: 'URL для переходу',
        openInNewWindow: 'Відкрити у новому вікні'
      },
      table: {
        table: 'Таблиця',
        addRowAbove: 'Додати рядок вище',
        addRowBelow: 'Додати рядок нижче',
        addColLeft: 'Додати стовпчик ліворуч',
        addColRight: 'Додати стовпчик праворуч',
        delRow: 'Видалити рядок',
        delCol: 'Видалити стовпчик',
        delTable: 'Видалити таблицю'
      },
      hr: {
        insert: 'Вставити горизонтальну лінію'
      },
      style: {
        style: 'Стиль',
        p: 'Нормальний',
        blockquote: 'Цитата',
        pre: 'Код',
        h1: 'Заголовок 1',
        h2: 'Заголовок 2',
        h3: 'Заголовок 3',
        h4: 'Заголовок 4',
        h5: 'Заголовок 5',
        h6: 'Заголовок 6'
      },
      lists: {
        unordered: 'Маркований список',
        ordered: 'Нумерований список'
      },
      options: {
        help: 'Допомога',
        fullscreen: 'На весь екран',
        codeview: 'Початковий код'
      },
      paragraph: {
        paragraph: 'Параграф',
        outdent: 'Зменшити відступ',
        indent: 'Збільшити відступ',
        left: 'Вирівняти по лівому краю',
        center: 'Вирівняти по центру',
        right: 'Вирівняти по правому краю',
        justify: 'Розтягнути по ширині'
      },
      color: {
        recent: 'Останній колір',
        more: 'Ще кольори',
        background: 'Колір фону',
        foreground: 'Колір шрифту',
        transparent: 'Прозорий',
        setTransparent: 'Зробити прозорим',
        reset: 'Відновити',
        resetToDefault: 'Відновити початкові'
      },
      shortcut: {
        shortcuts: 'Комбінації клавіш',
        close: 'Закрити',
        textFormatting: 'Форматування тексту',
        action: 'Дія',
        paragraphFormatting: 'Форматування параграфу',
        documentStyle: 'Стиль документу',
        extraKeys: 'Додаткові комбінації'
      },
      help: {
        'insertParagraph': 'Вставити новий абзац',
        'undo': 'Скасувати останню дію',
        'redo': 'Повторити останню дію',
        'tab': 'Збільшити відступ / перейти вперед',
        'untab': 'Зменшити відступ / перейти назад',
        'bold': 'Напівжирний текст',
        'italic': 'Курсив',
        'underline': 'Підкреслений текст',
        'strikethrough': 'Закреслений текст',
        'removeFormat': 'Очистити форматування',
        'justifyLeft': 'Вирівняти ліворуч',
        'justifyCenter': 'Вирівняти по центру',
        'justifyRight': 'Вирівняти праворуч',
        'justifyFull': 'Вирівняти по ширині',
        'insertUnorderedList': 'Увімкнути або вимкнути маркований список',
        'insertOrderedList': 'Увімкнути або вимкнути нумерований список',
        'outdent': 'Зменшити відступ абзацу',
        'indent': 'Збільшити відступ абзацу',
        'formatPara': 'Змінити поточний блок на абзац (тег P)',
        'formatH1': 'Змінити поточний блок на H1',
        'formatH2': 'Змінити поточний блок на H2',
        'formatH3': 'Змінити поточний блок на H3',
        'formatH4': 'Змінити поточний блок на H4',
        'formatH5': 'Змінити поточний блок на H5',
        'formatH6': 'Змінити поточний блок на H6',
        'insertHorizontalRule': 'Вставити горизонтальну лінію',
        'linkDialog.show': 'Відкрити вікно вставки посилання'
      },
      history: {
        undo: 'Відмінити',
        redo: 'Повторити'
      },
      specialChar: {
        specialChar: 'Спеціальні символи',
        select: 'Виберіть символ'
      }
    }
  });
})(jQuery);
/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=summernote-uk-UA.js.map