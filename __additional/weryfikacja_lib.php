<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/wp-config.php' );

function get_str_for_qr($orderId, $hash) {
	$order = wc_get_order( $orderId );

	// The loop to get the order items which are WC_Order_Item_Product objects since WC 3+
/*	foreach( $order->get_items() as $item_id => $item_product ){
		//Get the product ID
		$product_id = $item_product->get_product_id();
		//Get the WC_Product object
		$product = $item_product->get_product();
		// The product name
		$product_name = $order_item->get_name(); // … OR: $product->get_name();
		//Get the product SKU (using WC_Product method)
		$sku = $product->get_sku();
		
	} */
	$email = $order->get_billing_email();
	//$hash = base_convert($orderId*123465+8,10,32);
	update_post_meta($orderId, 'order_hash', $hash);
	update_post_meta($orderId, 'order_released', "0");	
	
	$str = 'mail='.$email.'&idOrder='.$orderId.'&hash='.$hash;
	wh_log($str);
	
	return $str;
}

function get_order_data_for_ticket($orderId) {
	return wc_get_order( $orderId );
}

function generate_qr_code($orderId, $hash) {
	ini_set('memory_limit', '-1');
	$are_virtual = false;
	$order = get_order_data_for_ticket($orderId);
	foreach( $order->get_items() as $item_id => $item_product ){
		$product = $item_product->get_product();
		if($product->is_virtual('yes')) {
			$are_virtual = true;
		}
	}
	
	wh_log('$are_virtual');
	
	if($are_virtual) {
		$str = get_str_for_qr($orderId, $hash);
		QRcode::png($str,$_SERVER['DOCUMENT_ROOT'].'/qr_tickets/'.$orderId.'.png', QR_ECLEVEL_L, 10);
		$png_image = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'].'/qr_tickets/ticket_template.png');
		$qr_image = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'].'/qr_tickets/'.$orderId.'.png');
		imagecopy($png_image, $qr_image, 1800, 750, 0, 0, 370, 370);
		
		$font = $_SERVER['DOCUMENT_ROOT'].'/qr_tickets/fonts/arial.ttf';
		$font_monospace = $_SERVER['DOCUMENT_ROOT'].'/qr_tickets/fonts/nk57-monospace-sc-sb.ttf';
		//$text_color = imagecolorallocate($png_image, 0, 0, 0);
		
		//dodajemy dane naglowka
		$txt_black = ImageColorAllocate ($png_image, 0, 0, 0);
		
		$orderHeaderMarginLeft = 150;
		$orderAddrStart = 915;
		$orderAddrLineHeight = 60;
		$orderAddrFontSize = 45;
		$orderItemFontSize = 35;
		$currentLine = $orderAddrStart;
		
		
		//hash pod kodem QR
		$hash_value = get_post_meta($orderId, 'order_hash', true);
		ImageTTFText ($png_image, 35, 0, 1835, 1170, $txt_black, $font, $hash_value);
		
		$email = $order->get_billing_email();
		$firstName = $order->get_billing_first_name();
		$lastName = $order->get_billing_last_name();
		$addrLine1 = $order->get_billing_address_1();
		$addrLine2 = $order->get_billing_address_2();
		$addrCity = $order->get_billing_city();
		$addrPostCode = $order->get_billing_postcode();
		$addrCountry = $order->get_billing_country();
		$phoneNumber = $order->get_billing_phone();
		
		$firstLastName = $firstName.' '.$lastName;
		$postCodeCity = $addrPostCode.' '.$addrCity;
		
		ImageTTFText ($png_image, $orderAddrFontSize, 0, $orderHeaderMarginLeft, $currentLine, $txt_black, $font, $firstLastName);
		$currentLine = $currentLine + $orderAddrLineHeight;
		ImageTTFText ($png_image, $orderAddrFontSize, 0, $orderHeaderMarginLeft, $currentLine, $txt_black, $font, $addrLine1);
		$currentLine = $currentLine + $orderAddrLineHeight;
		ImageTTFText ($png_image, $orderAddrFontSize, 0, $orderHeaderMarginLeft, $currentLine, $txt_black, $font, $postCodeCity);
		$currentLine = $currentLine + $orderAddrLineHeight;
		ImageTTFText ($png_image, $orderAddrFontSize, 0, $orderHeaderMarginLeft, $currentLine, $txt_black, $font, $email);
		$currentLine = $currentLine + $orderAddrLineHeight;
		ImageTTFText ($png_image, $orderAddrFontSize, 0, $orderHeaderMarginLeft, $currentLine, $txt_black, $font, $phoneNumber);
		//imagestring($png_image, 4, 5, 5,  $str, $text_color);
		
		
		//dodajemy tabelke do biletu
		$tableStart = 1300;
		$tableWidth = 2282;
		$tableMarginLeft = 100;
		imagesetthickness ($png_image, 3);
		$line1Y = $tableStart-35;
		ImageLine($png_image, $tableMarginLeft, $line1Y, $tableMarginLeft+$tableWidth, $line1Y, $line_color);
		ImageTTFText ($png_image, $orderAddrFontSize, 0, 151, $tableStart+25, $txt_black, $font, 'Lp');
		ImageTTFText ($png_image, $orderAddrFontSize, 0, 503, $tableStart+25, $txt_black, $font, 'Nazwa');
		ImageTTFText ($png_image, $orderAddrFontSize, 0, 1780, $tableStart+25, $txt_black, $font, 'Ilość');
		ImageTTFText ($png_image, $orderAddrFontSize, 0, 1975, $tableStart+25, $txt_black, $font, 'Cena');
		ImageTTFText ($png_image, $orderAddrFontSize, 0, 2147, $tableStart+25, $txt_black, $font, 'Wartość');
		$line2Y = $tableStart+50;
		ImageLine($png_image, $tableMarginLeft, $line2Y, $tableMarginLeft+$tableWidth, $line2Y, $line_color);
		
		$lp = 1;
		$positionsStart = $line2Y + 90;
		$currentPosLine = $positionsStart;
		$positionLineHeight = 70;
		
		$sumQuantity = 0;
		$sumValue = 0;
		$isStar = false;
		$starDesc = "";
		foreach( $order->get_items() as $item_id => $item_product ){
			$product = $item_product->get_product();
			if($product->is_virtual('yes')) {
				$product_name = $product->get_name();
				$product_quantity = $item_product->get_quantity();
				$product_price = $product->get_price();
				$product_value = $item_product->get_total();
				
				//pobieramy atryb dla produktow wirtualnych. Jeśli not null - dodajemy "gwiazdke" na dole
				$starLocal = $product->get_attribute('Zestaw');
				if(!(trim($starLocal) === '')) {
					$isStar = true;
					$starDesc = $starLocal;
				}
				
				ImageTTFText ($png_image, $orderItemFontSize, 0, 153, $currentPosLine, $txt_black, $font_monospace, $lp);
				ImageTTFText ($png_image, $orderItemFontSize, 0, 250, $currentPosLine, $txt_black, $font_monospace, $product_name);
				
				//ffs, ręcznie robili align right
				$dimensions = imagettfbbox($orderItemFontSize, 0, $font_monospace, $product_value);
				$textWidth = abs($dimensions[4] - $dimensions[0]);
				$xValue = 2315 - $textWidth;
				
				$dimensions = imagettfbbox($orderItemFontSize, 0, $font_monospace, $product_quantity);
				$textWidth = abs($dimensions[4] - $dimensions[0]);
				$xQuantity = 1835 - $textWidth;
				
				$dimensions = imagettfbbox($orderItemFontSize, 0, $font_monospace, $product_price);
				$textWidth = abs($dimensions[4] - $dimensions[0]);
				$xPrice = 2098 - $textWidth;
				
				$sumQuantity = $sumQuantity + $product_quantity;
				$sumValue = $sumValue + $product_value;
				
				ImageTTFText ($png_image, $orderItemFontSize, 0, $xQuantity, $currentPosLine, $txt_black, $font_monospace, $product_quantity);
				ImageTTFText ($png_image, $orderItemFontSize, 0, $xPrice, $currentPosLine, $txt_black, $font_monospace, $product_price);
				ImageTTFText ($png_image, $orderItemFontSize, 0, $xValue, $currentPosLine, $txt_black, $font_monospace, $product_value);
				$currentPosLine = $currentPosLine + $positionLineHeight;
				$lp++;
			}
		}
		$line3Y = $currentPosLine-15;
		ImageLine($png_image, $tableMarginLeft, $line3Y, $tableMarginLeft+$tableWidth, $line3Y, $line_color);
		ImageTTFText ($png_image, $orderItemFontSize, 0, 250, $line3Y+80, $txt_black, $font_monospace, 'Suma');
		
		$dimensions = imagettfbbox($orderItemFontSize, 0, $font_monospace, $sumValue);
		$textWidth = abs($dimensions[4] - $dimensions[0]);
		$xValue = 2315 - $textWidth;
			
		$dimensions = imagettfbbox($orderItemFontSize, 0, $font_monospace, $sumQuantity);
		$textWidth = abs($dimensions[4] - $dimensions[0]);
		$xQuantity = 1835 - $textWidth;
			
		ImageTTFText ($png_image, $orderItemFontSize, 0, $xQuantity, $line3Y+80, $txt_black, $font_monospace, $sumQuantity);
		ImageTTFText ($png_image, $orderItemFontSize, 0, $xValue, $line3Y+80, $txt_black, $font_monospace, $sumValue);
		
		//gwiazdka
		if($isStar) {
			ImageTTFText ($png_image, 32, 0, $tableMarginLeft, 2300, $txt_black, $font, '*) - '.$starDesc);		
		}
		
		$fileToAttach = $_SERVER['DOCUMENT_ROOT'].'/qr_tickets/'.$hash_value.'.jpg';
		//imagepng($png_image, $fileToAttach);
		
		
		$bg = imagecreatetruecolor(imagesx($png_image), imagesy($png_image));
		imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
		imagealphablending($bg, TRUE);
		imagecopy($bg, $png_image, 0, 0, 0, 0, imagesx($png_image), imagesy($png_image));
		$quality = 80; // 0 = worst / smaller file, 100 = better / bigger file 
		//$imageResized = imagescale($bg, 1370, 1920);
		imagejpeg($bg, $fileToAttach, $quality);
		imagedestroy($bg);
		imagedestroy($png_image);
		imagedestroy($qr_image);
		unlink($_SERVER['DOCUMENT_ROOT'].'/qr_tickets/'.$orderId.'.png');
		return $fileToAttach;	
	}
}
