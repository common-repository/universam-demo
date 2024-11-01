<?php
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_cart extends USAM_View_Form
{	
	protected function get_title_tab()
	{ 			
		return sprintf( __('Корзина № %s', 'usam'), $this->data['id'] );
	}
	
	protected function get_data_tab()
	{ 	
		global $wpdb;	
		$this->data = $wpdb->get_row( "SELECT * FROM ".USAM_TABLE_USERS_BASKET." WHERE id ='$this->id'", ARRAY_A );
		$this->tabs = [	
			['slug' => 'products', 'title' => __('Товары','usam')],		
		];		
	}

	protected function toolbar_buttons( ) 
	{
		if ( $this->id != null )
		{
			$this->newsletter_templates( 'contact', $this->data['contact_id'] );
			$emails = usam_get_contact_emails( $this->data['contact_id'] );	
			?><div class="action_buttons__button"><a class="button js-open-message-send" data-emails='<?php echo implode(",",$emails); ?>'><?php _e('Письмо','usam'); ?></a></div><?php		
			parent::toolbar_buttons();			
		}
	}	
		
	protected function main_content_cell_1( ) 
	{
		?>							
		<div class="view_data">					
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php esc_html_e( 'Сумма корзины', 'usam'); ?>:</label></div>
				<div class ="view_data__option">
					<?php echo usam_get_formatted_price( $this->data['totalprice'] ); ?>
				</div>
			</div>	
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php esc_html_e( 'Количество товаров', 'usam'); ?>:</label></div>
				<div class ="view_data__option">
					<?php echo usam_currency_display( $this->data['quantity'], ['currency_symbol' => false, 'decimal_point' => false, 'currency_code' => false]); ?>
				</div>
			</div>	
			<?php
			if ( $this->data['shipping_method'] )
			{
				$service = usam_get_delivery_service( $this->data['shipping_method'] );
				if ( $service ) {
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Выбранный способ доставки', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $service['name']; ?></div>
				</div>	
			<?php } } ?>
			<?php
			if ( $this->data['coupon_name'] )
			{
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Использованный купон', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $this->data['coupon_name']; ?></div>
				</div>	
			<?php }
			if ( $this->data['bonuses'] )
			{
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Использованные бонусы', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $this->data['bonuses']; ?></div>
				</div>	
			<?php } ?>	
		</div>	
		<?php	
	}	
	
	protected function main_content_cell_2( ) 
	{ 		
		?>		
		<div class="view_data">		
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php _e('Клиент', 'usam'); ?>:</label></div>
				<div class ="view_data__option">
					<?php $contact = usam_get_contact( $this->data['contact_id'] );	?>
					<?php if ( $contact ) { ?>
						<a href='<?php echo usam_get_contact_url( $this->data['contact_id'] ); ?>'><?php echo $contact['appeal']; ?></a>
					<?php } ?>
				</div>
			</div>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Корзина создана', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $this->data['date_insert'] ); ?></div>
			</div>			
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Корзина обновлена', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $this->data['recalculation_date'] ); ?></div>
			</div>						
		</div>	
		<?php
	}

	function display_tab_products()
	{
		$this->list_table( 'cart_products' );			
	}
}