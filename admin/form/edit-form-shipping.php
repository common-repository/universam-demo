<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Shipping extends USAM_Edit_Form
{	
	protected $vue = true;	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.__('Изменить службу доставки','usam').'</span><span v-else>'.__('Добавить службу доставки','usam').'</span>';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_data_tab(  )
	{			
		$default = ['id' => 0, 'name' => '', 'description' => '', 'include_in_cost' => 1, 'active' => 0, 'handler' => '', 'img' => 0, 'sort' => 10, 'tax_id' => 0, 'delivery_option' => 0, 'period_from' => 0, 'period_to' => 0, 'period_type' => 'day', 'price' => 0, 'courier_company' => 0, 'storage_id' => 0, 'margin' => 0, 'margin_type' => 'p', 'types_payers' => [], 'roles' => [], 'locations' => [], 'price_from' => 0, 'price_to' => 0, 'products_from' => 0, 'products_to' => 0, 'weight_to' => 0, 'weight_from' => 0];		
		$this->js_args['storage'] = '';
		if( $this->id )			
		{
			$this->data = usam_get_delivery_service( $this->id );	
			$metas = usam_get_delivery_service_metadata( $this->id );
			if ( $metas )
				foreach($metas as $metadata )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
			$this->data['types_payers'] = array_map('intval', usam_get_array_metadata( $this->id, 'delivery_service', 'type_payer'));			
			$this->data['roles'] = usam_get_array_metadata( $this->id, 'delivery_service', 'roles');
			$this->data['locations'] = array_map('intval', usam_get_array_metadata( $this->id, 'delivery_service', 'locations'));
			
			$merchant_instance = usam_get_shipping_class( $this->id );
			$options = $merchant_instance->get_options();
			foreach( $options as $option )
			{
				$this->data[$option['code']] = isset($this->data[$option['code']]) ? $this->data[$option['code']] : $option['default'];
				$default[$option['code']] = $option['default'];	
			}
			if ( $this->data['storage_id'] )
			{
				$storage = usam_get_storage( $this->data['storage_id'] );
				if ( $storage )
				{
					$gateways = usam_get_integrations( 'shipping' );
					$address = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'address'));
					$title = $storage['owner']?sprintf(__("Склад %s", "usam"),$gateways[$this->data['handler']]):__("Ваш склад", "usam");
					$location = usam_get_location( $storage['location_id'] );
					$this->js_args['storage'] = ($location ? 'г. '.$location['name']:$storage['title']).' '.$address.' - '.$title;
				}
			}
		}		
		$this->data = usam_format_data( $default, $this->data );	
		$this->js_args['thumbnail'] = ['url' => (string)wp_get_attachment_image_url( $this->data['img'], 'full' )];
	}
	
	protected function print_scripts_style() 
	{ 
		wp_enqueue_media();
	}

	function display_right()
	{		
		 ?>
		<usam-box :id="'usam_status_active'" :title="'<?php _e( 'Статус активности', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<usam-checked v-model="data.active" :text="'<?php _e('Активно', 'usam'); ?>'"></usam-checked>
			</template>
		</usam-box>
		<usam-box :id="'usam_status_active'" :title="'<?php _e( 'Миниатюра для доставки', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<wp-media v-model="data.img" :file="thumbnail"></wp-media>
			</template>
		</usam-box>
		<usam-box :id="'shipping_module_general_settings'" :title="'<?php _e( 'Общие настройки', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for="option_sort"><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input type="text" id="option_sort" name="sort" maxlength = "3" size = "3" v-model="data.sort" autocomplete="off"/>		
						</div>
					</div>
				</div>	
			</template>
		</usam-box>
		<?php
	}	
	
	function display_left()
	{	
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<div class="form_description"><textarea v-model="data.description" placeholder="<?php _e('Описание', 'usam') ?>"></textarea></div>
			<div class="edit_form">
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_handler'><?php esc_html_e( 'Обработчик', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select name='handler' v-model="data.handler" id='option_handler'>		
							<option value=''><?php esc_html_e( 'По умолчанию', 'usam'); ?></option>
							<?php					
							$gateways = usam_get_integrations( 'shipping' );
							foreach ( $gateways as $code => $name ) 
							{								
								?><option value='<?php echo $code; ?>'><?php echo $name; ?></option><?php	
							}	
							?>
						</select>	
					</div>
				</div>
				<div class ="edit_form__item" v-for="(property, k) in options">
					<div class ="edit_form__item_name">{{property.name}}</div>
					<div class="edit_form__item_option">
						<?php require( USAM_FILE_PATH.'/admin/templates/template-parts/type-option.php' ); ?>
					</div>
				</div>				
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Сроки доставки', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<?php _e( 'от', 'usam'); ?> <input type="text" maxlength = "3" size = "3" style='width:100px' v-model="data.period_from"> <?php _e( 'до', 'usam'); ?> <input type="text" maxlength = "3" size = "3" style='width:100px' v-model="data.period_to">
						<select v-model="data.period_type" style='width:100px'>
							<option value="hour"><?php _e( 'час', 'usam'); ?></option>
							<option value="day"><?php _e( 'день', 'usam'); ?></option>					
							<option value="month"><?php _e( 'месяц', 'usam'); ?></option>
						</select>		
					</div>
				</div>				
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label><?php esc_html_e( 'Включать в стоимость заказа', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">								
						<selector v-model="data.include_in_cost"></selector>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label><?php esc_html_e( 'Компания предоставляющая транспортные услуги', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select v-model='data.courier_company'>
							<option value="0"><?php _e('Ваш интернет-магазин', 'usam'); ?></option>
							<option :value="account.id" v-html="account.bank_account_name" v-for="account in bank_accounts"></option>
						</select>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Налог', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">								
						<select name="tax_id" id="shipping_tax" v-model="data.tax_id">
							<option value="0"><?php esc_html_e( 'Не выбрано', 'usam'); ?></option>
							<?php $taxes = usam_get_taxes(['active' => 1]);									
							foreach ( $taxes as $tax )			
							{
								?><option value="<?php echo $tax->id; ?>"><?php echo "$tax->name ($tax->value%)"; ?></option><?php
							}									
							?>	
						</select>	
					</div>
				</div>	
				<div class ="edit_form__item" v-if="selfpickup">
					<div class ="edit_form__item_name"><label for="option_storage"><?php esc_html_e( 'Вариант доставки', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<selector v-model="data.delivery_option" :items="[{id:0, name:'<?php _e('До двери', 'usam'); ?>'},{id:1, name:'<?php _e('Самовывоз из пункта', 'usam'); ?>'}]"></selector>
						<div><a v-if="data.delivery_option>0" href="<?php echo admin_url('admin.php?page=storage&tab=storage') ?>"><?php esc_html_e( 'Настроить пункты выдачи', 'usam'); ?></a></div>
					</div>
				</div>	
				<div class ="edit_form__item" v-if="data.handler">
					<div class ="edit_form__item_name"><label for="option_storage"><?php esc_html_e( 'Склад отгрузки', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<autocomplete :selected="storage" @change="data.storage_id=$event.id; storage=$event.name" :request="'storages'"></autocomplete>
						<input type='hidden' name="storage_id" v-model="data.storage_id">							
					</div>
				</div>			
				<div class ="edit_form__item" v-if="data.handler">
					<div class ="edit_form__item_name"><label for='option_margin'><?php esc_html_e( 'Наценка', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option edit_form__item_row">
						<input type="text" id='option_margin' maxlength = "8" size = "8" v-model="data.margin"/>
						<select name="margin_type" id='option_margin_type' v-model="data.margin_type">
							<option value="p">%</option>
							<option value="f"><?php echo esc_html( usam_get_currency_sign() ) ?></option>
						</select>
					</div>
				</div>				
			</div>	
		</div>
		<usam-box :id="'delivery_service_restrictions'" :title="'<?php _e( 'Ограничения способов доставки', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e( 'Вес (г)', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<?php _e( 'от', 'usam'); ?> <input type="text" name="weight_from" maxlength = "8" size = "8" style='width:100px' v-model="data.weight_from"/> <?php _e( 'до', 'usam'); ?> <input type="text" name="weight_to" maxlength = "8" size = "8" style='width:100px' v-model="data.weight_to"/>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e('Стоимость корзины', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<?php _e( 'от', 'usam'); ?> <input type="text" name="price_from" v-model="data.price_from" maxlength = "12" size = "12" style='width:100px'/> <?php _e( 'до', 'usam'); ?> <input type="text" maxlength = "12" size = "12" name="price_to" style='width:100px' v-model="data.price_to"/>				
						</div>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e('Количество товаров в корзине', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<?php _e( 'от', 'usam'); ?> <input type="text" name="products_from" maxlength = "12" size = "12" style='width:100px' v-model="data.products_from"/> <?php _e( 'до', 'usam'); ?> <input type="text" maxlength = "12" size = "12" name="products_to" style='width:100px' v-model="data.products_to"/>				
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Роли пользователей','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select-list @change="data.roles=$event.id" :multiple='1' :lists="roles" :selected="data.roles"></select-list>	
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Типы плательщиков','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select-list @change="data.types_payers=$event.id" :multiple='1' :lists="payers" :selected="data.types_payers"></select-list>	
						</div>
					</div>		
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Местоположения','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<locations v-model="data.locations"></locations>			
						</div>
					</div>						
				</div>
			</template>
		</usam-box>
		<?php	
	}
}
?>