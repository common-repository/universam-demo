<?php
class USAM_Tab_customization extends USAM_Page_Tab
{	
	protected  $display_save_button = false;	
	protected $views = ['simple'];
		
	public function get_title_tab()
	{			
		return __('Настройка нашего бизнеса шаг за шагом', 'usam');	
	}
	
	public function display() 
	{		
		$groups = [
			'main' => ['name' => __('Основные настройки','usam')],			
			'theme' => ['name' => __('Настройка внешнего вида сайта','usam')],
			'product' => ['name' => __('Настройте товары','usam')],
			'import_product' => ['name' => __('Добавьте товары','usam')],
			'order' => ['name' => __('Настройте оформление заказов','usam')],
			'feedback' => ['name' => __('Настройте общение с клиентами','usam')],
			'marketing' => ['name' => __('Настройте продвижение бизнеса','usam')],
			'control' => ['name' => __('Управление','usam')],			
		];		
		$steps = [			
			'type_price' => ['group' => 'main', 'name' => __('Добавьте типы цен','usam'), 'description' => __('Вы можете создавать закупочные или зависимые цены','usam'), 'url' => admin_url('admin.php?page=shop_settings&tab=directories&view=settings&table=prices')],	
			'phone' => ['group' => 'main', 'name' => __('Добавьте телефоны','usam'), 'description' => __('Добавьте телефоны, чтобы его использовать на сайте','usam'), 'url' => admin_url('admin.php?page=shop_settings&tab=directories&view=settings&table=phones')],			
			'company' => ['group' => 'main', 'name' => __('Добавьте собственную компанию','usam'), 'description' => __('Обязательно укажите реквизиты, электронную почту и телефон','usam'), 'url' => admin_url('admin.php?page=crm&tab=companies')],
			'your_company_details' => ['group' => 'main', 'name' => __('Выберете основную компанию','usam'), 'description' => __('Выберете основную из добавленных компаний','usam'), 'url' => admin_url('admin.php?page=shop_settings&tab=general#usam_your_company_details')],
			
			'slider' => ['group' => 'theme', 'name' => __('Конструктор слайдеров','usam'), 'description' => __('Начните с конструктора слайдера','usam'), 'url' => admin_url('admin.php?page=interface&tab=sliders&view=table&table=sliders&form=edit&form_name=slider')],
			'banner' => ['group' => 'theme', 'name' => __('Конструктор баннеров','usam'), 'description' => __('Создайте с помощью конструктора баннер для главной страницы','usam'), 'url' => admin_url('admin.php?page=interface&tab=banners')],
			'color' => ['group' => 'theme', 'name' => __('Настройте цвета и формы','usam'), 'description' => __('Прочтите как настроить','usam'), 'url' => 'https://docs.wp-universam.ru/document/nastrojka-temy'],
			'home' => ['group' => 'theme', 'name' => __('Настройте главную страницу','usam'), 'description' => __('Включите или отключите блоки на главной','usam'), 'url' => admin_url('admin.php?page=interface&tab=presentation')],
			'search' => ['group' => 'theme', 'name' => __('Настройте поиск','usam'), 'description' => '', 'url' => admin_url('admin.php?page=interface&tab=search')],
						
			
			'delivery' => ['group' => 'order', 'name' => __('Настройте доставку','usam'), 'description' => __('Вы можете подключить разные способы доставки товаров','usam'), 'url' => admin_url('admin.php?page=orders&tab=orders&view=settings&table=shipping')],
			'payment' => ['group' => 'order', 'name' => __('Настройте оплату','usam'), 'description' => __('Вы можете принимать оплату выставляя счет или через платежные шлюзы','usam'), 'url' => admin_url('admin.php?page=orders&tab=orders&view=settings&table=payment_gateway')],
			'order_properties' => ['group' => 'order', 'name' => __('Настройте поля оформления заказа','usam'), 'description' => __('Вы можете добавить, включить или отключить поля овормления заказа','usam'), 'url' => admin_url('admin.php?page=orders&tab=orders&view=settings&table=order_properties')],		
			'order_newsletters' => ['group' => 'order', 'name' => __('Настройте письма клиентам','usam'), 'description' => __('Настройте письма, которые клиенты будут получать при изменении статуса заказа','usam'), 'url' => admin_url('admin.php?page=newsletter&tab=email_newsletters&table=trigger_email_newsletters')],
			'blanks' => ['group' => 'order', 'name' => __('Настройте бланк счета','usam'), 'description' => __('Настройте бланк счета или комерческого предложения','usam'), 'url' => admin_url('admin.php?page=shop_settings&tab=blanks')],
			'sms' => ['group' => 'order', 'name' => __('СМС шлюз','usam'), 'description' => __('Настройте СМС шлюз, если вы хотите отправлять смс клиентам, например, при изменение статусов заказов','usam'), 'url' => admin_url('admin.php?page=feedback&tab=sms&view=settings')],			
			
			'chat' => ['group' => 'feedback', 'name' => __('Настройте чат','usam'), 'description' => __('Вы можете подключить чат на сайте, а также viber, telegram, vk','usam'), 'url' => admin_url('admin.php?page=feedback&tab=chat&view=settings&table=messengers')],			
			'emails' => ['group' => 'feedback', 'name' => __('Подключите электронную почту','usam'), 'description' => __('Умный почтовый клиент разберет письма по заказам и контрагентам автоматически','usam'), 'url' => admin_url('admin.php?page=feedback&tab=email&view=settings&table=mailboxes')],			
			'webform' => ['group' => 'feedback', 'name' => __('Веб-формы','usam'), 'description' => __('Настройте формы получения обращений от клиентов','usam'), 'url' => admin_url('admin.php?page=interface&tab=webforms')],

			
			'sales_area' => ['group' => 'product', 'name' => __('Мультирегиональность','usam'), 'description' => __('Добавьте регионы, если вы хотите иметь разные остатки и цены в разных регионах','usam'), 'url' => admin_url('admin.php?page=shop_settings&tab=directories&view=settings&table=sales_area')],
			'product_attributes' => ['group' => 'product', 'name' => __('Настройте характеристики товаров','usam'), 'description' => __('Настройте характеристики и фильтры товаров','usam'), 'url' => admin_url('edit-tags.php?taxonomy=usam-product_attributes&post_type=usam-product')],
			'prices' => ['group' => 'product', 'name' => __('Варианты цен','usam'), 'url' => admin_url('admin.php?page=shop_settings&tab=directories&view=settings&table=prices')],
			'storage' => ['group' => 'product', 'name' => __('Склады интернет-магазина','usam'), 'url' => admin_url('admin.php?page=storage&tab=storage')],
			
			
			'product' => ['group' => 'import_product', 'name' => __('Импортировать товары и файла','usam'), 'description' => __('Вы можете импортировать товары или перенести с другой платформы','usam'), 'url' => admin_url('admin.php?page=exchange&tab=product_importer&view=table&table=product_importer&form=progress&form_name=product_importer')],
			'1c' => ['group' => 'import_product', 'name' => __('Подключить 1С','usam'), 'description' => __('Вы можете загрузить товары из 1С','usam'), 'url' => admin_url('admin.php?page=exchange&tab=1c')],
			'moysklad' => ['group' => 'import_product', 'name' => __('Подключить Мой склад','usam'), 'description' => __('Вы можете загрузить товары из Мой склад','usam'), 'url' => admin_url('admin.php?form=edit&service_code=moysklad&form_name=application&page=applications&tab=installed_applications')],
			'parser' => ['group' => 'import_product', 'name' => __('Парсер товаров','usam'), 'description' => __('Вы можете загрузить товары с сайтов ваших поставщиков и ежедневно проверять цены','usam'), 'url' => admin_url('admin.php?page=exchange&tab=parser')],
			
			
			'metrika' => ['group' => 'marketing', 'name' => __('Яндекс метрика или Google Analytics','usam'), 'description' => __('Подключите Яндекс Метрику или Google Analytics','usam'), 'url' => admin_url('admin.php?page=seo&tab=positions&view=settings&section=yandex')],
			'social_networks' => ['group' => 'marketing',  'name' => __('Социальные сети','usam'), 'description' => __('Подключите социальные сети','usam'), 'url' => admin_url('admin.php?page=social_networks&tab=vk_products&view=settings&section=application')],
			'semantic_core' => ['group' => 'marketing', 'name' => __('Семантическое ядро','usam'), 'description' => __('Настройте ключевые слова и собирайте информацию о позиции сайта в поиске','usam'), 'url' => admin_url('admin.php?page=seo&tab=keywords')],
			
		
			'notification' => ['group' => 'control', 'name' => __('Уведомления для персонала','usam'), 'description' => __('Добавьте уведомление о новых заказах, сообщениях и других события на электронную почту, по смс или в мессенджер','usam'), 'url' => admin_url('admin.php?page=shop_settings&tab=notification')],
		];			
		$bank_account = usam_get_bank_account( get_option('usam_shop_company') );
		if ( !empty($bank_account) )
		{
			$company = usam_get_company( $bank_account['company_id'] );	
			if ( isset($company['name']) )
			{
				$steps['company'] = ['group' => 'main', 'name' => sprintf(__('Изменить реквизиты %s','usam'), $company['name']), 'description' => __('Обязательно укажите расчетный счет','usam'), 'url' => admin_url('admin.php?page=crm&tab=companies&form=edit&form_name=company&id='.$company['id'])];
			}
		}
		?>
		<div class="postbox usam_box open_wizard">
			<?php printf( __('Вы можете использовать <a href="%s" target="_blank"><strong>мастер установки</strong></a>', 'usam'), admin_url('admin.php?page=usam-setup') ); ?>
		</div>
		<div class="usam_customization">
		<?php	
			foreach( $groups as $code => $group )
			{
				?>
				<h3><?php echo $group['name']; ?></h3>
				<div class="postbox">					
					<div class="usam_customization_steps">
						<?php	
						foreach( $steps as $key => $step )
						{
							if ( $code == $step['group'] )
							{
								?>
								<div class="usam_customization__step <?php echo $this->check_step( $key )?'step_completed':''; ?>">
									<div class="usam_customization__icon">
										<span class="dashicons dashicons-yes-alt"></span>
									</div>  
									<div class="usam_customization__content">
										<a href="<?php echo $step['url']; ?>" class="usam_customization__title" target="_blank" rel="noopener"><?php echo $step['name']; ?></a>  
										<?php if ( !empty($step['description']) ) { ?>
											<a href="<?php echo $step['url']; ?>" class="usam_customization__description" target="_blank" rel="noopener"><?php echo $step['description']; ?></a>  
										<?php } ?>
									</div>  
								</div>     
								<?php
							}							
						}
						?>
					</div>
				</div><?php	
			}
			?>
		</div>		
		<?php	
	}	
	
	private function check_step( $step ) 
	{
		switch ( $step ) 
		{		
			case 'product' :
				return usam_get_products( array('fields' => 'ids', 'posts_per_page' => 1, 'stocks_cache' => false, 'prices_cache' => false) );
			break;	
			case 'your_company_details' :
				return get_option( 'usam_shop_location' ) && get_option( 'usam_shop_company' );
			break;
			case 'company' :
				return get_option( 'usam_shop_location' ) && get_option( 'usam_shop_company' );
			break;			
			case 'parser' :
				require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');
				return usam_get_parsing_sites( );
			break;		
			case 'semantic_core' :
				require_once( USAM_FILE_PATH . '/includes/seo/keywords_query.class.php' );	
				return usam_get_keywords( );
			break;				
			case 'type_price' :
				$type_prices = usam_get_prices( );
				return count($type_prices);
			break;	
			case 'emails' :
				return usam_get_mailboxes( );
			break;	
			case 'phone' :
				return usam_get_shop_phone();
			break;
			case '1c' :
				$option = get_option('usam_1c');
				return !empty($option['active']);
			break;
			case 'moysklad' :
				$services = usam_get_applications(['service_code' => 'moysklad']);
				return !empty($services);
			break;			
			case 'sms' :
				$sms = get_option('usam_sms_gateway_option');
				if ( !empty($sms['login']) && !empty($sms['password']) )		
					return true;
			break;	
			case 'notification' :
				$notifications = maybe_unserialize( get_site_option('usam_notifications') );	
				return !empty($notifications );
			break;			
			case 'chat' :
				return usam_get_social_network_profiles( );
			break;	
			case 'payment' :
				require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
				return usam_get_payments( );
			break;				
			case 'delivery' :
				return usam_get_delivery_services( );
			break;		
			case 'order_properties' :
				return true;
			break;		
			case 'product_attributes' :
				$product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => 'usam-product_attributes']);
				return count($product_attributes);
			break;	
			case 'social_networks' :
				$vk_api = get_option( 'usam_vk_api' );	
				if ( !empty($vk_api['client_id']) )
					return true;			
			break;	
			case 'metrika' :
				$yandex = get_option('usam_yandex');
				if ( !empty($yandex['metrika']) )		
					return true;
			break;	
			case 'sales_area' :
				$sales_area = usam_get_sales_areas();
				if ( !empty($sales_area) )		
					return true;
			break;			
		}		
		return false;
	}
}
?>