<?php
require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Lead extends USAM_Edit_Form
{				
	protected $vue = true;	
	protected function get_data_tab(  )
	{			
		$default = ['id' => 0, 'name' => '', 'number' => usam_get_document_number( 'lead' ), 'totalprice' => 0, 'number_products' => 0, 'type_price' => usam_get_manager_type_price(), 'type_payer' => usam_get_type_payer_customer(), 'status' => 'not_processed', 'user_id' => 0, 'manager_id' => get_current_user_id(), 'contact_id' => 0, 'company_id' => 0, 'shipping' => 0, 'source' => 'manager', 'date_insert' => date("Y-m-d H:i:s"), 'date_status_update' => date("Y-m-d H:i:s"), 'bank_account_id' => get_option( 'usam_shop_company', 0 ), 'products' => [], 'taxes' => [], 'external_document' => '', 'external_document_date' => ''];
		if ( $this->id )
		{
			$this->data = usam_get_lead( $this->id );
			if ( $this->viewing_not_allowed() )
			{
				$this->data = [];
				return;
			}
			$metas = usam_get_lead_metadata( $this->id );
			if ( $metas )
				foreach($metas as $metadata )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);			
			$this->data['currency'] = usam_get_currency_sign_price_by_code( $this->data['type_price'] );	
			$type_price = usam_get_setting_price_by_code( $this->data['type_price'] );		
			$this->data['rounding'] = isset($type_price['rounding']) ? $type_price['rounding'] : 2;			
			if( !empty($this->data['date_insert']) )
				$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i" );				
			$this->data['products'] = usam_get_products_lead( $this->data['id'] );		
			$this->data['product_taxes'] = usam_get_lead_product_taxes( $this->data['id'] ); 		
			foreach($this->data['product_taxes'] as $k => $product)
				$this->data['product_taxes'][$k]->name = stripcslashes($product->name);				
		}
		$this->data = array_merge( $default, $this->data );				
		$this->js_args['user'] = ['user_login' => ''];
		if( $this->data['user_id'] )
		{
			$userdata = get_userdata( $this->data['user_id'] );
			if ( $userdata )
				$this->js_args['user'] = ['user_login' => $userdata->data->user_login];
		}
		$this->register_modules_products();		
	}	

	protected function viewing_not_allowed()
	{
		return !usam_check_document_access( $this->data, 'lead', 'view' );
	}	
	
	protected function get_title_tab()
	{ 			
		if ( $this->not_exist )	
		{				
			$title = sprintf( __('Лид №%s не существует', 'usam'), $this->id );		
		}
		elseif ( $this->id != null )
		{
			$title = sprintf( __('Изменить лид %s','usam'), '<span class="subtitle_date">'.esc_html__('от', 'usam').' '.usam_local_formatted_date( $this->data['date_insert'] ).'</span>');
			usam_employee_viewing_objects(['object_type' => 'order', 'object_id' => $this->id]);
		}
		else
			$title = __('Добавить новый лид', 'usam');	
		return $title;
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
		
	protected function toolbar_buttons( ) 
	{ 				
		if ( usam_check_document_access( $this->data, 'lead', 'edit' ) )
		{
			?><button type="button" class="button button-primary action_buttons__button" @click="saveForm"><?php echo $this->title_save_button(); ?></button><?php	
		}
		?>		
		<div class="action_buttons__button" v-if="data.id>0"><a href="<?php echo add_query_arg(['form' => 'view']); ?>" class="button"><?php _e('Посмотреть','usam'); ?></a></div>
		<div v-if="data.id>0">
			<?php
			$links[] = ['action' => 'deleteItem', 'title' => esc_html__('Удалить', 'usam'), 'capability' => 'delete_lead'];				
			$this->display_form_actions( $links );
			?>
		</div>
		<?php
	}
		
	function print_scripts_style()
	{
		wp_enqueue_style('usam-order-admin'); 	
	}		
	
	protected function get_fastnav( ) 
	{											
		$nav = [			
			'usam_order_products' => __('Товары', 'usam'), 
			'usam_order_customer' => __('Данные покупателя', 'usam'),
		];
		return $nav;
	}	
		
	public function display_right( )
	{										
		?>
		<usam-box :id="'usam_main_data_document'" :handle="false" :title="'<?php _e( 'Основная информация', 'usam'); ?>'">
			<template v-slot:body>
			<div class='edit_form'>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Дата', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<datetime-picker v-model="data.date_insert"/>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Продавец', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<select v-model='data.bank_account_id'>
							<option :value="account.id" v-html="account.bank_account_name" v-for="account in bank_accounts"></option>
						</select>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Источник', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<select v-model="data.source">
							<?php									
							$order_source = usam_get_order_source();
							foreach ( $order_source as $key => $name ) 
							{											
								?><option value='<?php echo $key; ?>'><?php echo $name; ?></option><?php
							}
							?>
						</select>	
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<select v-model='data.status'>
							<option v-for="status in statuses" v-if="status.internalname == data.status || status.visibility" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
						</select>
					</div>
				</div>
				<label class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Не уведомлять', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type='checkbox' v-model='prevent_notification' value='1'>
					</div>
				</label>			
			</div>		
			</template>			
		</usam-box>
		<usam-box :id="'managers'" :handle="false" :title="'<?php _e( 'Ответственный', 'usam'); ?>'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-manager.php' ); ?>
		</usam-box>
		<usam-box :id="'usam_groups'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-groups.php' ); ?>
		</usam-box>	
		<?php
	}	
		
		
	public function display_left( ) 
	{		
		?>		
		<div class='event_form_head'>			
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<div class='edit_form'>			
				<div class ="edit_form__item" v-if="!data.products.length">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Сумма', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type='text' name='totalprice' v-model="data.totalprice">
					</div>
				</div>
			</div>
		</div>
		<usam-box :id="'usam_order_products'" v-if="data.products.length || data.totalprice==0">
			<template v-slot:title>
				<?php _e( 'Товары', 'usam'); ?><span v-if="data.products.length">({{data.products.length}})</span>
			</template>			
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/documents/table-products-lead.php' ); ?>
			</template>
		</usam-box>
		<usam-box :id="'usam_order_customer'" :title="'<?php _e('Данные покупателя','usam'); ?>'">
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/documents/lead-customer-details.php' ); ?>
			</template>
		</usam-box>		
		<?php
	}
}