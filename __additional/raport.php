<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/wp-config.php' );
include_once($_SERVER['DOCUMENT_ROOT'].'/authorization.php' );

class Report
{
    var $waiting;
    var $released;
}

class Products
{
    var $name;
    var $count;
}

function db_raport_data() {	
    global $wpdb;
	
	$sql_waiting = "
select 
 oi.order_item_name as 'produkt',
 sum(oim.meta_value) as 'ilosc'
from ".$wpdb->prefix."posts p 
join ".$wpdb->prefix."woocommerce_order_items oi on p.ID = oi.order_id and oi.order_item_type = 'line_item'
join ".$wpdb->prefix."woocommerce_order_itemmeta as oim ON oi.order_item_id = oim.order_item_id and oim.meta_key = '_qty'
where p.post_type = 'shop_order' and p.post_status = 'wc-completed' and p.released = 0 group by oi.order_item_name 
order by oi.order_item_name";

	$sql_released = "
select 
 oi.order_item_name as 'produkt',
 sum(oim.meta_value) as 'ilosc'
from ".$wpdb->prefix."posts p 
join ".$wpdb->prefix."woocommerce_order_items oi on p.ID = oi.order_id and oi.order_item_type = 'line_item'
join ".$wpdb->prefix."woocommerce_order_itemmeta as oim ON oi.order_item_id = oim.order_item_id and oim.meta_key = '_qty'
where p.post_type = 'shop_order' and p.post_status = 'wc-completed' and p.released = 1 group by oi.order_item_name 
order by oi.order_item_name";
		
	$report = new Report();
	
	$report->waiting = getReport($sql_waiting);
	$report->released = getReport($sql_released);
	
	return wp_json_encode($report);
}

function getReport($sql) {
    global $wpdb;
	
	$reportData = $wpdb->get_results($sql, OBJECT);
	$reportList = array();
	
	if ($reportData)
	{		
		foreach ($reportData as $reportDataRow) 
		{ 
			$product = new Products();
			$product->name = $reportDataRow->produkt;
			$product->count = $reportDataRow->ilosc;
			
			$reportList[] = $product;
		} 
	}
	
	return $reportList;
}

echo db_raport_data();
