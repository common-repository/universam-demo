<?php			
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
class USAM_Form_Lead extends USAM_View_Form
{						
	protected $ribbon = true;	
	protected function get_data_tab(  )
	{				
		$this->data = usam_get_lead( $this->id );		
		if ( empty($this->data) || $this->viewing_not_allowed() )
		{
			$this->data = [];
			return;
		}
		$default = ['id' => 0, 'name' => '', 'number' => usam_get_document_number( 'lead' ), 'totalprice' => 0, 'number_products' => 0, 'type_price' => usam_get_manager_type_price(), 'type_payer' => usam_get_type_payer_customer(), 'status' => 'not_processed', 'user_id' => 0, 'manager_id' => get_current_user_id(), 'contact_id' => 0, 'company_id' => 0, 'shipping' => 0, 'source' => 'manager', 'date_insert' => date("Y-m-d H:i:s"), 'date_status_update' => date("Y-m-d H:i:s"), 'bank_account_id' => get_option( 'usam_shop_company', 0 ), 'products' => [], 'taxes' => [], 'groups' => [], 'external_document' => '', 'external_document_date' => ''];		
		$metas = usam_get_lead_metadata( $this->id );
		if ( $metas )
			foreach($metas as $metadata )
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);			
		$this->data['currency'] = usam_get_currency_sign_price_by_code( $this->data['type_price'] );	
		$type_price = usam_get_setting_price_by_code( $this->data['type_price'] );		
		$this->data['rounding'] = isset($type_price['rounding']) ? $type_price['rounding'] : 2;			
		if( !empty($this->data['date_insert']) )
			$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i" );	

		$this->data['product_taxes'] = usam_get_lead_product_taxes( $this->data['id'] ); 	
		foreach($this->data['product_taxes'] as $k => $product)
			$this->data['product_taxes'][$k]->name = stripcslashes($product->name);				
		$this->data['products'] = usam_get_products_lead( $this->data['id'] );
		
		$this->data = array_merge( $default, $this->data );			
		$this->js_args['user'] = ['user_login' => ''];
		if( $this->data['user_id'] )
		{
			$userdata = get_userdata( $this->data['user_id'] );
			if ( $userdata )
				$this->js_args['user'] = ['user_login' => $userdata->data->user_login];
		}
		$this->register_modules_products();		
		
	//	if ( empty($this->data) )
	//		return false;				
		$this->tabs = [
			['slug' => 'main', 'title' => __('Основное','usam') ], 	
			['slug' => 'change', 'title' => __('Изменения','usam')],
		];
		
		$this->header_title = __('Описание', 'usam');
		
		$this->change = !usam_check_object_is_completed($this->data['status'], 'lead') || usam_check_current_user_role('administrator');
		$user_id = get_current_user_id(); 
		if ( !user_can( $user_id, 'edit_lead' ) )
			$this->change = false; 
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function viewing_not_allowed()
	{			
		return !usam_check_document_access( $this->data, 'lead', 'view' );
	}
	
	protected function get_title_tab()
	{ 		
		if ( empty($this->data) )	
		{				
			$title = sprintf( __('Лид №%s не существует', 'usam'), $this->id );		
		}
		else
		{
			usam_employee_viewing_objects(['object_type' => 'lead', 'object_id' => $this->id]);
			return sprintf('%s %s', $this->data['name'], '<span class="subtitle_date">'.esc_html__('от', 'usam').' '.usam_local_date( $this->data['date_insert'], "d.m.Y" ).'</span>');			
		}
		return $title;
	}
	
	function print_scripts_style()
	{
		wp_enqueue_style('usam-order-admin'); 	
	}
	
	protected function toolbar_buttons( ) 
	{
		if ( $this->id != null )
		{			
			if ( usam_check_document_access( $this->data, 'lead', 'edit' ) )
			{
				?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php
			}
			$links = [
				['action' => 'recalculate', 'group' => 'leads', 'title' => esc_html__('Пересчитать', 'usam'), 'capability' => 'edit_lead'], 	
				['action' => 'motify_order_status_mail', 'group' => 'leads', 'title' => esc_html__('Уведомить о статусе на почту', 'usam'), 'capability' => 'send_email'], 
				['action' => 'motify_order_status_sms', 'group' => 'leads', 'title' => esc_html__('Уведомить о статусе в смс', 'usam'), 'capability' => 'send_sms'],
			];							
			$links[] = ['action' => 'delete', 'group' => 'leads', 'title' => esc_html__('Удалить', 'usam'), 'capability' => 'delete_lead'];					
			$this->display_form_actions( $links );							
		}
	}
	
	protected function main_content_cell_1()
	{	
		?>						
		<h3><?php esc_html_e( 'Основная информация', 'usam'); ?></h3>
		<div class = "view_data">	
			<?php 			
			if ( $this->data['contact_id'] ) 
			{ 							
				?>	
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e('Клиент','usam'); ?>:</div>
					<div class ="view_data__option document_customer">		
						<?php 						
						$customer = usam_get_contact( $this->data['contact_id'] );						
						if ( !empty($customer) )
						{
							$customer['thumbnail'] = usam_get_contact_foto( $customer['id'] );
							$customer['link'] = usam_get_contact_url( $customer['id'] );
							$customer['name'] = trim(usam_get_contact_metadata( $customer['id'], 'full_name' ));	
							if ( $customer['contact_source'] == 'employee' )
								unset($customer['status']);							
						}
						$lastname = usam_get_lead_metadata($this->id, 'billinglastname');
						$firstname = usam_get_lead_metadata($this->id, 'billingfirstname');
						if ( $firstname && $lastname )
							$customer['name'] = $lastname.' '.$firstname;
						$this->display_crm_customer( $customer );	
						if ( !empty($customer['status']) && $customer['status'] != 'customer' )
							echo usam_display_status( $customer['status'], 'contact');
						?>	
					</div>					
				</div>						
				<?php 			
			} 	
			if ( $this->data['company_id'] ) 
			{ 							
				?>
				<div class ="view_data__row">	
					<div class ="view_data__name"><?php _e( 'Компания в базе','usam'); ?>:</div>
					<div class ="view_data__option document_customer">		
					<?php 	
						$customer = usam_get_company( $this->data['company_id'] );
						if ( !empty($customer) )
						{
							$customer['link'] = usam_get_company_url( $customer['id'] );
							$customer['thumbnail'] = usam_get_company_logo( $customer['id'] );
						}
						if ( empty($customer['company']) || $customer['company'] != usam_get_lead_metadata( $this->id, 'company') )
							$customer['name'] = usam_get_lead_metadata( $this->id, 'company');
						$this->display_crm_customer( $customer );	
						if ( !empty($customer['status']) && $customer['status'] != 'customer' )
							echo usam_display_status( $customer['status'], 'company');
					?>
					</div>	
				</div>
				<?php 
			} 				
			?>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<select v-model='data.status'>
						<option v-for="status in statuses" v-if="status.internalname == data.status || status.visibility" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
					</select>
				</div>
			</div>	
			<?php $this->display_manager_box() ?>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Источник','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php echo usam_get_order_source_name($this->data['source']); ?>
				</div>
			</div>	
			<?php $this->display_groups( admin_url("admin.php?page=orders&tab=leads") ) ?>
		</div>		
		<?php	
	}
	
	protected function main_content_cell_2()
	{	
		?>						
		<h3><?php esc_html_e( 'Финансовая информация', 'usam'); ?></h3>
		<div class = "view_data">	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Стоимость','usam'); ?>:</div>
				<div class ="view_data__option" v-if="data.products.length">
					<span class="document_totalprice" v-html="to_currency(data.totalprice)"></span>
				</div>
				<div class ="view_data__option" v-else>
					<span class="document_totalprice change_data" v-if="propertyСhange==''" @click="pСhange('totalprice')" v-html="to_currency(data.totalprice)"></span>
					<input v-else type="text" class="property_change" v-model="data.totalprice" @keypress="isNumber">
				</div>
			</div>
		</div>		
		<?php	
	}
	
	public function display_tab_main( )
	{		
		?>
		<usam-box :id="'usam_order_products'" v-if="data.products.length || data.totalprice==0">
			<template v-slot:title>
				<?php _e( 'Товары', 'usam'); ?><span v-if="data.products.length">({{data.products.length}})</span>
			</template>			
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/documents/table-products-lead.php' ); ?>
				<button v-if="!edit" type="button" class="button" @click="edit=!edit"><?php _e( 'Добавить товар', 'usam'); ?></button>
				<div v-if="edit" class="select_product__buttons">			
					<button type="button" class="button button-primary" @click="saveForm"><?php _e( 'Сохранить', 'usam'); ?></button>
					<button type="button" class="button" @click="edit=!edit"><?php _e( 'Отменить', 'usam'); ?></button>
				</div>
			</template>
		</usam-box>
		<usam-box :id="'usam_order_customer'">
			<template v-slot:title>
				<?php _e( 'Данные покупателя', 'usam'); ?><a @click="edit_data=!edit_data"><?php _e('Изменить','usam'); ?></a>
			</template>	
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/documents/lead-customer-details.php' ); ?>
			</template>
		</usam-box>		
		<?php		
	}
}