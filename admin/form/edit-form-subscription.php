<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php'  );
class USAM_Form_subscription extends USAM_Edit_Form
{
	protected $vue = true;
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить подписку','usam');
		else
			$title = __('Добавить подписку', 'usam');	
		return $title;
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_toolbar_buttons( ) 
	{
		$links = [ 			
			['name' => __('Посмотреть','usam'), 'action_url' => add_query_arg(['id' => $this->id, 'form' => 'view', 'form_name' => 'subscription']), 'display' => 'not_null'],
			['vue' => ["@click='saveForm'"], 'primary' => true, 'name' => $this->id ? __('Сохранить','usam'):__('Добавить','usam'), 'display' => 'all'],
		];	
		return $links;
	}
	
	protected function get_data_tab(  )
	{				
		$default = ['id' => 0, 'status' => 'not_signed', 'period_value' => 1,  'period' => 'month', 'customer_type' => 'contact', 'customer_id' => '', 'manager_id' => get_current_user_id(), 'type_price' => usam_get_manager_type_price(), 'days' => 30, 'start_date' => date( "Y-m-d H:i:s" ), 'products' => []];
		$this->js_args = ['customer_name' => '', 'manager' => []];
		if ( $this->id != null )
		{
			$this->data = usam_get_subscription( $this->id );
			if ( !$this->data )
				return;
			$this->data['start_date'] = get_date_from_gmt( $this->data['start_date'], "Y-m-d H:i:s" );			
			$this->data['end_date'] = get_date_from_gmt( $this->data['end_date'], "Y-m-d H:i:s" );
			$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i:s" );
			$metas = usam_get_subscription_metadata( $this->id );
			foreach($metas as $metadata )
			{
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
			}			
			$this->data['products'] = usam_get_products_subscription( $this->data['id'] );
			foreach($this->data['products'] as $k => $product)
			{
				$this->data['products'][$k]->name = stripcslashes($product->name);
				$this->data['products'][$k]->small_image = usam_get_product_thumbnail_src($product->product_id);
				$this->data['products'][$k]->url = get_permalink( $product->product_id );
				$this->data['products'][$k]->sku = usam_get_product_meta( $product->product_id, 'sku' );
				$this->data['products'][$k]->formatted_quantity = usam_get_formatted_quantity_product_unit_measure($product->quantity, $product->unit_measure);
			}
			$this->data['currency'] = usam_get_currency_sign_price_by_code( $this->data['type_price'] );	
			if ( $this->data['customer_id'] )
			{
				if ( $this->data['customer_type'] == 'contact' )
				{			
					$contact = usam_get_contact( $this->data['customer_id'] );	
					$this->js_args['customer_name'] = isset($contact['appeal'])?stripcslashes($contact['appeal']):'';
				}
				else
				{			
					$company = usam_get_company( $this->data['customer_id'] );			
					$this->js_args['customer_name'] = isset($company['name'])?stripcslashes($company['name']):'';
				}
			}		
		}
		$this->data = array_merge( $default, $this->data );
		$contact = usam_get_contact( $this->data['manager_id'], 'user_id' );
		if( $contact )
		{
			$this->js_args['manager'] = $contact;
			$this->js_args['manager']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['manager']['url'] = usam_get_contact_url( $contact['id'] );
		}		
		$this->register_modules_products();		
	}	
		
	function display_left()
	{			
		?>
		<usam-box :id="'usam_subscription_general'" :title="'<?php _e( 'Данные подписки', 'usam'); ?>'">
			<template v-slot:body>				
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_days'><?php esc_html_e( 'Дата начала подписки', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<v-date-picker v-model="data.start_date" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
								<template v-slot="{ inputValue, inputEvents }"><input type="text" :value="inputValue" v-on="inputEvents"/></template>
							</v-date-picker>
						</div>
					</div>		
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Период', 'usam'); ?>:</div>
						<div class ="edit_form__item_option edit_form__item_group">
							<input type="text" v-model="data.period_value" size="45">
							<select v-model="data.period">
								<option value='day'><?php _e('День','usam') ?></option>
								<option value='month'><?php _e('Месяц','usam') ?></option>	
								<option value='year'><?php _e('Год','usam') ?></option>						
							</select>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_status'><?php esc_html_e( 'Статус', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<select name='status' v-model="data.status">		
								<?php							
								$statuses = usam_get_subscription_statuses();
								foreach ( $statuses as $code => $name ) 
								{								
									?><option value='<?php echo $code; ?>'><?php echo $name; ?></option><?php	
								}	
								?>
							</select>	
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_status'><?php esc_html_e( 'Подписчик', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<div class="counterparty">
								<select v-model="data.customer_type" class='select_customer_type' @change="customer_name=''">
									<?php
									foreach (['company' => __('Компания','usam'),'contact' => __('Контакт','usam')] as $key => $value) 
									{				
										printf('<option value="%s">%s</option>', $key, $value);	
									}
									?>
								</select>
								<autocomplete @change="data.customer_id=$event.id" :selected="customer_name" :request="request" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
							</div>
						</div>
					</div>
				</div>	
			</template>
		</usam-box>
		<usam-box :id="'usam_section_products'" :title="'<?php _e( 'Товары', 'usam'); ?>'">
			<template v-slot:body>				
				<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/table-products-subscription.php' );	?>	
			</template>
		</usam-box>
		<?php		
    }
	
	public function display_right( )
	{
		?>
		<usam-box :id="'managers'" :handle="false" :title="'<?php _e('Ответственный','usam'); ?>'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-manager.php' ); ?>
		</usam-box>	
		<?php
	}
}
?>