<?php
class USAM_Tab_purchase extends USAM_Tab
{	
	protected $views = ['simple'];
	public function get_title_tab()
	{			
		return __('Заказ', 'usam');	
	}	
		
	public function display() 
	{
		usam_add_box( 'usam_document_order', __('Настройки документа заказ','usam'), array( $this, 'display_document_order' ) );		
		usam_add_box( 'usam_inventory_control', __('Складской учет','usam'), [$this, 'inventory_control']);	
		usam_add_box( 'usam_bonus_rules', __('Правило использование бонусов','usam'), array( $this, 'bonus_rules' ) );
		if ( usam_check_type_product_sold( 'electronic_product' ) )
			usam_add_box( 'usam_download_file', __('Загрузка файлов', 'usam'), array( $this, 'download_file' ) );	
		usam_add_box( 'usam_checkout_options', __('Оформления заказа', 'usam'), array( $this, 'checkout_options' ) );				
	}	
	
	public function checkout_options()
	{
		$options = [ 
			['type' => 'radio', 'title' => __('Регистрация при покупки', 'usam'), 'option' => 'registration_upon_purchase', 'default' => 'not_require', 'radio' => ['not_require' => __('Не требовать', 'usam'), 'suggest' => __('Предлагать', 'usam'), 'require' => __('Обязательная', 'usam'), 'automatic' => __('Автоматическая после покупки', 'usam')] ],
		];
		$this->display_table_row_option( $options );		
	}
	
	public function download_file() 
	{				
		$options = array( 
			['type' => 'input', 'title' => __('Максимальное число загрузок для файла', 'usam'), 'option' => 'max_downloads', 'default' => 1, 'attribute' => ['maxlength' => "3", 'size' => "3"]],
			['type' => 'radio', 'title' => __('Блокировка загрузки на IP-адрес', 'usam'), 'option' => 'ip_lock_downloads', 'default' => 0],					
		 );
		$this->display_table_row_option( $options );		
	}
	
	public function display_document_order() 
	{
		$type_prices = usam_get_prices(['fields' => 'code=>title']);
		$type_prices = array_merge(['' => __('Не контролировать', 'usam')], $type_prices );
		$options = [	
			['type' => 'input', 'title' => __('Дней отсрочки', 'usam'), 'option' => 'number_days_delay_payment', 'default' => 3, 'description' => __('Количество дней отсрочки оплаты', 'usam'), 'attribute' => ['maxlength' => "3", 'size' => "3"]],				
			['type' => 'select', 'title' => __('Минимальная цена продажи', 'usam'), 'option' => 'min_selling_price_product', 'options' => $type_prices],
		];
		$this->display_table_row_option( $options );	 
	}
	
	public function bonus_rules() 
	{			
		$options = [
			['type' => 'radio', 'key' => 'generate_cards', 'title' => __('Создавать карты', 'usam'), 'option' => 'bonus_rules', 'description' => __( "Автоматически создавать бонусные карты.", 'usam'), 'default' => 0],
			['type' => 'input', 'key' => 'percent', 'title' => __('Оплата бонусами', 'usam'), 'option' => 'bonus_rules', 'description' => __('Процент от заказа, который можно оплачивать бонусами', 'usam'), 'attribute' => ['maxlength' => "5", 'size' => "5"]],
			['type' => 'input', 'key' => 'activation_date', 'title' => __('Дата активации бонусов', 'usam'), 'option' => 'bonus_rules', 'description' => __('Бонусы, которые начисляются за покупку будут активированы через указанное количество дней', 'usam'), 'attribute' => ['maxlength' => "5", 'size' => "5"], 'default' => 14],
			['type' => 'radio', 'key' => 'bonus_coupon', 'title' => __('Купоны вместе с бонусами', 'usam'), 'option' => 'bonus_rules', 'description' => __( "Можно ли использовать купоны и бонусы вместе.", 'usam'), 'default' => 0],
			['type' => 'radio', 'key' => 'exclude_discount_products', 'title' => __('Исключить товары на акции', 'usam'), 'option' => 'bonus_rules', 'description' => '', 'default' => 1],		
		];
		$this->display_table_row_option( $options );
	}
	
	public function inventory_control() 
	{				
		$options = [];
		if ( usam_check_type_product_sold( 'product' ) )
		{
			$reservation_storage = array();
			foreach( usam_get_storages() as $storage)
			{					
				$reservation_storage[$storage->id] = $storage->title;
			}		
			$options = [
				['type' => 'radio', 'title' => __('Складской контроль', 'usam'), 'option' => 'inventory_control', 'description' => __( "Если складской контроль выбран, то остатки увеличиваются документами поступления, иначе вручную.", 'usam'), 'default' => 0],
				['type' => 'radio', 'title' => __('Отгружать со склада, на котором нет товаров', 'usam'), 'option' => 'accurate_inventory_control','description' => __( "Если выбрано, то можно делать отгрузку со склада, на котором нет товаров, при условии наличие товара на других складах.", 'usam'), 'default' => 1],
				['type' => 'input', 'title' => __('Хранение корзин', 'usam'), 'option' => 'time_keeping_baskets', 'description' => __( "Установите время хранения товаров в корзине клиентов. Вы также можете указать десятичные, такие как '0.5 дней'.", 'usam'), 'attribute' => ['maxlength' => "5", 'size' => "5"]],
				['type' => 'select', 'title' => __('Склад резервирования', 'usam'), 'option' => 'default_reservation_storage', 'options' => $reservation_storage, 'description' =>  __('Склад резервирования по умолчанию', 'usam')],	
				['type' => 'select', 'title' => __('Товар резервируется', 'usam'), 'option' => 'product_reserve_condition', 
					'options' => ['' => __('Не резервируется', 'usam'), 'o' => __('При оформлении заказа', 'usam'), 'p' => __('При полной оплате заказа', 'usam')],
					'description' =>  __('Настройка резервирования товара', 'usam')],
				['type' => 'input', 'title' => __('Снятие резервов', 'usam'), 'option' => 'product_reserve_clear_period', 'description' => __( "Установите время храниния резервов в днях", 'usam'), 'attribute' => ['maxlength' => "5", 'size' => "5"]],
			 ];
		}
		$options[] = ['type' => 'input', 'title' => __('Хранение корзин', 'usam'), 'option' => 'time_keeping_baskets', 'description' => __( "Установите время хранения товаров в корзине клиентов. Вы также можете указать десятичные, такие как '0.5 дней'.", 'usam'), 'attribute' => ['maxlength' => "5", 'size' => "5"]];		
		$this->display_table_row_option( $options );
	}
}
?>