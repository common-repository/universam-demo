<?php
require_once( USAM_FILE_PATH . '/admin/includes/map_view.class.php' );
require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
class USAM_delivery_documents_Map_View extends USAM_Map_View
{		
	public function prepare_items( ) 
	{		
		$this->query_vars = array();
		if ( !empty($_REQUEST['s']) )
			$this->query_vars['search'] = trim(stripslashes($_REQUEST['s'])); 
		elseif ( !empty($_REQUEST['search']) )
			$this->query_vars['search'] = trim(stripslashes($_REQUEST['search'])); 		
		$this->query_vars['status__not_in'] = array( 'canceled', 'shipped' );
		$this->query_vars['cache_meta'] = true;
		$this->query_vars['storage_pickup'] = 0;
		$this->query_vars['cache_order_meta'] = true;		
		$this->query_vars['conditions'] = ['key' => 'order_id', 'compare' => '>', 'value' => 0];		
		if ( empty($this->query_vars['search']) && empty($this->query_vars['include']) )
		{		
			$selected = $this->get_filter_value( 'status' );
			if ( $selected )
				$this->query_vars['status'] = sanitize_title($selected);	
						
			$selected = $this->get_filter_value( 'method' );
			if ( $selected )
				$this->query_vars['method'] = $selected;
			
			$selected = $this->get_filter_value( 'storage' );
			if ( $selected )
				$this->query_vars['storage'] = $selected;	
			
			$selected = $this->get_filter_value( 'storage_pickup' );
			if ( $selected )
				$this->query_vars['storage_pickup'] = $selected;	
			
			$selected = $this->get_filter_value( 'export' );
			if ( $selected )
			{
				foreach ( $selected as $result ) 
				{
					if ( $result === '0' )
						$this->query_vars['meta_query'] = ['relation' => 'OR', ['key' => 'exchange', 'compare' => "NOT EXISTS"], ['key' => 'exchange','value' => 0, 'compare' => '=']];		
					else
						$this->query_vars['meta_query'] = [['key' => 'exchange','value' => 1, 'compare' => '=']];		
				}
			}	
			$this->get_digital_interval_for_query( array('price' ) );		
		}
		$points = array();
		$shipping_documents = usam_get_shipping_documents( $this->query_vars );
		if ( empty($shipping_documents) )
			return $points;		
		
		foreach ( $shipping_documents as $document ) 
		{
			$url = admin_url("admin.php?page=delivery&form=edit&form_name=shipped&id=".$document->id);
			$latitude = (string)usam_get_order_metadata( $document->order_id, 'latitude' );
			$longitude = (string)usam_get_order_metadata( $document->order_id, 'longitude' );			
			
			if ( $latitude )
			{				
				$property_types = usam_get_order_property_types( $document->order_id );				
				$address = !empty($property_types['delivery_address'])?$property_types['delivery_address']['_name']:'';	
				$contact = !empty($property_types['delivery_contact'])?$property_types['delivery_contact']['_name']:'';		

				$date_delivery = usam_get_shipped_document_metadata( $document->id, 'date_delivery' );
				$time = $date_delivery ? "<div class='map__pointer_date'>".esc_html__('Время доставки', 'usam').': '.get_date_from_gmt($date_delivery, "H:i")."</div>":'';
				$totalprice = $document->totalprice?usam_currency_display($document->totalprice):'';
							
				$note = usam_get_document_metadata($document->id, 'note');
				$points[] = array( 'id' => $document->id, 'title' => '№'.$document->id.' '.$totalprice, 'description' => "<div class='map__pointer'><div class='map__pointer_text'><div class='map__pointer_name'><a href='$url'><span>№ $document->id</span><span>".$totalprice."</span></a></div><div class='map__pointer_notes'>".esc_html( usam_limit_words($note))."</div>$time<div class='map__pointer_row'>$contact</div><div class='map__pointer_address'>$address</div></div></div>", 'latitude' => $latitude, 'longitude' => $longitude );	
			}
		}		
		return $points;
	}
}
?>