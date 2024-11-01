<?php
class USAM_Order_Importer
{	
	private $rule;
	private $data;
	private $add = 0;
	private $update = 0;
	
	public function __construct( $id ) 
	{			
		if ( is_array($id) )
			$this->rule = $id;
		else
		{
			require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
			$this->rule = usam_get_exchange_rule( $id );
			$metas = usam_get_exchange_rule_metadata( $id );
			foreach($metas as $metadata )
				$this->rule[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
		}
	}
	
	public function start( $data ) 
	{			
		if ( empty($data) ) 
		{			
			return false;
		}	
		$this->data = $data;
				
		add_filter( 'block_local_requests', '__return_false' );
		add_filter( 'https_ssl_verify', '__return_false' );
			
		add_filter( 'block_local_requests', '__return_false' );				
		$anonymous_function = function($is, $host, $url) { return true; };	
		add_filter( 'http_request_host_is_external', $anonymous_function, 10, 3 );
		
		$anonymous_function = function($r, $url) { 
			$r['user-agent'] = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0';
			return $r;
		};	
		add_filter( 'http_request_args', $anonymous_function, 10, 2 );
		add_filter( 'usam_prevent_notification_change_status', '__return_false' );	
		
		usam_update_object_count_status( false );		
		
		$this->add = 0;
		$this->update = 0;
		$records = $this->import();	

		usam_update_object_count_status( true );			
		
		return ['add' => $this->add, 'update' => $this->update, 'records' => $records];
	}

	private function import( ) 
	{						
		$properties = usam_get_properties( array( 'type' => 'order', 'active' => 1, 'fields' => 'code=>data' ) );		
		if (isset($this->data[0]['document_id']))
			$primary_key = 'document_id';
		elseif (isset($this->data[0]['number']))
			$primary_key = 'number';
		else
			$primary_key = '';			
		$orders = array();	
		foreach($this->data as $number => $row)
		{							
			if ( $primary_key && isset($row[$primary_key]) )
				$orders[$row[$primary_key]][] = $row;
			else						
				$orders[] = array( $row );
			unset($this->data[$number]);
		}
		$this->data = $orders;
		unset($orders);	
		$i = 0;
		$start_time = time();
		foreach( $this->data as $key => $order )
		{	
			$products = array();			
			if (!empty($order[0]['poduct_id']))
				$product_primary_key = 'poduct_id';
			elseif (!empty($order[0]['sku']))
				$product_primary_key = 'sku';	
			elseif (!empty($order[0]['sku']))
				$product_primary_key = 'code';	
			elseif (!empty($order[0]['barcode']))
				$product_primary_key = 'barcode';				
			else
				$product_primary_key = '';	
			if ( $product_primary_key )
			{
				foreach ( $order as $product )
				{
					$product_id = isset($product['product_id'])?$product['product_id']:usam_get_product_id_by_meta( $product_primary_key, $product[$product_primary_key] );	
					if( $product_id )
					{
						$name = isset($product['name'])?$product['name']:get_the_title($product_id);
						$quantity = isset($product['quantity'])?$product['quantity']:1;
						$price = isset($product['price'])?$product['price']:usam_get_product_price( $product_id );
						$products[] = array( 'product_id' => $product_id, 'price' => $price, 'quantity' => $quantity, 'name' => $name );
					}
				}
			}	
			$order_id = $this->insert( $order[0], $products );
			if ( $order_id )
			{														
				usam_add_order_customerdata( $order_id, $order[0] );
				do_action('usam_document_order_save', $order_id);
			}
			unset($this->data[$key]);
			$i = $number+1;
			if ( $this->rule['max_time'] < time() - $start_time )
				break;	
		}	
		return $i;		
	}	

	private function insert( $new_data, $products )
	{	
		global $wpdb;		
		if ( empty($new_data) )
			return false;
		$order_id = false;	
		if ( !empty($new_data['code']) )
		{		
			$order_id = $wpdb->get_var("SELECT id FROM ".USAM_TABLE_ORDERS." WHERE code='".$new_data['code']."'" );
			if ( $this->check_wpdb_error() )
				return false;
		}
		if ( $order_id )
		{
			$purchase_log = new USAM_Order( $order_id );
			$purchase_log->set( $new_data );
			if ( $purchase_log->save() )
			{
				$this->update++;
			}
		}
		else
		{			
			$purchase_log = new USAM_Order( $new_data );	
			if ( $purchase_log->save() )
			{
				$purchase_log->add_products( $products );
				$order_id = $purchase_log->get('id');	
				$this->add++;					
			}			
		}		
		return $order_id;		
	}	

	function check_wpdb_error() 
	{
		global $wpdb;
		if ( !$wpdb->last_error ) 
			return false;
		return true;
	}		
}
?>