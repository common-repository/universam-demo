<?php
$sql =  "	
	CREATE PROCEDURE calculate_order_to_supplier( IN day INT,IN supplier INT ) 
	LANGUAGE SQL 
	DETERMINISTIC 
	SQL SECURITY DEFINER 					
	BEGIN 
		DECLARE meta_value longtext;
		SELECT data.product_id AS product_id, AVG(data.quantity_sold) AS quantity_sold, AVG(data.stock)-pmeta.meta_value AS stock FROM `".USAM_TABLE_DATA_ORDER_PRODUCTS."` AS data INNER JOIN `".$wpdb->postmeta."` AS pmeta ON ( pmeta.post_id=data.product_id AND pmeta.meta_key=".USAM_META_PREFIX."stock ) WHERE AVG(data.stock) > pmeta.meta_value AND TO_DAYS(NOW()) - TO_DAYS(data.date_insert) <= day GROUP BY data.product_id; 
		
		
		
		
		
		"; 
		foreach( $this->p_data['meta'] as $meta_key => $meta_value)
		{
			$usam_key = USAM_META_PREFIX.$meta_key;
			$value = maybe_serialize( $meta_value );					
			$sql .= " CASE ";
			$sql .= " WHEN EXISTS (SELECT meta_value FROM `".$wpdb->postmeta."` WHERE post_id='$this->product_id' AND meta_key='$usam_key' LIMIT 1) THEN  ";
			$sql .= " UPDATE `".$wpdb->postmeta."` SET meta_value='$value' WHERE `meta_key`='$usam_key' AND `post_id`=$this->product_id; ";		
			$sql .= " ELSE "; 
			$sql .= " INSERT INTO `".$wpdb->postmeta."` (`meta_value`, `meta_key`, `post_id`) VALUES ('$value', '$usam_key', $this->product_id); ";
			$sql .= " END CASE; "; 
		}
		$sql .= "						
	END;
	CREATE PROCEDURE ordering_products_to_store( IN day INT,IN storageid INT ) 
	LANGUAGE SQL 
	DETERMINISTIC 
	SQL SECURITY DEFINER 					
	BEGIN 
		DECLARE meta_value longtext;
		SELECT product_id, AVG(quantity_sold) AS quantity_sold, AVG(stock) AS stock FROM ".USAM_TABLE_STOCK_MANAGEMENT_DATA." WHERE storage_id = storageid AND TO_DAYS(NOW()) - TO_DAYS(date_insert) <= day GROUP BY product_id; 						
	END;
	";						
$query = $wpdb->query( $sql );
//$query = $wpdb->query( "CALL usam_update_product_metas_{$this->product_id}(); " );	









//SELECT data.product_id AS product_id, AVG(data.quantity_sold) AS quantity_sold, AVG(data.stock)-pmeta.meta_value AS stock FROM `".USAM_TABLE_DATA_ORDER_PRODUCTS."` AS data INNER JOIN `".$wpdb->postmeta."` AS pmeta ON ( pmeta.post_id=data.product_id AND pmeta.meta_key=".USAM_META_PREFIX."stock ) WHERE AVG(data.stock) > pmeta.meta_value AND TO_DAYS(NOW()) - TO_DAYS(data.date_insert) <= 365+day GROUP BY data.product_id; 
?>