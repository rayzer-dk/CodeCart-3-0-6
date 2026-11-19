/* Consistent one-line admin list names with the original full text on hover.
 * Only name/title columns are enhanced; numeric cells, actions and form inputs are untouched.
 */
(function () {
  'use strict';
  var titleHeader = /(?:^|\\s)(?:назва|название|найменування|наименование|name|title|заголовок|модель|model)(?:\\s|$)/i;
  function textOf(element) {
    return (element.textContent || '').replace(/\\s+/g, ' ').trim();
  }
  function mark(element, title) {
    if (!title || element.classList.contains('ccp-table-ellipsis')) return;
    element.classList.add('ccp-table-ellipsis');
    if (!element.hasAttribute('title')) element.setAttribute('title', title);
  }
  function enhanceCell(cell) {
    if (cell.classList.contains('ccp-table-ellipsis-cell')) return;
    if (cell.querySelector('input,select,textarea,[contenteditable="true"]')) return;
    var special = cell.querySelector('.ccp-blog-name-text, .ccp-table-title');
    var anchor = special || Array.prototype.find.call(cell.querySelectorAll('a'), function (a) {
      return !a.closest('.btn,.cc-quality,.cc-quality-count,.ccp-row-actions') &&
        !a.querySelector('button,input,select') && textOf(a).length > 0;
    });
    var label = anchor;
    if (!label) {
      Array.prototype.some.call(cell.childNodes, function (node) {
        if (node.nodeType !== 3 || !node.textContent.trim()) return false;
        var span = document.createElement('span');
        span.textContent = node.textContent.trim();
        node.parentNode.replaceChild(span, node);
        label = span;
        return true;
      });
    }
    if (!label) return;
    var title = textOf(label);
    if (!title) return;
    cell.classList.add('ccp-table-ellipsis-cell');
    mark(label, title);
  }
  function scan() {
    Array.prototype.forEach.call(document.querySelectorAll('#content table.table'), function (table) {
      var heads = table.querySelectorAll('thead > tr:first-child > th, thead > tr:first-child > td');
      if (!heads.length) return;
      var indexes = [];
      Array.prototype.forEach.call(heads, function (head, i) {
        if (titleHeader.test(textOf(head))) indexes.push(i);
      });
      if (!indexes.length) return;
      Array.prototype.forEach.call(table.querySelectorAll('tbody > tr'), function (row) {
        Array.prototype.forEach.call(indexes, function (index) {
          if (row.cells && row.cells[index]) enhanceCell(row.cells[index]);
        });
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan);
  else scan();
  if (window.jQuery) window.jQuery(document).ajaxComplete(function () { scan(); });
}());
