<?php
class USAM_Tab_orders extends USAM_Page_Tab
{			
	public function __construct()
	{			
		$this->views = [];
		if( current_user_can( 'grid_document' ) )
			$this->views[] = 'grid';						
		$this->views[] = 'table';
		if( current_user_can( 'map_document' ) )
			$this->views[] = 'map';	
		if( current_user_can( 'report_document' ) )
			$this->views[] = 'report';	
		if( current_user_can( 'setting_document' ) )
			$this->views[] = 'settings';			
		
		add_filter('screen_settings', [$this, 'screen_settings'], 100, 2  );		
	}	
	
	public function get_title_tab()
	{			
		if ( $this->view == 'map' )
			return __('Заказы на карте', 'usam');
		elseif ( $this->view == 'report' )
			return __('Отчеты по заказам', 'usam');		
		elseif ($this->table == 'purchase_rules' )
			return __('Правила покупки', 'usam');
		elseif ($this->table == 'orders_export' )
			return __('Шаблоны экспорта заказов', 'usam');
		elseif ($this->table == 'order_import' )
			return __('Шаблоны импорта заказов', 'usam');
		elseif ($this->table == 'types_payers' )
			return __('Типы плательщиков', 'usam');
		elseif ($this->table == 'order_property_groups' )
			return __('Группы свойств', 'usam');
		elseif ($this->table == 'company_property_groups' )
			return __('Группы реквизитов компаний', 'usam');
		elseif ($this->table == 'order_properties' )
			return __('Свойства заказа', 'usam');	
		elseif ($this->table == 'shipping' )
			return __('Способы доставки', 'usam');			
		elseif ($this->table == 'payment_gateway' )
			return __('Способы оплаты', 'usam');	
		elseif ($this->table == 'order_status' )
			return __('Статусы заказов', 'usam');	
		elseif ($this->table == 'view_grouping' )
			return __('Группировка просмотра', 'usam');	
		else
			return __('Заказы', 'usam');		
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'orders_export' )		
			return [['form' => 'edit', 'form_name' => 'order_export', 'title' => __('Добавить', 'usam'), 'capability' => 'export_order']];						
		elseif ( $this->table == 'order_import' )		
			return [ 
				['form' => 'edit', 'form_name' => 'order_import', 'title' => __('Добавить', 'usam'), 'capability' => 'add_order'],
				['form' => 'progress', 'form_name' => 'order_importer', 'title' => __('Импортировать заказы', 'usam'), 'capability' => 'add_order']
			];
		elseif ( $this->table == 'purchase_rules' )		
			return [['form' => 'edit', 'form_name' => 'purchase_rule', 'title' => __('Добавить', 'usam'), 'capability' => 'setting_document']];	
		elseif ( $this->table == 'types_payers' )		
			return array( array('form' => 'edit', 'form_name' => 'type_payer', 'title' => __('Добавить', 'usam'), 'capability' => 'setting_document' ) );			
		elseif ( $this->table == 'order_property_groups' )		
			return array( array('form' => 'edit', 'form_name' => 'order_property_group', 'title' => __('Добавить', 'usam'), 'capability' => 'setting_document' ) );		
		elseif ( $this->table == 'order_properties' )		
			return array( array('form' => 'edit', 'form_name' => 'order_property', 'title' => __('Добавить', 'usam'), 'capability' => 'setting_document' ) );		
		elseif ( $this->table == 'shipping' )		
			return array( array('form' => 'edit', 'form_name' => 'shipping', 'title' => __('Добавить способ доставки', 'usam'), 'capability' => 'setting_document' ) );	
		elseif ( $this->table == 'payment_gateway' )		
			return array( array('form' => 'edit', 'form_name' => 'payment_gateway', 'title' => __('Добавить', 'usam'), 'capability' => 'setting_document' ) );	
		elseif ( $this->table == 'order_status' )		
			return array( array('form' => 'edit', 'form_name' => 'order_status', 'title' => __('Добавить', 'usam'), 'capability' => 'setting_document' ) );	
		elseif ( $this->table == 'view_grouping' )		
			return array( array('form' => 'edit', 'form_name' => 'view_grouping', 'title' => __('Добавить группировку', 'usam'), 'capability' => 'setting_document' ) );
		elseif ( $this->table == 'orders' || $this->view == 'grid' )
			return [
				['action' => 'new', 'title' => __('Добавить', 'usam'), 'capability' => 'add_order'],
				['table' => 'order_import', 'title' => __('Импорт', 'usam'), 'capability' => 'import_order'],
				['table' => 'orders_export', 'title' => __('Экспорт', 'usam'), 'capability' => 'export_order'],
				['button' => 'compare_invoices', 'title' => __('Сравнить с накладными', 'usam')],
			];
		return [];
	}	
	
	public function get_tab_sections() 
	{ 
		$tables = array();
		if ( $this->view == 'settings' )
		{ 
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );						
		}
		return $tables;
	}
	
	function help_tabs() 
	{	
		$help = array( 'capabilities' => __('Возможности', 'usam'), 'search' => __('Поиск', 'usam'), 'panel' => __('Контекстная панель', 'usam') );
		return $help;
	}
		
	public function screen_settings( $screen_settings, $t ) 
	{ 		
		$type_price = usam_get_manager_type_price();
		return 
		"<fieldset class='viewing'>
			<legend>".__("Тип цены при создании заказа", "usam")."</legend>
			<div class='viewing_options'>
				".usam_get_select_prices( $type_price )."
			</div>
		</fieldset>";
	}
	//'view_grouping' => ['title' => __('Группировка просмотра','usam'), 'type' => 'table'],
	public function get_settings_tabs() 
	{ 
		return [
			'order_status' => ['title' => __('Статусы заказов','usam'), 'type' => 'table'], 
			'types_payers' => ['title' => __('Типы плательщиков','usam'), 'type' => 'table'], 					
			'order_property_groups' => ['title' => __('Группы свойств','usam'), 'type' => 'table'], 
			'order_properties' => ['title' => __('Свойства','usam'), 'type' => 'table'], 			
			'shipping' => ['title' => __('Способы доставки','usam'), 'type' => 'table'], 
			'payment_gateway' => ['title' => __('Способы оплаты','usam'), 'type' => 'table'], 			
			'transaction_results' => ['title' => __('Результаты покупки','usam'), 'type' => 'section'],
			'purchase_rules' => ['title' => __('Правила покупки','usam'), 'type' => 'table'], 
		];
	}
	
	private function print_tab( $key, $message ) 
	{	
		?>
		<div id = "message_<?php echo $key; ?>" class="tab">
			<?php 
			$transaction_results = get_option( 'usam_page_transaction_results' );
			if ( !empty($transaction_results[$key]) )
				$text = $transaction_results[$key];
			else
				$text = $message['text'];
			
			wp_editor(stripslashes(str_replace('\\&quot;','',$text )),'usam_message_transaction_'.$key,array(
				'textarea_rows' => 30,
				'textarea_name' => 'usam_options[page_transaction_results]['.$key.']',
				'media_buttons' => false,
				'tinymce' => array( 'theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
				)	
			);
			?>			
		</div>		
		<?php 
	}
	
	public function display_section_transaction_results() 
	{			
		usam_list_order_shortcode(); 
		
		$message_transaction = usam_get_message_transaction();			
		?>		
		<div id = "tabs_transaction_results" class = "usam_tabs usam_tabs_style1">
			<div class = "header_tab">
				<?php 									
				foreach ( $message_transaction as $key => $message )
				{						
					?><a class = "tab" href="#message_<?php echo $key; ?>"><?php echo $message['title']; ?></a><?php									
				}	
				?>
			</div>	
			<div class = "countent_tabs">	
				<?php						
				foreach ( $message_transaction as $key => $message )
				{				
					$this->print_tab( $key, $message );					
				}
				?>
			</div>
		</div>
		<?php
	}
	
	public function add_to_footer( ) 
	{ 		
		if ( $this->view == 'table' && $this->table == 'orders' )
		{
			wp_enqueue_style( 'usam-progress-form' );			
			$filename = USAM_FILE_PATH . "/admin/includes/modal/compare_invoices.php";	
			require_once( apply_filters( 'usam_admin_modal_file', $filename, 'compare_invoices' ) );	
		}
		
	}
}