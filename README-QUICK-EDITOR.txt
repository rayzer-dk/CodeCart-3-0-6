CodeCart 3.0.6 — product list inline editor layout fix.
Update the three files below at their exact paths:
upload/admin/view/javascript/codecart-quick-price-edit.js
upload/admin/view/stylesheet/codecart_product_list.css
upload/admin/view/template/catalog/product_list.twig

Price and quantity now open in a compact 180px editor ABOVE the value.
The title text is removed. Only input, Save and Cancel remain.
The source cell remains unchanged, so no columns shift or overlap.
Save via green check or Enter. Cancel via X, Escape or outside click.
Status/index switches are unchanged and stay secured by existing AJAX.
Clear OpenCart modification and Twig cache after updating.
Always create a full backup of all site files and the database first!
