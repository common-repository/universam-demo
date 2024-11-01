<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/import-form.php' );
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
class USAM_Form_product_import extends USAM_Import_Form
{				
	protected $rule_type = 'product_import';		
	
	function display_left()
	{					
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>	
			<?php $this->display_settings(); ?>
		</div>	
		<?php	
		$this->display_columns();				
		usam_add_box( 'usam_product_default_values', __('Настройки значения по умолчанию','usam'), array( $this, 'display_default_values' ));
		usam_add_box( 'usam_not_updated_products', __('Обработка не обновленных товаров','usam'), array( $this, 'display_not_updated_products' ));
    }	
	
	function display_default_values()
	{			
		?>
		<div class='edit_form'>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_status'><?php esc_html_e( 'Статус товара' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select id='option_status' v-model="data.post_status">						
						<option value=''><?php esc_html_e('Не менять' , 'usam'); ?></option>
						<?php
						foreach ( get_post_stati(['show_in_admin_status_list' => true], 'objects') as $key => $status ) 
						{										
							?><option value='<?php echo $key; ?>'><?php echo $status->label; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_contractor'><?php esc_html_e( 'Поставщик товара' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<select-list @change="data.contractor=$event.id" :lists="contractors" :selected="data.contractor" :none="' - '"></select-list>
				</div>
			</div>	
			<div class ="edit_form__item" v-for="(item, k) in taxonomies" v-if="terms[item.name] !== undefined">
				<div class ="edit_form__item_name">{{item.label}}:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data[item.name]=$event.id" :lists="terms[item.name]" :selected="data[item.name]"></select-list>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Пользователь' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<?php 
					$user_id = usam_get_exchange_rule_metadata( $this->id, 'user_id' );	
					$selected = '';
					if ( $user_id )
					{
						$user = get_user_by('id', $user_id);	
						$selected = $user->user_nicename;
					}
					?>
					<autocomplete :selected="'<?php echo $selected; ?>'" @change="data.user_id=$event.ID" :request="'users'"></autocomplete>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='change_stock'><?php esc_html_e( 'Остаток', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id='change_stock' type="number" v-model="data.change_stock" placeholder='<?php _e('Укажите число если нужно', 'usam'); ?>'>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='product_views'><?php esc_html_e( 'Популярность' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<input id='product_views' type="number" v-model="data.product_views" placeholder='<?php _e('Укажите число', 'usam'); ?>'>
				</div>
			</div>		
			<div class ="edit_form__item" v-if="priceColumns">
				<div class ="edit_form__item_name"><label for='option_change_price'><?php esc_html_e( 'Изменить цену на %' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<input id='option_change_price' type="text" v-model="data.change_price" placeholder='<?php _e('Укажите число и -/+', 'usam'); ?>'>
				</div>
			</div>
			<div class ="edit_form__item" v-if="priceColumns2">
				<div class ="edit_form__item_name"><label for='option_change_price2'><?php esc_html_e( 'Изменить цену на % (Скопировать в свойство)' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<input id='option_change_price2' type="text" v-model="data.change_price2" placeholder='<?php _e('Укажите число и -/+', 'usam'); ?>'>
				</div>
			</div>	
		</div>
		<?php
	}
		
	function display_not_updated_products()
	{
		?>
		<div class='edit_form'>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_selection_raw_data'><?php esc_html_e( 'Выбор не обработанных товаров', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id='option_selection_raw_data' v-model="data.selection_raw_data">							
						<option value='template'><?php esc_html_e('Добавленных этим шаблоном' , 'usam'); ?></option>	
						<option value='all'><?php esc_html_e('Всех товаров' , 'usam'); ?></option>						
					</select>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='not_updated_products_status'><?php esc_html_e( 'Статус товара' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select id='not_updated_products_status' v-model="data.not_updated_products_status">						
						<option value=''><?php esc_html_e('Не менять' , 'usam'); ?></option>
						<?php
						foreach ( get_post_stati(['show_in_admin_status_list' => true], 'objects') as $key => $status ) 
						{										
							?><option value='<?php echo $key; ?>'><?php echo $status->label; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='not_updated_products_stock'><?php esc_html_e( 'Изменить запас', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id='not_updated_products_stock' type="number" v-model="data.not_updated_products_stock" placeholder='<?php _e('Укажите число если нужно', 'usam'); ?>'>
				</div>
			</div>	
		</div>
		<?php
	}
}
?>