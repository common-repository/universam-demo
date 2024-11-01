<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH ."/admin/includes/general-forms/order_form.php");	
require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
class USAM_Form_Order extends USAM_Edit_Form
{			
	private $purchase_log;	
	private $is_closed = false;
	protected $vue = true;
	
	protected function get_data_tab(  )
	{	
		$default = ['id' => 0, 'cancellation_reason' => '', 'date_paid' => '', 'groups' => []];
		$this->purchase_log = new USAM_Order( $this->id );	
		$this->data = $this->purchase_log->get_data();			
		if ( $this->viewing_not_allowed() )
		{
			$this->data = [];
			return;
		}
		if ( !$this->purchase_log->exists() )	
		{
			$this->not_exist = true;
			return false;
		}
		$calculated_data = $this->purchase_log->get_calculated_data();	
		$this->data = array_merge( $this->data,  $calculated_data );		
		$metas = usam_get_order_metadata( $this->id );
		foreach($metas as $metadata )
			if ( !isset($this->data[$metadata->meta_key]) )
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
		$this->data = array_merge( $default, $this->data );	
		if( !empty($this->data['date_pay_up']) )
			$this->data['date_pay_up'] = get_date_from_gmt( $this->data['date_pay_up'], "Y-m-d H:i" );		
		if( !empty($this->data['date_paid']) )
			$this->data['date_paid'] = get_date_from_gmt( $this->data['date_paid'], "Y-m-d H:i" );
		if( !empty($this->data['date_insert']) )
			$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i" );				
		$this->js_args = [	
			'payment_gateways' => usam_get_payment_gateways(),
			'payment_types' => usam_get_payment_types(),
			'column_names' => usam_get_columns_product_table(),
		];		
		$this->js_args['user'] = ['user_login' => ''];
		if( $this->data['user_ID'] )
		{
			$userdata = get_userdata( $this->data['user_ID'] );
			if ( $userdata )
				$this->js_args['user'] = ['user_login' => $userdata->data->user_login];
		}		
	}		
	
	protected function viewing_not_allowed()
	{		
		return !usam_check_document_access( $this->data, 'order', 'view');
	}
	
	protected function get_title_tab()
	{ 			
		if ( $this->not_exist )	
			$title = __('Заказ не существует', 'usam');	
		elseif ( $this->id != null )
		{
			$title = sprintf( __('Изменить заказ %s','usam'), '<span class="number">#'.$this->data['number'].'</span> <span class="subtitle_date">'.esc_html__('от', 'usam').' '.usam_local_formatted_date( $this->data['date_insert'] ).'</span>');
			usam_employee_viewing_objects(['object_type' => 'order', 'object_id' => $this->id]);
		}
		else
			$title = __('Добавить новый заказ', 'usam');	
		return $title;
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function toolbar_buttons( ) 
	{ 					
		if ( usam_check_document_access( $this->data, 'order', 'edit' ) )
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
	
	function currency_display( $price ) 
	{				
		return usam_get_formatted_price( $price, ['type_price' => $this->data['type_price'], 'wrap' => true] );
	}	
		
	public function display_payment_documents()
    { 			
		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/payment-documents.php' );
		?>
		<div class="button_box">			
			<button type="button" class="button" @click="addPayment"><?php _e( 'Добавить', 'usam'); ?></button>
		</div>		
		<?php
	}	
		
	public function display_shipped_documents()
    {
		$this->register_modules_products();
		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/documents/shipped-documents.php' );
		?>
		<div class="button_box">			
			<button type="button" class="button" @click="addShipped"><?php _e( 'Добавить', 'usam'); ?></button>
		</div>		
		<?php
	}			
		
	protected function get_fastnav( ) 
	{											
		$nav = [			
			'usam_order_products' => __('Товары', 'usam'), 
			'usam_create_notes' => __('Заметки', 'usam'),
			'usam_payment_history' => __('Оплаты', 'usam'),
			'usam_shipped_products' => __('Отгрузки', 'usam'),		
		];
		if ( usam_check_type_product_sold( 'product' ) )
			$nav['usam_shipped_products'] = __('Отгрузки', 'usam');
		if ( usam_check_type_product_sold( 'electronic_product' ) )
			$nav['usam_downloadable_files'] = __('Загружаемые', 'usam');
		return $nav;
	}
	
	public function display_main_data( )
	{	
		?>			
		<div class='edit_form'>					
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( '№ документа', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type='text' name='number' v-model="data.number">
				</div>
			</div>
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
						<option v-for="status in statuses" v-if="status.type=='order' && (status.internalname == data.status || status.visibility)" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
					</select>
				</div>
			</div>
			<label class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Не уведомлять', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type='checkbox' v-model='prevent_notification' value='1'>
				</div>
			</label>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Сумма', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<?php echo $this->currency_display($this->data['totalprice']); ?>
				</div>
			</div>							
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Статус оплаты', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<span class="item_status item_status_valid" v-if="data.paid==2"><?php _e('Оплачен', 'usam'); ?></span>
					<span class="item_status item_status_notcomplete" v-else-if="data.paid==1"><?php _e('Частично оплачен', 'usam'); ?></span>
					<span class="item_status item_status_attention" v-else><?php _e('Не оплачен', 'usam'); ?></span>	
				</div>
			</div>		
			<div class ="edit_form__item" v-if="data.date_paid && data.paid">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Дата оплаты', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">{{localDate(data.date_paid,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}</div>
			</div>
			<div class ="edit_form__item" v-if="data.paid!==0">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Оплачено', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option" v-html="to_currency(total_paid)"></div>
			</div>				
			<div class ="edit_form__item" v-if="payment_required>0">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Требуется оплата', 'usam'); ?>:</div>
				<div class ="edit_form__item_option" v-html="to_currency(payment_required)"></div>
			</div>
			<div class ="edit_form__item" v-if="payment_required<0">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Переплата', 'usam'); ?>:</div>
				<div class ="edit_form__item_option" v-html="to_currency(payment_required)"></div>
			</div>
			<div class ="edit_form__item" v-if="data.paid!==2">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Оплатить до', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<v-date-picker v-model="data.date_pay_up" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
						<template v-slot="{ inputValue, inputEvents }"><input type="text" :value="inputValue" v-on="inputEvents"/></template>
					</v-date-picker>
				</div>
			</div>
		</div>		
		<?php
	}					
		
	public function display_right( )
	{									
		usam_add_box(['id' => 'usam_main_data_document', 'title' => __('Основная информация','usam'), 'function' => [$this, 'display_main_data'], 'close' => false]);
		?>
		<usam-box v-if="data.status=='canceled'" :id="'cancellation_reason'" :title="'<?php _e( 'Причина отмены заказа', 'usam'); ?>'">
			<template v-slot:body>
				<textarea class='cancellation_reason' rows='3' cols='40' maxlength='255' v-model="data.cancellation_reason"></textarea>
			</template>
		</usam-box>
		<usam-box :id="'managers'" :handle="false" :title="'<?php _e('Ответственный','usam'); ?>'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-manager.php' ); ?>
		</usam-box>	
		<usam-box :id="'usam_groups'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-groups.php' ); ?>
		</usam-box>	
		<?php	
	}	
		/**
	 * Вывод конструкции заказа
	 */
	public function display_left( ) 
	{		
		?>
		<usam-box :id="'purchlog_notes'" :title="'<?php _e( 'Заметки заказа', 'usam'); ?>'">
			<template v-slot:body>
				<textarea class="width100" rows='3' cols='40' maxlength='255' v-model="data.note"></textarea>
			</template>
		</usam-box>
		<?php 		
		$title = __('Заказанные позиции', 'usam');
	//	if ( $this->data['paid'] == 2 )
	//		$title .= ' <span class="item_status_valid item_status">'.__('Оплачен, менять нельзя', 'usam').'</span>';
		$form = new USAM_Order_Form( $this->data );	
		usam_add_box(['id' => 'usam_order_products', 'title' => $title, 'function' => [$form, 'section_products']]);
		if ( usam_check_type_product_sold( 'electronic_product' ) )
		{			
			?>
			<usam-box :id="'downloadable_files'" :title="'<?php _e( 'Загружаемые товары', 'usam'); ?>'" v-if="files !== undefined">
				<template v-slot:body>
					<div class="usam_table_container">
						<table class="usam_list_table table_products" cellspacing="0">
							<thead>
								<tr>
									<th class="column_title"><?php _e( 'Имя товара', 'usam'); ?></th>	
									<th class="column_available"><?php _e( 'Доступно', 'usam'); ?></th>					
									<th class="column_ip"><?php _e( 'IP', 'usam'); ?></th>
									<th class="column_status"><?php _e( 'Статус', 'usam'); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr v-if="Object.keys(files).length === 0"><td colspan = '4' class = "no-items"><?php _e( 'Нет загружаемых заказов', 'usam'); ?> </td></tr>
								<tr v-for="file in files" v-else>
									<td v-html="file.name"></td>
									<td><input type="text" v-model="file.downloads" size = '5' maxlength='5'></td>
									<td>{{file.ip_number}}</td>							
									<td>
										<select v-model="file.active">
											<option value='0'><?php _e( 'Не доступно', 'usam'); ?></option>	
											<option value='1'><?php _e( 'Доступно', 'usam'); ?></option>										
										</select>
									</td>							
								</tr>
							</tbody>			
						</table>
					</div>	
				</template>
			</usam-box>
			<?php
		}		
		?>
		<usam-box :id="'usam_order_customer'">
			<template v-slot:title>
				<?php _e( 'Данные покупателя', 'usam'); ?><a @click="sidebar('buyers')"><?php _e('Сменить покупателя','usam'); ?></a>
			</template>	
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/documents/order-customer-details.php' ); ?>
			</template>
		</usam-box>	
		<?php		
		if ( usam_check_type_product_sold( 'product' ) )
			usam_add_box( 'usam_shipped_products', __('Отгрузки', 'usam'), array($this, 'display_shipped_documents') );
		usam_add_box( 'usam_payment_history', __('История оплаты', 'usam'), array( $this, 'display_payment_documents' ) );		
	}
}