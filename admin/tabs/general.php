<?php
class USAM_Tab_General extends USAM_Tab
{		
	protected $views = ['simple'];
	public function get_title_tab()
	{			
		return __('Основные настройки', 'usam');	
	}	
			
	public function action_processing() 
	{
		if ( isset($_POST['location'] ) ) 
		{		
			$location = absint($_POST['location']);
			update_option('usam_shop_location', $location );
		}	
		if ( isset($_POST['types_products_sold'] ) ) 
		{		
			$types_products_sold = array_map('sanitize_title', (array)$_POST['types_products_sold']);
			foreach ( $types_products_sold as $type ) 
			{
				switch ( $type ) 
				{
					case 'product' :
					
					break;
					case 'services' :
					
					break;
					case 'subscriptions' :
					
					break;
					case 'electronic_product' :
					
					break;
				}
			}
			update_option('usam_types_products_sold', $types_products_sold );
		}	
		require_once( USAM_FILE_PATH . '/includes/directory/country_query.class.php'  );	
		$locations_db = usam_get_locations( array('fields' => 'id=>name', 'parent' => 0 ) );	
		$locations = array('RU' => __('Россия', 'usam'), 'BY' => __('Беларусь', 'usam'), 'KZ' => __('Казахстан', 'usam'), 'UA' => __('Украина', 'usam'), 'others'  => __('Другие', 'usam') );
		
		$locations_code = array();
		if ( !empty($locations_db) )
		{
			foreach ( $locations as $code => $name1 ) 
			{
				foreach ( $locations_db as $id => $name2 ) 
				{					
					if ( $name1 == $name2 )
						$locations_code[$code] = $id;
				}
			}
		}		
		$select_codes = !empty($_REQUEST['codes'])?$_REQUEST['codes']:array();
		$delete_location = array();
		$codes = array();	
		foreach ( $locations as $code => $name ) 
		{			
			if (  in_array($code, $select_codes) )
			{				
				if ( $code == 'others' )
				{					
					if ( !in_array('США', $locations_db) )
						$codes[] = $code;					
				}
				elseif ( !in_array($name, $locations_db) )					
					$codes[] = $code;
			}
			elseif ( in_array($name, $locations_db) && !empty($locations_code[$code]) )
			{				
				$delete_location[] = $locations_code[$code];
			}
			elseif ( $name == 'Другие' )					
			{		
				foreach ( $locations_db as $id => $name2 ) 
				{					
					if ( !in_array($name2, $locations) )
						$delete_location[] = $id;
				}				
			}
		} 		
		if ( !empty($codes) )
			usam_install_locations( $codes );		
		if ( !empty($delete_location) )
			usam_delete_locations( $delete_location );		
		parent::action_processing( );
	}
	
	public function display() 
	{			
		?>	
		<div class="postbox usam_box open_wizard">
			<?php printf( __('Вы можете использовать <a href="%s" target="_blank"><strong>мастер установки</strong></a>', 'usam'), admin_url('admin.php?page=usam-setup') ); ?>
		</div>
		<?php
		usam_add_box('usam_work', __('Вариант развертывания платформы', 'usam'), array( $this, 'option_work_meta_box' ) );				
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )	
			usam_add_box('usam_marketplace', __('Настройки Маркетплейса', 'usam'), array( $this, 'marketplace_meta_box' ) );
		elseif ( get_option('usam_website_type', 'store' ) == 'price_platform' )
			usam_add_box('usam_price_platform', __('Настройки Прайс-площадки', 'usam'), array( $this, 'price_platform_meta_box' ) );
		usam_add_box('usam_your_company_details', __('Реквизиты вашей фирмы', 'usam'), array( $this, 'your_company_details_meta_box' ) );			
		usam_add_box('usam_locations', __('Местоположения, в которых вы будете продавать', 'usam'), array( $this, 'locations_meta_box' ) );	
		usam_add_box('usam_types_products', __('Продаваемые товары', 'usam'), array( $this, 'types_products_meta_box' ) );
		usam_add_box('usam_checkout_consent_processing_personal_data', __('Согласие на обработку персональных данных', 'usam'), array($this, 'consent_processing_personal_data_meta_box') );	
		usam_add_box('usam_cookie_notice', __('Уведомление о согласии на использование файлов cookie', 'usam'), array($this, 'cookie_notice_meta_box') );	
		usam_add_box('usam_currency_options', __('Настройки валюты', 'usam'), array( $this, 'currency_options' ) );
		usam_add_box('usam_units', __('Единица измерения веса', 'usam'), array( $this, 'units_meta_box' ) );
		usam_add_box('usam_allow_tracking', __('Центр информации', 'usam'), array( $this, 'allow_tracking_meta_box' ) );
		usam_add_box('usam_mytarget', 'myTarget', array( $this, 'mytarget_meta_box' ) );				
		usam_add_box('usam_document_number_counter', __('Формат номеров документов', 'usam'), [$this, 'formatted_document_number_meta_box']);
	}		
	
	public function consent_processing_personal_data_meta_box()
	{	               
		wp_editor(stripslashes(str_replace('\\&quot;','',get_option('usam_consent_processing_personal_data'))),'usam_consent_processing_personal_data',array(
			'textarea_name' => 'usam_options[consent_processing_personal_data]',
			'media_buttons' => false,
			'textarea_rows' => 20,
			'tinymce' => array('theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
			)	
		);
	}	
	
	public function cookie_notice_meta_box()
	{	               
		wp_editor(stripslashes(str_replace('\\&quot;','',get_option('usam_cookie_notice'))),'_cookie_notice',array(
			'textarea_name' => 'usam_options[cookie_notice]',
			'media_buttons' => false,
			'textarea_rows' => 3,
			'tinymce' => array('theme_advanced_buttons3' => false, 'remove_linebreaks' => false )
			)	
		);
	}

	public function locations_meta_box() 
	{				
		$location_name = usam_get_locations( array('fields' => 'name', 'parent' => 0 ) );	
		$locations = array('RU' => __('Россия', 'usam'), 'BY' => __('Беларусь', 'usam'), 'KZ' => __('Казахстан', 'usam'), 'UA' => __('Украина', 'usam'), 'others'  => __('Другие', 'usam') );
		?>
		<div class="usam_checked">
			<?php 
			foreach ( $locations as $key => $name ) : 						
				if ( $key == 'others' )
					$checked = in_array('США', $location_name);
				else
					$checked = in_array($name, $location_name);
				?>
				<div class="usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $key ); ?> <?php echo $checked?'checked':''; ?>">
					<div class="usam_checked_enable">
						<input type="checkbox" name="codes[]" class="input-checkbox" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked, true ); ?>/>
						<label><?php echo esc_html( $name ); ?></label>
					</div>										
				</div>
			<?php endforeach; ?>
		</div>	
		<?php	
	}
	
	public function types_products_meta_box() 
	{				
		$select_products = get_option('usam_types_products_sold', array('product', 'services' ) );	
		?>
		<div class="usam_checked">
			<?php 
			foreach ( usam_get_types_products_sold() as $key => $type ) : 							
				?>
				<div class="usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $key ); ?> <?php echo in_array($key, $select_products)?'checked':''; ?>">
					<div class="usam_checked_enable">
						<input type="checkbox" name="types_products_sold[]" class="input-checkbox" value="<?php echo esc_attr( $key ); ?>" <?php checked(in_array($key, $select_products), true ); ?>/>
						<label><?php echo esc_html( $type['plural'] ); ?></label>
					</div>										
				</div><?php
			endforeach; 
			?>
		</div>	
		<?php	
	}
	
	public function option_work_meta_box() 
	{	
		?>	
		<script>		
			jQuery(document).ready(function()
			{ 
				if ( jQuery('#usam_option_work').val() == 'recast' )
					jQuery('#central_platform_site').show();
				else
					jQuery('#central_platform_site').hide();
				jQuery('body').delegate('#usam_option_work', 'change', function(){					
					if ( jQuery(this).val() == 'recast' )
						jQuery('#central_platform_site').show();
					else
						jQuery('#central_platform_site').hide();
				});		
			});
		</script>	
		<?php 
		$options = [
			['type' => 'select', 'title' => __('Вариант сайта', 'usam'), 'option' => 'website_type', 'options' => ['crm' => __('Корпоративный сайт с CRM', 'usam'), 'store' => __('Интернет-магазин', 'usam'), 'marketplace' => __('Маркетплейс', 'usam'), 'price_platform' => __('Прайс-площадка', 'usam')], 'default' => 'store'],
		//	['type' => 'select', 'title' => __('Вариант работы', 'usam'), 'option' => 'option_work', 'default' => 'simple', 'options' => ['simple' => __('Обычный', 'usam'), 'central' => __('Центральная платформа', 'usam'), 'recast' => __('Периферийная платформа', 'usam')]], 			
		//	['type' => 'input', 'title' => __('Сайт центральной платформы', 'usam'), 'option' => 'central_platform_site', 'description' => '', 'default' => ''],
		];
		$this->display_table_row_option( $options );
	}
	
	public function marketplace_meta_box()
	{
		$options = [				
			['type' => 'select', 'title' => __('Как будем зарабатывать?', 'usam'), 'option' => 'options_earning_marketplace', 'options' => ['' => __('Отключено', 'usam'), 'sales_commission_seller' => __('Комиссия с продаж, платить продавец', 'usam'), 'payment_listing' => __('Оплата за количество в месяц', 'usam'), 'contact_customer' => __('Контакт с заказчиком', 'usam'), 'subscription' => __('Подписка', 'usam')], 'default' => 'sales_commission_seller'], 		
			['type' => 'input', 'title' => __('Какая комиссия?', 'usam'), 'option' => 'commission_marketplace', 'description' => '', 'default' => '5'],			
		];
		$this->display_table_row_option( $options );	
	}	
	
	public function price_platform_meta_box()
	{
		$options = array( 					
			array('type' => 'select', 'title' => __('Текст внешней ссылки', 'usam'), 'option' => 'target_addtocart_button', 'options' => array('_self' => __('Открывать ссылку в том же окне', 'usam'), '_blank' => __('Открывать ссылки в новом окне', 'usam') ), 'default' => '_self'  ), 				
		);
		$this->display_table_row_option( $options );	
	}	
	
	public function your_company_details_meta_box() 
	{					
		$location = get_option('usam_shop_location' );
		$shop_company = get_option('usam_shop_company' );
		?>		
		<div class='usam_setting_table edit_form'>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e('Местоположение по умолчанию', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php
					$autocomplete = new USAM_Autocomplete_Forms( );
					$autocomplete->get_form_position_location( $location );
					?>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e('Реквизиты вашей компании', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_select_bank_accounts( $shop_company, ['name' => "usam_options[shop_company]"] );
					if ( empty($shop_company) )	
					{
						?><p><?php _e('Добавьте вашу фирму и банковский счет в разделе', 'usam'); ?> &laquo;<a target="_blank" href="<?php echo admin_url("admin.php?page=crm&tab=companies"); ?>"><?php _e('Компании', 'usam'); ?></a>&raquo; <?php _e('и укажите тип компании &laquo;своя&raquo;', 'usam'); ?></p><?php
					}
					?>		
				</div>
			</div>			
			<?php				
				$mailboxes = usam_get_mailboxes( array('fields' => array('id','name','email') ) );
				$emails = array( 0 => __('Не выбрано', 'usam') );
				foreach( $mailboxes as $mailbox )
				{
					$emails[$mailbox->email] = $mailbox->name.' ('.$mailbox->email.')';
				}		
				$phones = array( '' => __('Не выбрано','usam') );
				foreach ( usam_get_phones() as $phone )
				{	
					$phones[$phone['phone']] = $phone['name'].' (т. '.usam_phone_format($phone['phone'], $phone['format']).')';
				}
				$options = array( 				
					['type' => 'select', 'title' => __('Телефон компании', 'usam'), 'option' => 'shop_phone', 'options' => $phones],	
					['type' => 'select', 'title' => __('Электронная почта компании', 'usam'), 'option' => 'return_email', 'options' => $emails, 'description' => __('Выберете основную электронную почту', 'usam')],
				);					  
				$this->row_option( $options );	
				?>				
		</div>
		<?php		
	}
	
	public function units_meta_box() 
	{				
		$units = usam_get_dimension_units( );		
		$dimension_units = array();
		foreach( $units as $key => $unit )
			$dimension_units[$key] = $unit['title'];
			
		$options = array( 
			array('type' => 'select', 'title' => __('Единица измерения веса', 'usam'), 'option' => 'weight_unit', 'options' => usam_get_weight_units( ), 'description' => ''),
			array('type' => 'select', 'title' => __('Единица измерения', 'usam'), 'option' => 'dimension_unit', 'options' => $dimension_units, 'description' => ''),
		);
		$this->display_table_row_option( $options );
	}	
	
	public function allow_tracking_meta_box() 
	{	
		$options = array( 
			array('type' => 'radio', 'title' => __('Автоматическое местоположение посетителя', 'usam'), 'option' => 'get_customer_location', 'default' => 1),  
			array('type' => 'radio', 'title' => __('Отслеживание поведения', 'usam'), 'option' => 'allow_tracking', 'default' => 1),
			array('type' => 'radio', 'title' => __('Подсказки для персонала', 'usam'), 'option' => 'pointer', 'default' => 1),
		);
		$this->display_table_row_option( $options );
	}	
	
	public function mytarget_meta_box() 
	{					
		$options = [	
			array('type' => 'radio', 'title' => __('Активировать счетчик', 'usam'), 'option' => 'mytarget_counter_active', 'default' => 0),
			array('key' => 'counter_id', 'type' => 'input', 'title' => __('Счетчик', 'usam'), 'option' => 'mytarget_counter', 'description' => '', 'default' => ''),
			array('key' => 'dynamic_remarketing', 'type' => 'radio', 'title' => __('Динамический ремаркетинг', 'usam'), 'option' => 'mytarget_counter', 'default' => 0),
		];
		$this->display_table_row_option( $options );
	}	
			
	public function currency_options() 
	{			
		$currencies = usam_get_currencies( );			
		
		$select_currency = [];
		foreach ( $currencies as $currency ) 
		{
			$select_currency[$currency->code] = $currency->code." ($currency->name)";
		}	
		$currency_sign = usam_get_currency_sign();		
						
		$options = [
			['type' => 'select', 'title' => __('Валюта по-умолчанию', 'usam'), 'option' => 'currency_type', 'options' => $select_currency],
			['type' => 'radio', 'title' => __('Расположение валюты', 'usam'), 'option' => 'currency_sign_location', 'radio' => ['1' => "100{$currency_sign}", '2' => "100 $currency_sign", '3' => "{$currency_sign}100", '4' => "$currency_sign 100"]],
			['type' => 'input', 'title' => __('Разделитель тысяч', 'usam'), 'option' => 'thousands_separator', 'attribute' => ['maxlength' => "1", 'size' => "1"]],
			['type' => 'input', 'title' => __('Десятичный разделитель', 'usam'), 'option' => 'decimal_separator', 'attribute' => ['maxlength' => "1", 'size' => "1"]],	
			['type' => 'text', 'title' => __('Предварительный просмотр', 'usam'), 'html' => "10".get_option('usam_thousands_separator' )."000".get_option('usam_decimal_separator' )."00"],				
		];
		$this->display_table_row_option( $options );
	}
		
	public function formatted_document_number_meta_box() 
	{
		$documents = usam_get_details_documents();
		$options = []; //PH00000000001
		foreach ( $documents as $type => $document )
			$options[] = ['type' => 'input', 'key' => $type, 'title' => $document['single_name'], 'option' => 'document_number_counter', 'description' => '', 'default' => '1'];	
			
		$this->display_table_row_option( $options );	
	}	
}