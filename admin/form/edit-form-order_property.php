<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-property.php' );
class USAM_Form_order_property extends USAM_Form_Property
{	
	protected $property_type = 'order';
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить свойство &#171;%s&#187;','usam'), $this->data['name'] );
		else
			$title = __('Добавить свойство заказа', 'usam');			
		return $title;
	}
	
	public function display_property_connection( )
	{					
		$payer = usam_get_property_metadata($this->id, 'payer');
		$payer_address = usam_get_property_metadata($this->id, 'payer_address');
		$delivery_contact = usam_get_property_metadata($this->id, 'delivery_contact');
		$delivery_address = usam_get_property_metadata($this->id, 'delivery_address');		
		?>	
		<div class="edit_form" >
			<?php $this->display_connection(); ?>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='order_field_payer'><?php esc_html_e( 'Плательщик', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name = "payer" id="order_field_payer">	
						<option value="0" <?php selected( $payer, 0 ) ?> ><?php esc_html_e( 'Не содержит', 'usam'); ?></option>
						<option value="1" <?php selected( $payer, 1 ) ?> ><?php esc_html_e( 'Содержит', 'usam'); ?></option>
				</select>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='order_field_payer_address'><?php esc_html_e( 'Адрес плательщика', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name = "payer_address" id="order_field_payer_address">					
						<option value="0" <?php selected( $payer_address, 0 ) ?> ><?php esc_html_e( 'Не содержит', 'usam'); ?></option>
						<option value="1" <?php selected( $payer_address, 1 ) ?> ><?php esc_html_e( 'Содержит', 'usam'); ?></option>
				</select>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='order_field_delivery_contact'><?php esc_html_e( 'Контактное лицо для доставки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name = "delivery_contact" id="order_field_delivery_contact">			
						<option value="0" <?php selected( $delivery_contact, 0 ) ?> ><?php esc_html_e( 'Не содержит', 'usam'); ?></option>
						<option value="1" <?php selected( $delivery_contact, 1 ) ?> ><?php esc_html_e( 'Содержит', 'usam'); ?></option>
				</select>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='order_field_delivery_address'><?php esc_html_e( 'Адрес доставки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name = "delivery_address" id="order_field_delivery_address">		
						<option value="0" <?php selected( $delivery_address, 0 ) ?> ><?php esc_html_e( 'Не содержит', 'usam'); ?></option>
						<option value="1" <?php selected( $delivery_address, 1 ) ?> ><?php esc_html_e( 'Содержит', 'usam'); ?></option>
				</select>	
				</div>
			</div>
		</div>
      <?php
	}   
	
	public function display_conditions( )
	{	
		$shippings = usam_get_array_metadata($this->id, 'property', 'shipping');		
		$delivery_option = usam_get_array_metadata($this->id, 'property', 'delivery_option');
		$category = usam_get_array_metadata($this->id, 'property', 'category');
		
		$args = ['selected_shipping' => $shippings, 'delivery_option' => $delivery_option, 'category' => $category];
		$select_products = get_option('usam_types_products_sold', array( 'product', 'services' ));
		if ( count($select_products) > 1 )
			$args['types_products'] = usam_get_array_metadata($this->id, 'property', 'types_products');
				
		$this->checklist_meta_boxs( $args );
	}
	
	function display_left()
	{						
		$this->titlediv( $this->data['name'] );	
		usam_add_box( 'usam_order_property_settings', __('Параметры','usam'), array( $this, 'display_settings' ) );
		usam_add_box( 'usam_data_type', __('Тип данных','usam'), array( $this, 'display_data_type' ) );	  						
		usam_add_box( 'usam_property_connection', __('Связь между свойствами','usam'), array( $this, 'display_property_connection' ) );		
		usam_add_box( 'usam_conditions', __('Ограничения отображения','usam'), array( $this, 'display_conditions' ) );	
    }	
}
?>