<?php	
require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php'  );
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_subscription extends USAM_View_Form
{	
	protected $ribbon = true;
	protected function get_title_tab()
	{ 	
		$to_date_timestamp = strtotime( $this->data['end_date'] );	
		$timestamp = time();
		if ( $to_date_timestamp < $timestamp )
			return sprintf( __('Подписка №%d (закончилась %s )', 'usam'), $this->data['id'], human_time_diff( $to_date_timestamp, $timestamp ) );
		return sprintf( __('Подписка №%d', 'usam'), $this->data['id'] );
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}	
	
	protected function get_toolbar_buttons( ) 
	{
		if ( current_user_can('edit_subscription') )
		{
			return [
				['vue' => ['@click="renew"'], 'name' => __('Продлить','usam'), 'display' => 'all'], 
			];
		}
		return [];
	}
	
	protected function get_data_tab()
	{ 	
		$this->data = usam_get_subscription( $this->id );	
		if ( !$this->data )
			return false;
		$metas = usam_get_subscription_metadata( $this->id );
		foreach($metas as $metadata )
			$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);			
		$this->data['products'] = usam_get_products_subscription( $this->data['id'] );
		foreach($this->data['products'] as $k => $product)
		{
			$this->data['products'][$k]->name = stripcslashes($product->name);
			$this->data['products'][$k]->small_image = usam_get_product_thumbnail_src($product->product_id);
			$this->data['products'][$k]->url = get_permalink( $product->product_id );
			$this->data['products'][$k]->sku = usam_get_product_meta( $product->product_id, 'sku' );
			$this->data['products'][$k]->formatted_quantity = usam_get_formatted_quantity_product_unit_measure($product->quantity, $product->unit_measure);
		}
		$this->data['start_date'] = get_date_from_gmt( $this->data['start_date'], "Y-m-d H:i:s" );			
		$this->data['end_date'] = get_date_from_gmt( $this->data['end_date'], "Y-m-d H:i:s" );
		$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i:s" );
		$this->data['currency'] = usam_get_currency_sign_price_by_code( $this->data['type_price'] );	
		$contact = usam_get_contact( $this->data['manager_id'], 'user_id' );
		if( $contact )
		{
			$this->js_args['manager'] = $contact;
			$this->js_args['manager']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['manager']['url'] = usam_get_contact_url( $contact['id'] );
		}
		$this->tabs = [
			['slug' => 'products', 'title' => __('Товары / Услуги','usam')],		
			['slug' => 'subscription_renewal', 'title' => __('Продление подписки','usam')], 
			['slug' => 'change', 'title' => __('Изменения','usam')],			
			['slug' => 'report', 'title' => __('Отчет','usam')],
		];	
		$this->register_modules_products();		
	}		
		
	protected function main_content_cell_1( ) 
	{		
		?>
		<div class="view_data">
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Клиент', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php $this->display_customer( $this->data['customer_id'], $this->data['customer_type'] ); ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<?php echo usam_get_status_name_subscription( $this->data['status'] ); ?>
				</div>
			</div>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Стоимость заказа','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice" v-html="data.totalprice"></span> <span v-html="data.currency"></span>
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Создана', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $this->data['date_insert'] ); ?></div>
			</div>						
		</div>	
		<?php	
	}	
	
	protected function main_content_cell_2( ) 
	{ 		
		$timestamp = time();
		$to_date_timestamp = strtotime( $this->data['end_date'] );	
		$date = human_time_diff( $to_date_timestamp, $timestamp );	
		?>
		<div class="view_data">				
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Дата подписки', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $this->data['start_date'], get_option( 'date_format', 'd.m.Y' ) ).'- '.usam_local_date( $this->data['end_date'], get_option( 'date_format', 'd.m.Y' ) ); ?></div>
			</div>
			<?php if ( $to_date_timestamp < $timestamp ) { 				
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Закончилась', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $date; ?></div>
				</div>	
			<?php } else { 
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Осталось', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $date; ?></div>
				</div>	
			<?php } ?>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Продление', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $this->data['period_value'].' '.__( $this->data['period'] ); ?></div>
			</div>
			<?php $this->display_manager_box() ?>
		</div>	
		<?php		
	}

	function display_tab_subscription_renewal()
	{
		$this->list_table( 'subscription_renewal' );			
	}
	
	public function display_tab_products( )
	{		
		require_once( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-subscription.php' );
	}
}
?>