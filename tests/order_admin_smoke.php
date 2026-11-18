<?php
define('DB_PREFIX', 'oc_');
require dirname(__DIR__) . '/upload/system/library/codecart/src/OrderAdminSearch.php';
function checkOrder($ok,$why) { if (!$ok) { throw new RuntimeException($why); } }
class FakeOrderDb { public function escape($v) { return addslashes((string)$v); } }
$db=new FakeOrderDb();
$filters=array('filter_phone'=>'+38 (095) 899-39-86','filter_email'=>'Test@example.com',
 'filter_product'=>'FTH-105','filter_total_min'=>'100,50','filter_total_max'=>'200.75',
 'filter_payment_method'=>"Credit's card",'filter_shipping_method'=>'Nova Poshta');
$sql=\CodeCart\Core\OrderAdminSearch::where($db,$filters);
$rank=\CodeCart\Core\OrderAdminSearch::ranking($db,$filters);
checkOrder(\CodeCart\Core\OrderAdminSearch::digits($filters['filter_phone'])==='380958993986','phone digits');
checkOrder(strpos($sql,"o.total >= '100.50'")!==false && strpos($sql,"o.total <= '200.75'")!==false,'range');
checkOrder(strpos($sql,'AND EXISTS (SELECT 1')!==false && strpos($sql,'op.order_id = o.order_id')!==false,'item filter');
checkOrder(strpos($sql,'LOWER(op.sku)')!==false,'historical order SKU');
checkOrder(strpos($sql,'CHAR(10)')!==false && strpos($sql,"\u{2011}")!==false,'phone separators');
checkOrder(strpos($rank,'THEN 0')!==false && strpos($rank,'THEN 1')!==false && strpos($rank,'THEN 3')!==false,'rank');
$fuzzySql = \CodeCart\Core\OrderAdminSearch::where($db,array('filter_phone'=>'0958993986'));
checkOrder(strpos($fuzzySql,"LIKE '958_93986'")!==false,'one wrong digit');
checkOrder(strpos($fuzzySql,"RIGHT(")!==false,'bounded national suffix');
checkOrder(strpos(\CodeCart\Core\OrderAdminSearch::where($db,array('filter_phone'=>'1234')), " LIKE '")===false,'no fuzzy for short terms');
checkOrder(\CodeCart\Core\OrderAdminSearch::amount('abc')==='','invalid numeric');
checkOrder(strpos(\CodeCart\Core\OrderAdminSearch::where($db,array('filter_total'=>'315')),"o.total >= '315'")!==false,'old exact amount');
checkOrder(strpos(\CodeCart\Core\OrderAdminSearch::where($db,array()),'telephone')===false,'no normal-list phone work');
echo "PASS: order filters, phone normalization, inclusive sums and ranking.\n";
