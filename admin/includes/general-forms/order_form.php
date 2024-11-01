<?php
require_once( USAM_FILE_PATH . '/includes/crm/contact_address_query.class.php' );	
require_once( USAM_FILE_PATH .'/includes/document/document_discounts_query.class.php' );
require_once( usam_get_admin_template_file_path( "table_products_order_form", 'table-form' ) );

class USAM_Order_Form
{
	protected $data = [];
	protected $list_table;
	public function __construct( &$data = [] ) 
	{ 		
		$data['coupon_name'] = (string)usam_get_order_metadata( $data['id'], 'coupon_name');
		$data['contact_type_price'] = usam_get_contact_metadata( $data['contact_id'], 'type_price');
		$data['coupon'] = null;
		if ( $data['coupon_name'] )
		{
			$data['coupon_effect_message'] = '';
			$data['coupon'] = usam_get_coupon( $data['coupon_name'], 'coupon_code' );
			if ( $data['coupon'] )
			{
				$data['coupon']['url'] = admin_url('admin.php?page=manage_discounts&tab=coupons&form=edit&form_name=coupon&id='.$data['coupon']['id']);
				if ( $data['coupon']['action'] == 'b' )
					$data['coupon_effect_message'] = " (".__('Изменить стоимость корзины','usam').")";
				elseif ( $data['coupon']['action'] == 's' )
					$data['coupon_effect_message'] = " (".__('Изменить стоимость доставки','usam').")";
			}
		}			
		$data['used_bonuses'] = usam_get_used_bonuses_order( $data['id'] );		
		$data['currency'] = usam_get_currency_sign_price_by_code( $data['type_price'] );
		$type_price = usam_get_setting_price_by_code( $data['type_price'] );		
		$data['rounding'] = isset($type_price['rounding']) ? $type_price['rounding'] : 2;
		$data['address_id'] = (int)usam_get_order_metadata( $data['id'], 'address' );	
		
		$data['product_taxes'] = usam_get_order_product_taxes( $data['id'] );
		foreach($data['product_taxes'] as $k => $product)
			$data['product_taxes'][$k]->name = stripcslashes($data['product_taxes'][$k]->name);
				
		if ( $data['id'] )
			$data['discounts'] = usam_get_document_discounts_query(['document_id' => $data['id'], 'document_type' => 'order']);
		else
			$data['discounts'] = [];
		$products = usam_get_products_order( $data['id'] );
		$post_ids = [];		
		foreach( $products as $product )
			$post_ids[] = $product->product_id;	
		if ( $post_ids )
			usam_get_products(['post__in' => $post_ids, 'update_post_meta_cache' => true, 'stocks_cache' => false, 'prices_cache' => false], true);	
		
		$this->list_table = new USAM_Table_Products_Order_Form( 'order', $data );	
		$user_columns = $this->list_table->get_user_columns();
		foreach($products as $k => $product)
		{			
			if( current_user_can( 'edit_order_contractor' ) )
				$products[$k]->contractor = (int)usam_get_product_meta( $product->product_id, 'contractor' );	
			$products[$k]->sku = usam_get_product_meta( $product->product_id, 'sku' );
			$products[$k]->small_image = usam_get_product_thumbnail_src($product->product_id);
			$products[$k]->url = get_permalink( $product->product_id );
			$products[$k]->name = stripcslashes($product->name);	
			$products[$k]->discounts = [];
			$products[$k]->formatted_bonus = usam_currency_display($product->bonus, ['decimal_point' => false]);
			$products[$k]->formatted_quantity = usam_get_formatted_quantity_product_unit_measure($product->quantity, $product->unit_measure);
			$products[$k]->quantity = usam_get_formatted_quantity_product($product->quantity, $product->unit_measure);
			$products[$k]->units = usam_get_product_property($product->product_id, 'units');			
			foreach($user_columns as $column)
			{
				$products[$k]->$column = usam_get_product_property($product->product_id, $column );	
			}
			if ( !empty($data['discounts']))
			{
				$url = admin_url('admin.php?page=manage_discounts&tab=discount&form=edit&form_name=product_discount');
				foreach ( $data['discounts'] as $discount )
				{
					if ( $discount->product_id == $product->product_id )				
						$products[$k]->discounts[] = ['name' => usam_get_discount_rule_name($discount, $data['type_price']), 'id' => $discount->id, 'url' => add_query_arg(['id' => $discount->rule_id], $url)];								
				}					
			}
		}		
		$data['products'] = $products;
		$this->data = $data;		
	}
	
	public function section_products( )
	{					
		$this->list_table->section_products();
		$this->list_table->select_product_buttons();
	}	
}