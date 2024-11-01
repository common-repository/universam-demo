<?php		
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
require_once( USAM_FILE_PATH ."/admin/includes/general-forms/order_form.php");	
require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
class USAM_Form_Order extends USAM_View_Form
{		
	protected $purchase_log;
	protected $orderform;
	protected $ribbon = true;
	
	protected function get_data_tab(  )
	{		
		$default = ['cancellation_reason' => '', 'date_paid' => ''];
		$this->purchase_log = new USAM_Order( $this->id );			
		if ( !$this->purchase_log->exists() )	
		{
			$this->not_exist = true;
			return false;
		}			
		$this->data = $this->purchase_log->get_data();		
		if ( $this->viewing_not_allowed() )
		{
			$this->data = [];
			return;
		}
		if( !empty($this->data['date_pay_up']) )
			$this->data['date_pay_up'] = get_date_from_gmt( $this->data['date_pay_up'], "Y-m-d H:i" );	
		if( !empty($this->data['date_insert']) )
			$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i" );	
		if( !empty($this->data['date_paid']) )
			$this->data['date_paid'] = get_date_from_gmt( $this->data['date_paid'], "Y-m-d H:i" );
		$metas = usam_get_order_metadata( $this->id );
		foreach($metas as $metadata )
			if ( !isset($this->data[$metadata->meta_key]) )
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
		$this->data = array_merge($default, $this->data);
		$this->tabs = [
			['slug' => 'main', 'title' => __('Основное','usam')], 
		];	
		if( usam_check_type_product_sold( 'product' ) )		
		{
			$this->tabs[] = ['slug' => 'stock', 'title' => __('Остатки','usam') ];
			if ( current_user_can('view_shipped') )
				$this->tabs[] = ['slug' => 'shipped_products', 'title' => __('Отгрузки','usam') ];
		}
		if( current_user_can( 'view_payment' ) || current_user_can( 'edit_payment' ) )
			$this->tabs[] = ['slug' => 'payment_history', 'title' => __('Оплаты','usam')];
		if( current_user_can( 'view_order_contractor' ) || current_user_can( 'edit_order_contractor' ) )
			$this->tabs[] = ['slug' => 'order_contractor', 'title' => usam_get_document_name('order_contractor')];
		//$this->tabs[] = ['slug' => 'map', 'title' => __('Карта','usam')];
		$this->tabs[] = ['slug' => 'related_documents', 'title' => __('Документы','usam')];
		$this->tabs[] = ['slug' => 'change', 'title' => __('Изменения','usam')];
		$this->tabs[] = ['slug' => 'report', 'title' => __('Отчет','usam')];
		
		$this->header_title = __('Описание', 'usam');
		$this->header_content = usam_get_order_metadata($this->id, 'note');		
		
		$this->change = !usam_check_object_is_completed($this->data['status'], 'order') || usam_check_current_user_role('administrator');
		$user_id = get_current_user_id(); 
		if ( !user_can( $user_id, 'edit_order' ) )		
		{			
			$this->change = false; 
		}			
		$calculated_data = $this->purchase_log->get_calculated_data();			
		$this->data = array_merge( $this->data, $calculated_data );			
		$this->js_args = [	
			'payment_gateways' => usam_get_payment_gateways(),
			'payment_types' => usam_get_payment_types(),			
		];		
		$this->js_args['user'] = ['user_login' => ''];
		if( $this->data['user_ID'] )
		{
			$userdata = get_userdata( $this->data['user_ID'] );
			if ( $userdata )
				$this->js_args['user'] = ['user_login' => $userdata->data->user_login];
		}
		add_action( 'admin_enqueue_scripts', array(&$this, 'print_scripts_style') );
		$this->orderform = new USAM_Order_Form( $this->data );
	}
			
	protected function get_title_tab()
	{ 	
		if ( $this->not_exist )	
			$title = __('Заказ не существует', 'usam');
		elseif ( $this->id != null )
		{
			$title = sprintf( __('Заказ %s','usam'), '<span class="number">#{{data.number}}</span> <span class="subtitle_date">'.esc_html__('от', 'usam')." {{localDate(data.date_insert,'".get_option('date_format', 'Y/m/j')." H:i')}}</span>");
			usam_employee_viewing_objects(['object_type' => 'order', 'object_id' => $this->id]);
		}
		else
			$title = __('Добавить новый заказ', 'usam');	
		return $title;
	}
	
	protected function viewing_not_allowed()
	{			
		return !usam_check_document_access( $this->data, 'order', 'view' );
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function toolbar_buttons( ) 
	{
		if ( $this->id != null )
		{
			if ( usam_check_document_access( $this->data, 'order', 'edit' ) )
			{	
				?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php	
			}
			$links = [
				['action' => 'recalculate_order', 'group' => 'orders', 'title' => esc_html__('Пересчитать заказ', 'usam'), 'capability' => 'edit_order'], 								
				['action' => 'copy', 'group' => 'orders', 'title' => esc_html__('Копировать заказ', 'usam'), 'capability' => 'add_order'], 
				['action' => 'motify_order_status_mail', 'group' => 'orders', 'title' => esc_html__('Уведомить о статусе на email', 'usam'), 'capability' => 'send_email'], 
				['action' => 'motify_order_status_sms', 'group' => 'orders', 'title' => esc_html__('Уведомить о статусе в смс', 'usam'), 'capability' => 'send_sms'],
				['action' => 'order_return', 'group' => 'orders', 'title' => esc_html__('Возврат заказа', 'usam'), 'capability' => 'edit_buyer_refund'],
			];	
			if ( get_option('usam_ftp_settings') )
				$links[] = ['action' => 'export_order_ftp', 'group' => 'orders', 'title' => esc_html__('Выгрузить на FTP чек', 'usam'), 'capability' => 'export_order'];
			if( !wp_is_mobile() )
			{
				if ( current_user_can( 'export_order' ) )
				{
					$rules = usam_get_exchange_rules(['type' => 'order_export']);			
					if ( !empty($rules) )
					{
						foreach ( $rules as $rule )
							$links[] = ['action' => 'download-'.$rule->id, 'group' => 'export', 'title' => sprintf( __('Экспортировать (%s)', 'usam'), $rule->name), 'capability' => 'export_order'];
					}
				}								
				$this->display_printed_form( 'order' );
			}	
			if ( current_user_can( 'send_email' ) ) 
			{				
				$this->newsletter_templates( 'order' );				
			}					
			$links[] = ['action' => 'delete', 'group' => 'orders', 'title' => esc_html__('Удалить', 'usam'), 'capability' => 'delete_order'];	
			$this->display_form_actions( $links );			
		}
	}
	
	function print_scripts_style()
	{ 
		wp_enqueue_style('usam-order-admin'); 	
	}	
	
	function currency_display( $price ) 
	{			
		return usam_get_formatted_price( $price, ['type_price' => $this->data['type_price'], 'wrap' => true]);
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
						$lastname = usam_get_order_metadata($this->id, 'billinglastname');
						$firstname = usam_get_order_metadata($this->id, 'billingfirstname');
						if ( $firstname && $lastname )
							$customer['name'] = $lastname.' '.$firstname;
						
						if ( !empty($customer['online']) )
						{
							if ( strtotime($customer['online']) >= USAM_CONTACT_ONLINE )
								$customer['name'] .= "<span class='customer_online'></span>";
							else
								$customer['name'] .= "<span class='date_visit'>".sprintf( __('был %s', 'usam'), get_date_from_gmt($customer['online'], 'd.m.Y H:i'))."</span>";
						}					
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
						if ( empty($customer['company']) || $customer['company'] != usam_get_order_metadata( $this->id, 'company') )
							$customer['name'] = usam_get_order_metadata( $this->id, 'company');
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
				<div class ="view_data__name"><?php _e( 'Статус','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php
					if ( current_user_can('edit_status_order') )
					{
						?>
						<div class="description_reason_cancellation" v-if="data.status=='canceled' && data.cancellation_reason==''">
							<textarea class='button' v-model="cancellation_reason" placeholder="<?php _e('Напишите причину','usam'); ?>"></textarea>
							<div class='event_buttons'>
								<button type='submit' class='button button-primary' @click="data.cancellation_reason=cancellation_reason; selectStatus()"><?php _e('Отменить заказ', 'usam'); ?></button>
								<button type='submit' class='button js-show-status-selection'><?php _e('Вернуть статусы', 'usam'); ?></button>
							</div>
						</div>
						<select v-model='data.status' v-else>
							<option v-for="status in statuses" v-if="status.type=='order' && (status.internalname == data.status || status.visibility)" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
						</select>						
						<?php
					}
					else
					{
						echo usam_get_object_status_name( $this->data['status'], 'order' );
					}
					?>
				</div>				
			</div>	
			<div class ="view_data__row" v-if="data.cancellation_reason">
				<div class ="view_data__name"><?php _e( 'Причина отказа','usam'); ?>:</div>
				<div class ="view_data__option cancellation_reason" v-html="data.cancellation_reason"></div>
			</div>
			<?php $this->display_manager_box();	?>		
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Источник','usam'); ?>:</div>
				<div class ="view_data__option">
					<?php 					
					if ( $this->data['source'] == 'vk' )
					{						
						$vk_group_id = usam_get_order_metadata($this->id , 'vk_group_id' ); 
						$profile = usam_get_social_network_profile( $vk_group_id );
						$title = $profile ? $profile['name']:'';
						echo usam_get_system_svg_icon( $this->data['source'], ['title' => $title] );
					}	
					else
						echo usam_get_order_source_name($this->data['source']);
					?>
				</div>
			</div>	
			<?php
			$date_exchange = usam_get_order_metadata($this->id, 'date_exchange');
			if ( $date_exchange )
			{
				$exchange = usam_get_order_metadata($this->id , 'exchange' ); 
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e( 'Выгружен в 1С','usam'); ?>:</div>
					<div class ="view_data__option"><?php echo usam_local_date( $date_exchange ).(!$exchange?' ('.__( 'готов к обновлению','usam').')':''); ?></div>
				</div>
				<?php 
			}			
			if ( usam_check_type_product_sold( 'product' ) && current_user_can('view_shipped') )
			{
				$shipped_documents = usam_get_shipping_documents_order( $this->id );		
				if ( !empty($shipped_documents) )
				{
					$shipped_document = (array)array_pop($shipped_documents);					
					?>	
					<div class ="view_data__row">
						<div class ="view_data__name"><?php _e( 'Получение заказа','usam'); ?>:</div>
						<div class ="view_data__option"><?php echo $shipped_document['name']; ?></div>
					</div>
					<?php
					if ( !empty($shipped_document['storage_pickup']) )
					{
						?>
						<div class ="view_data__row">
							<div class ="view_data__name"><?php _e( 'Офис получения','usam'); ?>:</div>
							<div class ="view_data__option"><?php 														
									$storage = usam_get_storage( $shipped_document['storage_pickup'] ); 
									if ( $storage )										
									{						
										$location = usam_get_location( $storage['location_id'] );
										$city = isset($location['name'])?htmlspecialchars($location['name'])." ":'';
										$storage_pickup_phone = usam_get_storage_metadata( $storage['id'], 'phone');
										$storage_pickup_schedule = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'schedule'));
										$storage_pickup_address = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'address'));
										?>									
										<div class='crm_customer'>
											<a href='<?php echo admin_url("admin.php?page=storage&tab=storage&table=storage&form=edit&form_name=storage&id=".$shipped_document['storage_pickup']); ?>'><?php echo $storage['title']; ?></a>
											<div class='crm_customer__info'>
												<div class='crm_customer__info_rows'>
													<div class='crm_customer__info_row'><div class="crm_customer__info_row_name storage_pickup_code"><?php _e("Код","usam"); ?>:</div><div class="crm_customer__info_row_option"><?php echo $storage['code']; ?></div></div>
													<div class='crm_customer__info_row'><div class="crm_customer__info_row_name storage_pickup_address"><?php _e("Адрес","usam"); ?>:</div><div class="crm_customer__info_row_option"><?php echo $storage_pickup_address; ?></div></div>
													<div class='crm_customer__info_row'><div class="crm_customer__info_row_name storage_pickup_phone"><?php _e("т.","usam"); ?>:</div><div class="crm_customer__info_row_option"><?php echo $storage_pickup_phone; ?></div></div>
													<div class='crm_customer__info_row'><div class="crm_customer__info_row_name storage_pickup_schedule"><?php _e("График работы","usam"); ?>:</div><div class="crm_customer__info_row_option"><?php echo $storage_pickup_schedule; ?></div></div>
												</div>
											</div>
										</div>
										<?php 
									}		
									else
										_e('Выбранный склад не существует', 'usam');
								?>			
							</div>
						</div>
						<?php 
					}
				}	
			}				
			$this->display_groups( admin_url("admin.php?page=orders&tab=orders") );	
			?>			
		</div>		
		<?php	
	}
	
	protected function main_content_cell_2()
	{			
		?>						
		<h3><?php esc_html_e( 'Финансовая информация', 'usam'); ?></h3>
		<div class = "view_data">
			<div class ="view_data__row">				
				<div class ="view_data__name"><?php _e( 'Продавец','usam'); ?>:</div>
				<div class ="view_data__option bank_account dropdown_area" v-if="typeof bank_accounts[data.bank_account_id] !== typeof undefined"><a :href="bank_accounts[data.bank_account_id].company_url" target="_blank">{{bank_accounts[data.bank_account_id].bank_account_name}}</a></div>
			</div>		
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Стоимость заказа','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="document_totalprice" v-html="formatted_totalprice"></span> <span v-html="data.currency"></span>
				</div>
			</div>
			<div class ="view_data__row" v-if="data.paid!==0">
				<div class ="view_data__name"><?php _e( 'Оплачено','usam'); ?>:</div>
				<div class ="edit_form__item_option" v-if="payments!==null" v-html="to_currency(total_paid)"></div>
			</div>				
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e( 'Статус оплаты','usam'); ?>:</div>
				<div class ="view_data__option">
					<span class="item_status item_status_valid" v-if="data.paid==2"><?php _e('Оплачен', 'usam'); ?></span>
					<span class="item_status item_status_notcomplete" v-else-if="data.paid==1"><?php _e('Частично оплачен', 'usam'); ?></span>
					<span class="item_status item_status_attention" v-else><?php _e('Не оплачен', 'usam'); ?></span>	
				</div>
			</div>
			<div class ="view_data__row" v-if="payments!==null && payment_required>0">
				<div class ="view_data__name"><?php _e( 'Требуется оплата','usam'); ?>:</div>
				<div class ="view_data__option" v-html="to_currency(payment_required)"></div>
			</div>
			<div class ="view_data__row" v-if="data.paid!==2">
				<div class ="view_data__name"><?php _e( 'Оплатить до','usam'); ?>:</div>
				<div class ="view_data__option" v-if="data.paid!==2">
					<span class='item_status' :class="[possibility_pay?'item_status_attention':'item_status_valid']">
						<v-date-picker v-model="data.date_pay_up" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
							<template v-slot="{ inputValue, inputEvents }"><span v-on="inputEvents">{{inputValue}}</span></template>
						</v-date-picker>
					</span>					
				</div>
			</div>
			<div class ="view_data__row" v-if="data.paid===2">
				<div class ="view_data__name"><?php _e( 'Дата оплаты','usam'); ?>:</div>
				<div class ="view_data__option">{{localDate(data.date_paid,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}</div>
			</div>
			<div class ="view_data__row" v-if="payments!==null && payments.length">
				<div class ="view_data__name"><?php _e( 'Оплата заказа','usam'); ?>:</div>
				<div class ="view_data__option" v-html="payments[0].name"></div>
			</div>
		</div>		
		<?php	
	}	
			
	public function display_tab_payment_history()
    { 			
		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/payment-documents.php' );
		?>
		<div class="button_box" v-if="payments!==null">			
			<button type="button" class="button" @click="addPayment"><?php _e( 'Добавить', 'usam'); ?></button>
			<button type="button" class="button button-primary" @click="savePayment" v-if="payments.length"><?php _e( 'Сохранить', 'usam'); ?></button>
		</div>		
		<?php
	}	
		
	public function display_tab_shipped_products()
    {
		$this->register_modules_products();
		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/documents/shipped-documents.php' );
		?>
		<div class="button_box">			
			<button type="button" class="button" @click="addShipped"><?php _e( 'Добавить', 'usam'); ?></button>
			<button type="button" class="button button-primary" @click="saveShipped" v-if="shippeds.length"><?php _e( 'Сохранить', 'usam'); ?></button>
		</div>		
		<?php
	}	

	function display_tab_order_contractor()
	{
		$this->register_modules_products();
		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/order_contractor-documents.php' );
		?>
		<div class="button_box">			
			<button type="button" class="button" @click="addOrderSupplier" v-if="contractors.length"><?php _e( 'Добавить', 'usam'); ?></button>
			<div class='usam_message message_error'><div class='validation-error'><?php _e( 'Поставщики не добавлены в базу, чтобы использовать "Заказ поставщику" добавьте их.', 'usam'); ?></div></div>
			<button type="button" class="button button-primary" @click="saveOrderSupplier" v-if="orders_contractor!==null && orders_contractor.length"><?php _e( 'Сохранить', 'usam'); ?></button>
		</div>		
		<?php
	}		
		
	public function display_tab_main( )
	{							
		$title = __('Заказанные позиции', 'usam');
	//	if ( $this->data['paid'] == 2 )
	//		$title .= ' <span class="item_status_valid item_status">'.__('Оплачен, менять нельзя', 'usam').'</span>';
		usam_add_box(['id' => 'usam_order_products', 'title' => $title, 'function' => [$this->orderform, 'section_products'], 'change_parameter' => 'edit', 'vue' => true, 'edit' => $this->change ]);
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
								<tr v-if="Object.keys(files).length === 0"><td colspan = '4' class = "no-items"><?php _e( 'Нет загружаемых заказов', 'usam'); ?></td></tr>
								<tr v-for="file in files" v-else>
									<td v-html="file.name"></td>
									<td>
										<span>{{file.downloads}}</span>
									</td>
									<td>{{file.ip_number}}</td>							
									<td>										
										<span v-if="file.active==0"><?php _e( 'Не доступно', 'usam'); ?></span>
										<span v-else><?php _e( 'Доступно', 'usam'); ?></span>
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
				<?php _e( 'Данные покупателя', 'usam'); ?><a @click="edit_data=!edit_data"><?php _e('Изменить','usam'); ?></a>
			</template>	
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/documents/order-customer-details.php' ); ?>
			</template>
		</usam-box>	
		<?php			
	}
	
	function display_tab_map()
	{
		?><div class="form_view__title"><?php _e('Адрес доставки заказа', 'usam'); ?></div><?php
		$property_types = usam_get_order_property_types( $this->id );		
		if ( !empty($property_types['delivery_address']) )
		{ 
			$property_types = usam_get_order_property_types( $this->id );	
			$address = !empty($property_types['delivery_address'])?$property_types['delivery_address']['_name']:'';	
			$contact = !empty($property_types['delivery_contact'])?$property_types['delivery_contact']['_name']:'';	
			$this->display_map('order',  $property_types['delivery_address']['_name'] );
			echo "<div class ='js-map-description' style='display:none'><div class='map__pointer'><div class='map__pointer_notes'>".esc_html( usam_limit_words(usam_get_order_metadata($this->id, 'note')))."</div><div class='map__pointer_text'><div class='map__pointer_name'>$contact</div><div class='map__pointer_address'>$address</div></div></div></div>";
		}
	}
	
	function display_tab_stock()
	{
		?>
		<div class="form_view__title"><?php _e('Остатки на складах, где есть товар', 'usam'); ?></div>
		<div class="table_products_container">		
			<table class="usam_list_table table_products stock_products">
				<thead>
					<tr>
						<th scope="col" class="manage-column manage-title sortable"><?php _e('Товар', 'usam'); ?></th>
						<th scope="col" class="manage-column sortable" :class="'column-'+storage.id" v-for="storage in storages" v-if="storages_stock[storage.id]!==undefined">
							<span class="storage_name" v-html="storage.title" :title="storage.title"></span>
						</th>
					</tr>
				</thead>		
				<tbody v-if="Object.keys(storages_stock).length !== 0">		
					<tr v-for="(product, k) in products">
						<td class="column-title">
							<div class="product_name_thumbnail">
								<div class="product_image image_container viewer_open" @click="slotProps.viewer(k)">
									<img :src="product.small_image">
								</div>
								<div class="product_name">
									<a v-html="product.name" @click="addProduct(k)"></a>	
									<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
									<strong class="product_stock"><?php _e( 'Всего', 'usam'); ?>: <span>{{storages_stock[0][product.product_id]}}</span></strong>
								</div>
							</div>				
						</td>
						<td class="column-quantity" v-if="storages_stock[storage.id]!==undefined" v-html="storages_stock[storage.id][product.product_id]" v-for="storage in storages"></td>
					</tr>
				</tbody>
			</table>
		</div>			
		<?php		
	}	
		
	function display_tab_related_documents()
	{	
		$this->display_related_documents( 'order' );
	}
}