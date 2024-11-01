<?php
/**
 * Экспорт заказов
 */
require_once( USAM_FILE_PATH . '/includes/exchange/exporter.class.php');
class USAM_Order_Exporter extends USAM_Exporter
{	
	private $product_columns;
	private $product_column = false;	
	private $product_attributes = array();	
	
	public function get_args( ) 
	{
		$args = ['order' => $this->rule['order'], 'orderby' => $this->rule['orderby'], 'paged' => $this->paged, 'number' => $this->number, 'meta_query' => [], 'date_query' => []];		
		if ( !empty($this->rule['groups']) )
			$args['group'] = $this->rule['groups'];
		if ( !empty($this->rule['status']) )
			$args['status'] = $this->rule['status'];
		if ( !empty($this->rule['source']) )
			$args['source'] = $this->rule['source'];			
		if ( !empty($this->rule['location']) )
			$args['meta_query'][] = ['key' => 'location','value' => $this->rule['location'], 'compare' => '='];		
		
		if( !empty($this->rule['from_dateinsert']) )
			$args['date_query'][] = ['after' => date('Y-m-d H:i:s', strtotime($this->rule['from_dateinsert'])), 'inclusive' => true];	
		if( !empty($this->rule['to_dateinsert']) )
			$args['date_query'][] = ['before' => date('Y-m-d H:i:s', strtotime($this->rule['to_dateinsert'])), 'inclusive' => true];
		$args['conditions'] = array();		
		if ( !empty($this->rule['to_ordersum']) )
		{			
			$args['conditions'][] = ['key' => 'totalprice', 'value' => $this->rule['to_ordersum'], 'compare' => '<='];	
		}
		if ( !empty($this->rule['from_ordersum']) )
		{			
			$args['conditions'][] = ['key' => 'totalprice', 'value' => $this->rule['from_ordersum'], 'compare' => '>='];	
		}
		if ( !empty($this->rule['to_ordercount']) )
		{			
			$args['conditions'][] = ['key' => 'number_products', 'value' => $this->rule['to_ordercount'], 'compare' => '<='];	
		}
		if ( !empty($this->rule['from_ordercount']) )
		{			
			$args['conditions'][] = ['key' => 'number_products', 'value' => $this->rule['from_ordercount'], 'compare' => '>='];	
		}
		if ( !empty($this->rule['to_id']) )
		{			
			$args['conditions'][] = ['key' => 'id', 'value' => $this->rule['to_id'], 'compare' => '<='];	
		}
		if ( !empty($this->rule['from_id']) )
		{			
			$args['conditions'][] = ['key' => 'id', 'value' => $this->rule['from_id'], 'compare' => '>='];	
		}
		return $args;
	}
	
	public function get_total( ) 
	{
		$args = $this->get_args();	
		unset($args['number']);		
		return usam_get_orders( $args );	
	}
			
	protected function get_data( $args = [] ) 
	{			
		if ( !$args )
			$args = $this->get_args();
			
		$this->properties = usam_get_properties(['type' => 'order', 'active' => 1, 'fields' => 'code=>data']);	
		$this->product_columns = ['poduct_id','name','sku','code','weight','weight_unit','price', 'barcode', 'old_price','discount','length','width','height','unit', 'unit_measure', 'quantity', 'contractor'];
		$product_attribute_cache = false;
		foreach ( $this->rule['columns'] as $column => $title )
		{
			if ( in_array($column,$this->product_columns) )
			{
				$this->product_column = true;
				$args['products_cache'] = true;
			}
			if ( stripos($column, 'attribute_') !== false)			
				$product_attribute_cache = true;
		}
		foreach( $this->properties as $code => $property )
		{
			if ( isset($this->rule['columns'][$code]) )
			{				
				$args['cache_meta'] = true; 
				break;
			}			
		}		
		if ( $product_attribute_cache )
			$this->product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'sort', 'taxonomy' => 'usam-product_attributes']);			
	
		$orders = usam_get_orders( $args );	
		
		$output	= array();			
		foreach ( $orders as $data )
		{			
			$export = $this->get_column( $data );
			$output = array_merge($output, $export);		
			
			wp_cache_delete( $data->id, 'usam_order' );
			wp_cache_delete( $data->id, 'usam_order_meta' );
			wp_cache_delete( $data->id, 'usam_order_code' );			
			wp_cache_delete( $data->id, 'usam_products_order' );
			wp_cache_delete( $data->id, 'usam_shipped_documents_order' );
			wp_cache_delete( $data->id, 'usam_payment_order' );
			wp_cache_delete( $data->id, 'usam_properties_order' );
		}
		return $output;		
	}
	
	protected function get_column( $order ) 
	{ 
		$results = array();			
		foreach ( $order as $key => $value )	
		{
			if ( isset($this->rule['columns'][$key]) )
				$results[$key] = $value;
		}
		if ( !empty($this->rule['columns']['note']) )
		{
			$results['note'] = str_replace(array("\r\n","\r","\n"),"", nl2br(usam_get_order_metadata($order->id, 'note')));
		}		
		if ( !empty($this->rule['columns']['document_id']) )
		{
			$results['document_id'] = $order->id; 
		}			
		if ( !empty($this->rule['columns']['nds']) )
		{
			$tax = usam_get_tax_amount_order( $order->id );
			$results['nds'] = $tax == 0 ? __("Без НДС","usam") : $tax; 
		}			
		if ( !empty($this->rule['columns']['manager']) )
		{
			$results['manager'] = usam_get_manager_name( $order->manager_id  ); 
		}		
		if ( !empty($this->rule['columns']['bank_account']) )
		{
			$bank_account = usam_get_bank_account( $order->bank_account_id );
			$results['bank_account'] = $bank_account['number'];	
		}	
		if ( !empty($this->rule['columns']['currency']) )
		{
			$results['currency'] = usam_get_currency_price_by_code( $order->type_price );	
		}							
		foreach( $this->properties as $code => $property )
		{
			if ( isset($this->rule['columns'][$code]) )
			{				
				$single = $property->field_type == 'checkbox'?false:true;
				$value = usam_get_order_metadata( $order->id, $code, $single );	
				if ( $property->field_type == 'checkbox' )
					$value = implode(", ", (array)$value);
				$results[$code] = $value;
			}			
		}		
		return $this->get_product_column( $order, $results );
	}
	
	private function get_product_column( $order, $order_export ) 
	{					
		$products = usam_get_products_order( $order->id );   
		$results = array();
		if ( !empty($products)  )	
		{	
			foreach( $products as $product )		
			{ 	
				$product_export = $order_export;
				foreach($this->product_columns as $column )	
				{
					if ( isset($this->rule['columns'][$column]) )
					{	
						if ( isset($product->$column) )
							$product_export[$column] = $product->$column;
						else
							$product_export[$column] = usam_get_product_property( $product->product_id, $column );						
					}	
				}
				foreach($this->product_attributes as $term)
				{ 
					if ( $term->parent != 0 )
					{				
						if ( isset($this->rule['columns']['attribute_'.$term->term_id]) )
						{ 
							if ( usam_attribute_stores_values( $term->term_id ) )
							{
								$attribute_values = usam_get_attribute_values( $term->term_id );
								$metas = usam_get_product_attribute($product->product_id, $term->slug, false );
								$attributes = [];
								if ( !empty($metas) )
								{									
									foreach( $metas as $meta )
									{
										$ok = true;
										foreach( $attribute_values as $option )
										{
											if ( $option->id == $meta->meta_value )	
											{
												$attributes[] = $option->value;
												$ok = false;
												break;
											}
										}
										if ( $ok && !is_numeric($meta->meta_value) )
											$attributes[] = $meta->meta_value;
									}
								}
								$attribute = implode($this->rule['splitting_array'],$attributes);
							}
							else
								$attribute = usam_get_product_attribute($product->product_id, $term->slug );							
							$product_export['attribute_'.$term->term_id] = $attribute;
						}
					}			
				}
				$results[] = $product_export;			
			}
		}
		else
		{
			foreach($this->product_columns as $column )		
			{		
				if ( isset($this->rule['columns'][$column]) )
					$order_export[$column] = '';					
			}
			foreach($this->product_attributes as $term)
			{		
				if ( isset($this->rule['columns']['attribute_'.$term->term_id]) )
					$order_export[$column] = '';					
			}
			$results[] = $order_export;				
		} 	
		return $results;
	}
}
?>