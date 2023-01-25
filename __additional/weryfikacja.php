<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/wp-config.php' );
include_once($_SERVER['DOCUMENT_ROOT'].'/weryfikacja_lib.php' );
include_once($_SERVER['DOCUMENT_ROOT'].'/authorization.php' );

$content = trim(file_get_contents("php://input"));
$decoded = json_decode($content, true);

class Order
{
    var $id;
    var $action;
    var $firstName;
    var $lastName;
    var $mail;
    var $phone;
    var $released;
    var $products;    
    var $comments;    
}

class Product
{
    var $name;
    var $count;
    var $virtual;
    var $value;
}
class Comment
{
    var $content;
    var $author;
    var $date;
}

function get_order_data($action = null, $mail = null, $idOrder = null, $hash) {
	global $wpdb;
	 
	//echo $mail.":".$idOrder.":".$hash;
	
	$sql = "
		select 
			p.id
		from ".$wpdb->prefix."posts p 
		join ".$wpdb->prefix."postmeta pm1 on p.ID = pm1.post_id and pm1.meta_key = '_billing_email'
		join ".$wpdb->prefix."postmeta pm2 on p.ID = pm2.post_id and pm2.meta_key = 'order_hash'
			where p.post_type = 'shop_order' and p.post_status = 'wc-completed'
				and UPPER(pm2.meta_value) = UPPER('".$hash."')";
		 
	if (!empty($mail)) {
		$sql .= " and pm1.meta_value = '".$mail."'";
	}	
	if (!empty($idOrder)) 
	{
		$sql .= " and p.id = ".$idOrder;		
	}
	//echo $sql;
	$orderData = $wpdb->get_results($sql, OBJECT);
	if ($orderData){
		return wc_get_order( $orderData[0]->id );
	}
	header('Location: /index.php?controller=404');
	die;
}

function get_comments_data($idOrder = null) {
	global $wpdb;
	 	
	$sql = "
		select 
		 c.comment_author as 'autor',
		 c.comment_content as 'komentarz',
		 c.comment_date as 'data'
		from ".$wpdb->prefix."comments c 
		where c.comment_post_ID = ".$idOrder." and c.comment_type = 'order_note'
		order by c.comment_date desc";
		
		//where c.comment_post_ID = ".$idOrder." and c.comment_type = 'order_note' and c.comment_author <> 'WooCommerce'
	
	$commentsData = $wpdb->get_results($sql, OBJECT);
	
	$comments = array();
	foreach ($commentsData as $commentsDataRow) 
	{ 		
		$comment = new Comment();
		$comment->content = $commentsDataRow->komentarz;
		$comment->author = $commentsDataRow->autor;
		$comment->date = $commentsDataRow->data;
		
		$comments[] = $comment;
	}
	
	return $comments;
}

function db_data($action, $mail, $idOrder, $hash) {
	$orderData = get_order_data($action, $mail, $idOrder, $hash);
	if ($orderData){
		$order_meta = get_post_meta($orderData->id);
		
		$order = new Order();
		$product = new Product();
		$comment = new Comment();
		
		$order->action = $action;
		$order->id = $orderData->id;
		$order->firstName = $orderData->get_billing_first_name();
		$order->lastName = $orderData->get_billing_last_name();
		$order->mail = $orderData->get_billing_email();
		$order->phone = $orderData->get_billing_phone();
		$order->released = $order_meta['order_released'][0];
	
		$products = array();
		foreach( $orderData->get_items() as $items => $item_product ){
			$productData = $item_product->get_product();
			
			$product = new Product();			
			$product->name = $productData->get_name();
			$product->count = $item_product->get_quantity();
			$product->value = $item_product->get_total();
			$product->virtual = $productData->is_virtual('yes');
			
			$products[] = $product;
		}
		$order->products = $products;
		$order->comments = get_comments_data($orderData->id);		
	}
	return $order;
}

$order = db_data($decoded['action'], $decoded['mail'], $decoded['idOrder'], $decoded['hash']);

echo wp_json_encode($order);
