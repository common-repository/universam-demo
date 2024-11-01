<?php
// Класс работает ajax в админском интерфейсе
require_once( USAM_FILE_PATH .'/includes/ajax.php' );
class USAM_Admin_Ajax extends USAM_Callback
{				
	protected $query = 'usam_ajax_action';	
	
	public function __construct() 
	{			
		add_action( 'wp_ajax_usam_ajax', [$this, 'handler_ajax'] );					
	}
			
	//Сравнить накладную с заказами
	function controller_compare_invoices()
	{
		$file = sanitize_text_field($_POST['file']);				
		$file_path =  USAM_UPLOAD_DIR."exchange/".$file;			
		$data = usam_read_file( $file_path );			
		$results = array();	
		if ( !empty($_POST['template_id']) )
		{
			$template_id = absint($_POST['template_id']);	
			require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
			$rule = usam_get_exchange_rule( $template_id );
			$metas = usam_get_exchange_rule_metadata( $rule['id'] );
			foreach($metas as $metadata )
				$rule[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
			$columns = $rule['columns'];			
		}	
		else
			$columns = array_map('sanitize_title', $_POST['columns']);	
		foreach ( $data as $rows ) 
		{
			foreach ( $rows as $column => $value ) 
			{				
				if ( $value && !empty($columns[$column]) )
				{
					if ( $columns[$column] == 'sku' )
					{		
						if ( $value != 'sku' || $value != __('Артикул','usam') )
							$results['sku'][] = trim($value);
					}
					elseif ( $columns[$column] == 'barcode' )
					{		
						if ( $value != 'barcode' || $value != __('Штрихкод','usam') )
							$results['barcode'][] = trim($value);
					}
				}
			}
		}
		$args = ['order_status' => 'job_dispatched'];
		$customer_id = sanitize_text_field($_POST['customer_id']);
		if ( $customer_id )
		{
			$customer_id = explode('-', $customer_id);
			$args[$customer_id[0]] = absint($customer_id[1]);
		}
		require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
		$products_order = usam_get_products_order_query( $args );		
		$products = [];	
		foreach ( $products_order as $product ) 
		{				
			if ( isset($products[$product->product_id]) )
				$products[$product->product_id]->quantity += $product->quantity;
			else
			{
				$product->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
				$product->url = usam_product_url( $product->product_id );
				$product->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
				$product->image = usam_get_product_thumbnail_src($product->product_id, 'small-product-thumbnail');	
				$products[$product->product_id] = $product;
			}
		}
		$found = [];
		$not_found = [];
		foreach ( $products as $product ) 
		{			
			foreach ( $results as $column => $values )
			{
				$meta = usam_get_product_meta( $product->product_id, $column );						
				if ( in_array($meta, $values) )
				{
					$found[] = $product;
					break;
				}
				else
					$not_found[] = $product;				
			}				
		}
		return ['found' => $found, 'not_found' => $not_found]; 
	}
	
	public function controller_display_category_in_attributes_product()
	{		
		if ( !empty($_POST['term_id']) )
		{
			$term_id = absint($_POST['term_id']);
			$selected = usam_get_taxonomy_relationships_by_id( $term_id, 'usam-category', 1 );				
		}
		else
			$selected = [];
		$content = wp_terms_checklist( 0, ['taxonomy' => 'usam-category', 'selected_cats' => $selected, 'checked_ontop' => false, 'echo' => false]);			
		return $content;
	}	
			
	function controller_combine_duplicate() 
	{
		require_once( USAM_FILE_PATH . '/admin/includes/elements-actions.class.php' );
		$result = USAM_Elements_Actions::start( sanitize_title($_POST['table']), 'combine' );
		return $result;
	}
	
	function controller_form_save() 
	{
		$result = false;	
		if( !empty($_POST['form_name']) && !empty($_POST['a']) )
		{		
			require_once( USAM_FILE_PATH . '/admin/includes/form-actions.class.php' );
			$action = sanitize_title($_POST['a']);			
			$result = USAM_Form_Actions::start( $action );	
			if ( !empty($result['id']) )
				$result = array_merge( $result, usam_get_callback_messages(['save' => 1]));	
			else
				$result = array_merge( $result, usam_get_callback_messages( $result ));	
		}			
		return $result;
	}	
		
	function controller_bulkactions() 
	{
		$result = false;
		if( isset($_POST['item']) && isset($_POST['a']) )
		{
			if ( !is_array($_POST['a']) )
				$actions = [ sanitize_title($_POST['a']) ];
			else
				$actions = array_map('sanitize_title', $_POST['a']);
			$item = sanitize_title($_POST['item']);		
			foreach ( $actions as $action )
			{
				require_once( USAM_FILE_PATH . '/admin/includes/elements-actions.class.php' );			
				$result = USAM_Elements_Actions::start( $item, $action );	
				$result = usam_get_callback_messages( $result );
			}
		}			
		return $result;
	}
		
	function controller_delete()
	{
		require_once( USAM_FILE_PATH . '/admin/includes/elements-actions.class.php' );
		$item = sanitize_title($_REQUEST['item']);
		return USAM_Elements_Actions::start( $item, 'delete' );
	}	
	
	function controller_get_products_table() 
	{
		if ( isset($_REQUEST['screen_id']) )
		{					
			set_current_screen( $_REQUEST['screen_id'] );
			
			$screen_id = sanitize_title($_REQUEST['screen_id']);				
			usam_update_page_sorting( $screen_id );
			
			$url = wp_get_referer();
			$_SERVER['REQUEST_URI'] = remove_query_arg(['action', 'nonce', 'usam_ajax_action'], $url );
			
			require_once( USAM_FILE_PATH. '/admin/includes/admin_menu.class.php' );	
			$menu = new USAM_Admin_Menu();
			$menu->display_products_list();
			
			$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => sanitize_key( $_REQUEST['screen_id'] ) ) );
			$list_table->prepare_items();

			ob_start();
			if ( !empty( $_REQUEST['no_placeholder'] ) )
				$list_table->display_rows();
			else 
				$list_table->display_rows_or_placeholder();			
			$rows = ob_get_clean();
					
			ob_start();
			$list_table->print_column_headers();
			$headers = ob_get_clean();
			
			ob_start();
			$list_table->print_column_footer( );
			$footer = ob_get_clean();
		 
			ob_start();
			$list_table->pagination('top');
			$pagination_top = ob_get_clean();
		 
			ob_start();
			$list_table->pagination('bottom');
			$pagination_bottom = ob_get_clean();	

			ob_start();
			$list_table->views();
			$views = ob_get_clean();	
		 
			$response = array( 'rows' => $rows );
			$response['pagination']['top'] = $pagination_top;		
			$response['pagination']['bottom'] = $pagination_bottom;		
			$response['column_headers'] = $headers;
			$response['views'] = $views;
			$response['column_footer'] = $footer;
			if ( isset($list_table->_pagination_args['total_items'] ) ) {
				$response['total_items_i18n'] = sprintf(
					_n( '%s item', '%s items', $list_table->_pagination_args['total_items'] ),
					number_format_i18n( $list_table->_pagination_args['total_items'] )
				);
			}
			if ( isset($list_table->_pagination_args['total_pages'] ) ) {
				$response['total_pages']      = $list_table->_pagination_args['total_pages'];
				$response['total_pages_i18n'] = number_format_i18n( $list_table->_pagination_args['total_pages'] );
			}				
			return $response;		
		}	
		return new stdClass();
	}
			
	function controller_get_list_table()
	{
		if ( isset($_REQUEST['table']) )
		{			
			$url = wp_get_referer();
			$_SERVER['REQUEST_URI'] = remove_query_arg(['action', 'nonce', 'usam_ajax_action', 'usam_ajax'], $url );
			
			if ( isset($_REQUEST['screen_id']) )
			{
				$screen_id = sanitize_title($_REQUEST['screen_id']);				
				usam_update_page_sorting( $screen_id );	 
			}	
			$list_table = usam_get_table( sanitize_title($_REQUEST['table']) );				
			if ( $list_table )
				return $list_table->ajax_response();
		}		 
		return new stdClass();
	}

	private function get_export_table( $list_table )
	{			
		if ( isset($_REQUEST['page']) && isset($_REQUEST['table']) )
		{			
			$page = sanitize_title($_REQUEST['page']);	
			$table = sanitize_title($_REQUEST['table']);	
			$class_name_table = 'USAM_Export_List_Table_'.$table;
			$name_file_table = $table .'_export_list_table';					
			$export_list_table_file = USAM_FILE_PATH .'/admin/export-list-table/'.$name_file_table.'.php';	
			if ( !file_exists($export_list_table_file) )		
			{
				$class_name_table = 'USAM_Export_List_Table_'.$page;
				$name_file_table = $page .'_export_list_table';			
				$export_list_table_file = USAM_FILE_PATH .'/admin/export-list-table/'.$name_file_table.'.php';				
				if ( file_exists($export_list_table_file) )		
					require_once( $export_list_table_file );			
				else
				{
					require_once( USAM_FILE_PATH .'/admin/includes/usam_export_list_table.class.php' );	
					$class_name_table = 'USAM_Export_List_Table';					
				}
			}	
			else
				require_once( $export_list_table_file );	
			$args = array( 'class_table' => $list_table );	
			$print_list_table = new $class_name_table( $args );			
			return $print_list_table;		
		}
	}	
	
	public function controller_print_table() 
	{
		if ( isset($_REQUEST['table']) )
		{
			$list_table = usam_get_table( sanitize_title($_REQUEST['table']) );
			$list_table->set_per_page( 0 );	
			$print_list_table = $this->get_export_table( $list_table );		
				
			$title_page = sanitize_text_field(stripslashes($_POST['title']));	
						
			ob_start();
			require_once( USAM_FILE_PATH .'/admin/includes/print/printing_table_form.php' );
			return ob_get_clean();	
		}		
	}
	
	public function controller_export_table_to_excel() 
	{	
		if ( isset($_REQUEST['table']) )
		{
			global $hook_suffix;
			$hook_suffix = '';

			$title = sanitize_text_field(stripslashes($_POST['title']));
			$list_table = usam_get_table( sanitize_title($_REQUEST['table']) );	
			$list_table->set_per_page( 0 );				
			$list_table->prepare_items();	
			$excel_list_table = $this->get_export_table( $list_table );					
		
			list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();	
			if ( isset($columns['cb']) )
				unset($columns['cb']);

			$excel_items = array();				
			$items = $list_table->items;	
			foreach ( $items as $item )
			{				
				$excel_items[] = $excel_list_table->get_row_columns( $item );				
			}	
			require_once( USAM_FILE_PATH . '/admin/includes/exported.class.php' );
			$exported = new USAM_Exported_Table( $title );	
			
			ob_start();
			$exported->excel( $excel_items, $columns );
			$xlsData = ob_get_clean();	
			return ["download" => "data:application/vnd.ms-excel;base64,".base64_encode($xlsData), 'title' => $title.".xlsx"];
		}
		return false;
	}
	
	protected function controller_download_folder() 
	{
		$folder_id = sanitize_text_field( $_REQUEST['id'] );	
		$folder = usam_get_folder( $folder_id );	
		usam_get_folders(['child_of' => $folder_id, 'cache_results' => true]); // КЕШИРОВАНИЕ		
		$folder_path = usam_zip_archive_folder( $folder_id );			
		ob_start();
		readfile($folder_path);
		$data = ob_get_clean();	
		unlink( $folder_path );
		return ["download" => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $folder['name'].".zip"];
	}
	
	protected function controller_download_file() 
	{
		$id = sanitize_text_field( $_REQUEST['id'] );	
		$file = usam_get_file( $id );
		ob_start();
		readfile(USAM_UPLOAD_DIR.$file['file_path']);
		$data = ob_get_clean();	
		return ["download" => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $file['file_name']];
	}
	
	public function controller_get_modal()
	{			
		$template = !empty($_REQUEST['template'])?sanitize_title($_REQUEST['template']):sanitize_title($_REQUEST['modal']);
		$filename = USAM_FILE_PATH . "/admin/includes/modal/$template.php";	
		$filename = apply_filters( 'usam_admin_modal_file', $filename, $template  );
		if ( file_exists($filename) ) 
		{ 
			ob_start();								
			require_once( $filename );	
			return ob_get_clean();	
		}
		else
			return usam_get_callback_messages(['ready' => 0]);
	}
	
	function controller_get_map_data()
	{		
		$points = array();		
		if ( usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE') )
		{
			$tab = isset($_POST['tab']) ? sanitize_key( $_POST['tab'] ) : '';	
			$file = USAM_FILE_PATH . "/admin/map-view/{$tab}_map_view.class.php";
			if ( file_exists($file) )
			{
				require_once( $file );
				$class = "USAM_{$tab}_Map_View";
				$map = new $class( );
				$points = $map->prepare_items();						
			}
		}
		return array( 'points' => $points );
	}	
	
	function controller_add_pick_group()
	{				
		$tab = isset($_POST['tab']) ? sanitize_key( $_POST['tab'] ) : '';	
		$ids = array_map('intval', (array)$_POST['ids']);
		$group = absint($_POST['group']);
		if ( !empty($ids) )
		{
			foreach ( $ids as $id )
			{
				switch ( $tab ) 
				{		
					case 'contacts' :
						usam_set_groups_object( $id, 'contact', $group );	
					break;		
					case 'companies' :
						usam_set_groups_object( $id, 'company', $group );
					break;					
				}
			}	
			return true;			
		}
		return false;
	}	
		
	
	function controller_get_sms_sending_form()
	{	
		$args = array();
		if ( !empty($_POST['to_phone']) )
			$args['to_phone'] = explode(',', $_POST['to_phone']);		
		if ( !empty($_POST['object_type']) && !empty($_POST['object_id']) )
		{
			$object_type = sanitize_title($_POST['object_type']);
			$args['object_id'] = absint($_POST['object_id']);
			$args['object_type'] = $object_type;			
			$args['to_email'] = array();
			switch ( $object_type ) 
			{		
				case 'document' :
					$document = usam_get_document( $args['object_id'] );	
					$contacts = usam_get_contacts_document( $args['object_id'] );									
					if ( $document['customer_id'] )
					{
						if ( $document['customer_type'] == 'company' )
							$args['to_phone'] = usam_get_company_phones( $document['customer_id'], false, 'mobile_phone' );
						else
							$args['to_phone'] = usam_get_contacts_communication( $document['customer_id'], 'mobile_phone' );
					} 
					if ( !empty($contacts) )
					{
						$phones = usam_get_contacts_communication( $contacts, 'mobile_phone' );
						$args['to_phone'] = array_merge( $args['to_phone'], $phones );	
					}		
				break;	
				case 'order' :
					$properties = usam_get_properties( array( 'type' => 'order', 'field_type' => 'mobile_phone' ) );		
					foreach ( $properties as $property )
					{	 
						$value = usam_get_order_metadata( $args['object_id'], $property->code );	
						if ( $value != '' )
							$args['to_phone'][$value] = $value;
					}						
				break;
				case 'lead' :
					require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
					$list_properties = usam_get_properties(['type' => 'order', 'field_type' => 'mobile_phone']);	
					foreach ( $list_properties as $property )
					{	 
						$value = usam_get_lead_metadata( $args['object_id'], $property->code );	
						if ( $value != '' )
							$args['to_phone'][$value] = $value;
					}	
					$args['object_type'] = 'lead';						
				break;	
				case 'review' :
					$email = usam_get_review_metadata( $args['object_id'], 'mobile_phone' );			
					if ( $email )
						$args['to_phone'][$email] = $email;						
					$args['object_type'] = 'review';						
				break;				
				case 'event' :
					$objects = usam_get_event_links( $args['object_id'] );		
					$contact_ids = array();
					$company_ids = array();
					foreach ( $objects as $object )
					{
						if ( $object->object_type == 'company' )
						{
							$company_ids[] = $object->object_id;
						}
						elseif ( $object->object_type == 'contact' )
						{ 	
							$contact_ids[] = $object->object_id;
						}							
					}		
					if ( !empty($contact_ids) )
						$args['to_phone'] = usam_get_contacts_communication( $contact_ids, 'mobile_phone' );
					
					if ( !empty($company_ids) )
					{
						$emails = usam_get_companies_communication( $company_ids, false, 'mobile_phone' );			
						$args['to_phone'] = array_merge( $args['to_phone'], $emails );
					}	
				break;						
			}
		}
		return usam_get_modal_window( __('Отправить сообщение','usam'), 'send_sms', usam_get_form_send_sms( $args ) );
	}	

	function controller_get_email_sending_form()
	{		
		$args = array();
		if ( !empty($_POST['to_email']) )
			$args['to_email'] = explode(',', $_POST['to_email']);		
		if ( !empty($_POST['object_type']) )
		{
			$object_type = sanitize_title($_POST['object_type']);
			if ( !empty($_POST['object_id']) )
			{
				$args['object_id'] = absint($_POST['object_id']);
				$args['object_type'] = $object_type;	
				$args['to_email'] = array();
				switch ( $object_type ) 
				{		
					case 'document' :
						$args['form_url'] = usam_url_admin_action( 'send_email', ['do_action_send_email' => 'document', 'id' => $args['object_id']] );
						$document = usam_get_document( $args['object_id'] );	
						$contacts = usam_get_contacts_document( $args['object_id'] );									
						if ( $document['customer_id'] )
						{
							if ( $document['customer_type'] == 'company' )
								$args['to_email'] = usam_get_companies_emails( $document['customer_id'], false );	
							else
								$args['to_email'] = usam_get_contacts_emails( $document['customer_id'] );		
						} 
						if ( !empty($contacts) )
						{
							$emails = usam_get_contacts_emails( $contacts );
							$args['to_email'] = array_merge( $args['to_email'], $emails );	
						}		
						$name = usam_get_document_name( $document['type'] );
						if ( $name )
							$args['title'] = "$name №".$document['number'];
						$args['type_file'] = 'document';		
					break;	
					case 'order' :
						$properties = usam_get_properties( array( 'type' => 'order', 'field_type' => 'email' ) );		
						foreach ( $properties as $property )
						{	 
							$value = usam_get_order_metadata( $args['object_id'], $property->code );	
							if ( $value != '' )
								$args['to_email'][$value] = $value;
						}					
						$shipped_documents = usam_get_shipping_documents_order( $args['object_id'] );	
						$shipped_document = (array)array_pop($shipped_documents);	
						if ( empty($shipped_document['storage_pickup']) )
						{
							$storage_pickup_name = __('Не выбрано', 'usam');
							$storage_pickup_address = ''; 
							$storage_pickup_phone = ''; 			
						}
						else
						{
							$storage = usam_get_storage( $shipped_document['storage_pickup'] ); 
							$storage_pickup_name = isset($storage['title'])?$storage['title']:''; 
							$storage_pickup_address = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'address')); 
							$storage_pickup_phone = usam_get_storage_metadata( $storage['id'], 'phone'); 
						}
						$readiness_date = usam_get_shipped_document_metadata( $shipped_document['id'], 'readiness_date' );
						$readiness_date = $readiness_date?'':get_date_from_gmt($readiness_date, "d.m.Y H:i");
						$date_allow_delivery = usam_get_shipped_document_metadata($shipped_document['id'], 'date_allow_delivery');
						$date_allow_delivery = $date_allow_delivery?get_date_from_gmt($shipped_document['date_allow_delivery'], "d.m.Y H:i"):'';
						$args['title'] = sprintf( __('Сообщение о вашем заказе №%s','usam'),$args['object_id'])." - ".get_bloginfo('name');
						$args['insert_text'] = array( 
							'_order_id' => array( 'title' => __('Номер заказа','usam'), 'data' => $args['object_id'] ), 
							'_readiness_date' => array( 'title' => __('Дата готовности','usam'), 'data' => $readiness_date ), 
							'_storage_pickup' => array( 'title' => __('Офис получения','usam'), 'data' => $storage_pickup_name ), 
							'_storage_pickup_address' => array( 'title' => __('Адрес склада отгрузки','usam') , 'data' => $storage_pickup_address ), 
							'_storage_pickup_phone' => array( 'title' => __('Телефон склада отгрузки','usam'), 'data' => $storage_pickup_phone ), 
							'_date_allow_delivery' => array( 'title' => __('Дата и время доставки','usam'), 'data' => $date_allow_delivery ), 
						);
					break;
					case 'lead' :
						$list_properties = usam_get_properties(['type' => 'order', 'field_type' => 'email']);		
						require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
						foreach ( $list_properties as $property )
						{	 
							$value = usam_get_lead_metadata( $args['object_id'], $property->code );	
							if ( $value != '' )
								$args['to_email'][$value] = $value;
						}					
					break;	
					case 'review' :
						$email = usam_get_review_metadata( $args['object_id'], 'mail' );			
						if ( $email )
							$args['to_email'][$email] = $email;													
					break;				
					case 'event' :
						$objects = usam_get_event_links( $args['object_id'] );		
						$contact_ids = array();
						$company_ids = array();
						foreach ( $objects as $object )
						{
							if ( $object->object_type == 'company' )
							{
								$company_ids[] = $object->object_id;
							}
							elseif ( $object->object_type == 'contact' )
							{ 	
								$contact_ids[] = $object->object_id;
							}							
						}		
						if ( !empty($contact_ids) )
							$args['to_email'] = usam_get_contacts_emails( $contact_ids );
						
						if ( !empty($company_ids) )
						{
							$emails = usam_get_companies_emails( $company_ids );			
							$args['to_email'] = array_merge( $args['to_email'], $emails );
						}	
					break;							
				}
			}
			if ( $object_type == 'file' ) 
			{
				$args['object_id'] = 0;
				$args['object_type'] = '';
				$args['upload'] = false;
			}
		}	
		ob_start();								
		require_once( USAM_FILE_PATH . "/admin/includes/modal/send_mail.php" );	
		return ob_get_clean();			
	}
	
	public function controller_change_group_price()
	{
		$result = 0;
		if ( !empty($_POST['markup']) )
		{ 
			$query = ['tax_query' => []];
			foreach (['category', 'brands', 'category_sale', 'catalog'] as $tax_slug )
			{
				if ( !empty($_POST[$tax_slug]) )
					$query['tax_query'][] = ['taxonomy' => 'usam-'.$tax_slug, 'field' => 'id', 'terms' => [absint($_POST[$tax_slug])], 'operator' => 'IN'];
			}		
			if ( $query )
			{
				$type_price = sanitize_text_field($_POST['type_price']);						
				$markup = absint($_POST['markup']);	
				$operation = $_POST['operation']=='+'?'+':'-';	
				$count = usam_get_total_products( $query );		
				if ( $count )
					$result = usam_create_system_process( __("Изменение цены","usam"),['query' => $query, 'markup' => $markup, 'operation' => $operation, 'type_price' => $type_price], 'price_change', $count, 'price_change_'.time());	
			}
		}
		return usam_get_callback_messages(['add_event' => $result]);
	}
	
	public function controller_change_product_price()
	{
		if ( empty($_POST['products']) )
			return usam_get_callback_messages(['ready' => 0]);
		
		$products_data_post = $_POST['products'];				
		$code_price = sanitize_text_field($_POST['code_price']);
		
		$products_data = array();
		$product_ids = array();
		foreach ( $products_data_post as $product ) 
		{
			$product_id = absint($product['product_id']);
			$price = (float)$product['price'];		
			$products_data[$product_id] = $price;
			$product_ids[] = $product_id;
		}
		usam_cache_current_product_discount( $product_ids );
		$products = usam_get_products(['post__in' => $product_ids]);
		$i = 0;
		foreach ( $products as $product ) 
		{			
			$prices['price_'.$code_price] = $products_data[$product->ID];
			$products_parent = usam_get_products(['post_parent' => $product->ID]);				
			if ( !empty($products_parent) )
			{
				$product_ids = array();
				foreach ( $products_parent as $product_parent ) 
				{
					$product_ids[] = $product_parent->ID;
				}
				usam_cache_current_product_discount( $product_ids );
				foreach ( $products_parent as $product_parent ) 
				{			
					usam_edit_product_prices( $product_parent->ID, $prices );	
				}	
			}						
			usam_edit_product_prices( $product->ID, $prices );		
			$i++;				
		}
		return usam_get_callback_messages(['update_product' => $i]);
	}
	
	public function controller_add_posts_ok()
	{		
		if ( empty($_POST['ids']) )
			return usam_get_callback_messages(['ready' => 0]);
	
		$profile_id  = sanitize_text_field($_POST['profile_id']);
		$ids = array_map('intval', (array)$_POST['ids']);		
		if ( !empty($_POST['message_format']) )
			$args['message_format'] = sanitize_textarea_field(stripslashes($_POST['message_format']));
				
		if ( isset($_POST['link']) )
			$args['add_link'] = absint( $_POST['link'] );
		$i = 0;	
		
		require_once( USAM_APPLICATION_PATH . '/social-networks/ok_api.class.php' );				
		if ( is_numeric($profile_id) )
		{
			$ok = new USAM_OK_API( $profile_id );
			foreach ( $ids as $id )
			{	
				if( $ok->publish_post( $id, $args ) )
					$i++;
			}
			$ok->set_log_file();
		}
		else
		{
			$profiles = usam_get_social_network_profiles(['type_social' => $profile_id]);	
			if ( $profiles )
			{
				foreach ( $profiles as $profile ) 
				{
					$ok = new USAM_OK_API( (array)$profile );
					foreach ( $ids as $id )
					{	
						if( $ok->publish_post( $id, $args ) )
							$i++;
					}
				}
				$ok->set_log_file();
			}
		}			
		return usam_get_callback_messages(['updated' => $i]);
	}
		
	public function controller_add_products_fb()
	{		
		if ( empty($_POST['ids']) )
			return usam_get_callback_messages(['ready' => 0]);
		
		$ids = array_map('intval', $_POST['ids']);		
		$group_id = sanitize_text_field($_POST['group_id']);	
		$category_id  = absint($_POST['category_id']);
		$album_id  = absint($_POST['album_id']);	
		$i = 0;			
		require_once( USAM_APPLICATION_PATH . '/social-networks/facebook_api.class.php' );
		if ( is_numeric($group_id) )
		{	
			$vkontakte = new USAM_Facebook_API( $group_id );			
			foreach ( $ids as $id )
			{		
				if( $vkontakte->publish_product( $id, $category_id, $album_id ) )
					$i++;
			}	
		}
		else
		{
			$profiles = usam_get_social_network_profiles( array( 'type_social' => 'vk_group' ) );	
			foreach ( $profiles as $profile ) 
			{
				$vkontakte = new USAM_Facebook_API( (array)$profile );
				foreach ( $ids as $id )
				{	
					if( $vkontakte->publish_product( $id, $category_id, $album_id ) )
						$i++;
				}
			}
		}
		return usam_get_callback_messages(['updated' => $i]);
	}
	
	public function controller_add_products_vk()
	{		
		if ( empty($_POST['ids']) )
			return usam_get_callback_messages(['ready' => 0]);
		
		$ids = array_map('intval', $_POST['ids']);		
		$group_id = sanitize_text_field($_POST['group_id']);	
		$category_id  = absint($_POST['category_id']);
		$album_id  = absint($_POST['album_id']);	
		$i = 0;			
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
		if ( is_numeric($group_id) )
		{	
			$vkontakte = new USAM_VKontakte_API( $group_id );			
			foreach ( $ids as $id )
			{		
				if( $vkontakte->publish_product( $id, $category_id, $album_id ) )
					$i++;
			}	
		}
		else
		{
			$profiles = usam_get_social_network_profiles( array( 'type_social' => 'vk_group' ) );	
			foreach ( $profiles as $profile ) 
			{
				$vkontakte = new USAM_VKontakte_API( (array)$profile );
				foreach ( $ids as $id )
				{	
					if( $vkontakte->publish_product( $id, $category_id, $album_id ) )
						$i++;
				}
			}
		}
		return usam_get_callback_messages(['updated' => $i]);
	}
	
	public function controller_add_posts_vk()
	{		
		if ( empty($_POST['ids']) || !is_array($_POST['ids']) )
			return usam_get_callback_messages(['ready' => 0]);
		
		$ids = array_map('intval', $_POST['ids']);		
		$profile_id  = sanitize_text_field($_POST['profile_id']);
		$i = 0;	
		
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );				
		$args = array();			
		if ( !empty($_POST['place_sale']) )
		{
			$storage_id = absint( $_POST['place_sale'] );
			$latitude = usam_get_storage_metadata( $storage_id, 'latitude');
			$longitude = usam_get_storage_metadata( $storage_id, 'longitude');
			if ( $latitude && $longitude )
			{
				$args['lat'] = $latitude;
				$args['long'] = $longitude;
			}			
		}
		if ( !empty($_POST['date']) )
			$args['publish_date'] = strtotime($_POST['date']);	
		if ( !empty($_POST['message_format']) )
			$args['message_format'] = sanitize_textarea_field(stripslashes($_POST['message_format']));
		
		if ( isset($_POST['market']) )
			$args['market'] = absint( $_POST['market'] );
		
		if ( isset($_POST['link']) )
			$args['add_link'] = absint( $_POST['link'] );
		
		if ( !empty($_POST['services']) )
			$args['services'] = sanitize_textarea_field( $_POST['services'] );
								
		if ( is_numeric($profile_id) )
		{
			$vkontakte = new USAM_VKontakte_API( $profile_id );
			foreach ( $ids as $id )
			{	
				if( $vkontakte->publish_post( $id, $args ) )
					$i++;
			}	
		}
		else
		{
			$profiles = usam_get_social_network_profiles( array( 'type_social' => $profile_id ) );	
			foreach ( $profiles as $profile ) 
			{
				$vkontakte = new USAM_VKontakte_API( (array)$profile );
				foreach ( $ids as $id )
				{	
					if( $vkontakte->publish_post( $id, $args ) )
						$i++;
				}
			}
		}
		return usam_get_callback_messages(['add_product' => $i]);
	}	
	
	public function controller_add_image_vk()
	{
		if ( empty($_POST['profile_id']) || empty($_POST['thumb_ids']) || empty($_POST['ids']) )
			return usam_get_callback_messages(['ready' => 0]);
		
		$ids = array_map('intval', $_POST['ids']);	
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
		$profile_id  = sanitize_text_field($_POST['profile_id']);
		$vkontakte = new USAM_VKontakte_API( $profile_id );			
		$i = 0;			
		foreach ( $_POST['thumb_ids'] as $thumb_id )
		{			
			$thumb_id  = sanitize_text_field($thumb_id);
			foreach ( $ids as $id )
			{															
				if ( $vkontakte->upload_photo_album( $id, $thumb_id ) )							
					$i++;	
			}
		}
		return usam_get_callback_messages(['updated' => $i]);
	}
	
	public function controller_pointer_close()
	{		
		if ( !empty($_POST['screen']) && isset($_POST['key']) )
		{ 
			$user_id = get_current_user_id();
			$user_pointer = get_user_option( 'usam_pointer' );
			$user_pointer = empty($user_pointer)?array():(array)$user_pointer;
			
			$screen = sanitize_title($_POST['screen']);
			$key = sanitize_title($_POST['key']);			
			$user_pointer[$screen][$key] = 1;				
			return update_user_option( $user_id, 'usam_pointer', $user_pointer );	
		}
		return false;
	}	
	
	public function controller_confirm_deactivation()
	{		
		if ( !empty($_POST['reason']) && !empty($_POST['message']) )
		{
			$reason = sanitize_textarea_field(stripslashes($_POST['reason']));
			$message = sanitize_textarea_field(stripslashes($_POST['message']));
			
			$api = new USAM_Service_API();
			$result = $api->confirm_deactivation( $reason, $message );		
			return $result;
		}
		return false;
	}		
	
	public function controller_bulk_actions_system_product_attribute()
	{
		if ( empty($_POST['attributes']) )
			return false;
	
		$attributes = $_POST['attributes'];	
		$product_data = [];
		$prices = usam_get_prices(['type' => 'all']);		
		$bonus_code_price = '';
		foreach( $attributes as $attribute )
		{
			if ( !empty($attribute['key']) && $attribute['key'] == 'bonus_code_price' )
			{
				$bonus_code_price = $attribute['value'];
				break;
			}
		}			
		$result = 0;
		foreach( $attributes as $attribute )
		{
			if ( !empty($attribute['key']) )
				switch ( $attribute['key'] ) 
				{		
					case 'virtual_product_attribute' :
						$product_data['productmeta']['virtual'] = sanitize_title($attribute['value']);
					break;
					case 'usam_date_picker-insert' :
						$product_data['post_date'] = date('Y-m-d H:i:s', strtotime($attribute['value']));
					break;
					case 'not_limited_attribute' :
						$storages = usam_get_storages( );
						$product_data['product_stock'] = [];
						foreach ( $storages as $storage )
						{
							$product_data['product_stock']['storage_'.$storage->id] = USAM_UNLIMITED_STOCK;
						}
					break; 
					case 'product_views' :						
						$product_data['postmeta']['views'] = absint($attribute['value']);
					break;									
					case 'status_product' :
						$product_data['post_status'] = sanitize_title($attribute['value']);
					break;					
					case 'sticky_product' :
						$product_data['sticky'] = $attribute['value']?1:0;
					break;	
					case 'contractor' :						
						$product_data['productmeta']['contractor'] = absint($attribute['value']);
					break;	
					case 'under_order_attribute' :
						$product_data['productmeta']['under_order'] = $attribute['value']?1:0;
					break;								
					case 'unit_measure_attribute' :
						$product_data['productmeta']['unit_measure'] = sanitize_title($attribute['value']);
					break;
					case 'unit_attribute' :						
						$product_data['productmeta']['unit'] = absint($attribute['value']);
					break;
					case 'product_weight' :						
						$product_data['productmeta']['weight'] = (float)$attribute['value'];
					break;											
					case 'product_length' :
						$product_data['productmeta']['length'] = (float)$attribute['value'];
					break;	
					case 'product_width' :
						$product_data['productmeta']['width'] = (float)$attribute['value'];
					break;
					case 'product_height' :
						$product_data['productmeta']['height'] = (float)$attribute['value'];
					break;							
					case 'bonus_type' :
						foreach ( $prices as $price )
						{							
							if ( $bonus_code_price == '' || $bonus_code_price == $price['code'] )
								$product_data['meta']['product_metadata']['bonuses'][$price['code']]['type'] = $attribute['value'];
						}						
					break;
					case 'bonus_value' :							
						foreach ( $prices as $price )
						{
							if ( $bonus_code_price == '' || $bonus_code_price == $price['code'] )
								$product_data['meta']['product_metadata']['bonuses'][$price['code']]['value'] = $attribute['value'];
						}
					break;				
				}						
		}		
		if ( !empty($product_data) )
		{
			if ( empty($_POST['post__in']) )
			{				
				require_once( USAM_FILE_PATH.'/admin/includes/admin_product_query.class.php' );
				$query_vars = USAM_Admin_Product_Query::get_filter();
				$i = usam_get_total_products( $query_vars );		
				$result = usam_create_system_process( sprintf(__("Обновление свойств у %s товаров", "usam" ), $i), ['update' => $product_data, 'args' => $query_vars], 'update_system_products_attribute', $i, 'update_system_products_attribute'.time() );
				return usam_get_callback_messages(['add_event' => $result]);
			}	
			else
			{	
				$args = ['post__in' => array_map('intval', $_POST['post__in'])];
				$i = usam_update_system_products_attribute($args, $product_data );			
				return usam_get_callback_messages(['updated' => $i]);
			}
		}	
		return usam_get_callback_messages(['ready' => $result]);
	}	
		
	public function controller_bulk_actions_product_attribute()
	{
		if ( empty($_POST['attributes']) )
			return false;
		$attributes = $_POST['attributes'];		
		$product_attributes = array();				
		foreach( $attributes as $attribute )
		{			
			$slug = sanitize_title($attribute['slug']);	
			if ( is_array($attribute['value']) )
				$value = stripslashes_deep($attribute['value']);	
			else
				$value = sanitize_text_field($attribute['value']);	
			$product_attributes[$slug] = $value;
		}			
		$result = 0;
		if ( empty($_POST['post__in']) )
		{
			require_once( USAM_FILE_PATH.'/admin/includes/admin_product_query.class.php' );
			$query_vars = USAM_Admin_Product_Query::get_filter();
			$i = usam_get_total_products( $query_vars );				
			$result = usam_create_system_process( sprintf(__("Обновление свойств у %s товаров", "usam" ), $i), ['update' => $product_attributes, 'args' => $query_vars], 'update_products_attribute', $i, 'update_products_attribute'.time() );		
			return usam_get_callback_messages(['add_event' => $result]);
		}	
		else
		{
			foreach( $_POST['post__in'] as $product_id )
			{					
				$product_id = absint($product_id);
				$product = new USAM_Product( $product_id );	
				$product->calculate_product_attributes( $product_attributes, true );
			}
			return usam_get_callback_messages(['updated' => count($_POST['post__in'])]);
		}	
		return usam_get_callback_messages(['ready' => $result]);		
	}	
	
	public function controller_bulk_actions_terms()
	{		
		if ( !empty($_POST['terms']) && isset($_POST['operation']) )
		{
			$operation = sanitize_title($_POST['operation']);	
			$terms  = $_POST['terms'];
			
			$title = __("Обновление свойств у %s товаров", "usam");	
			$process = 'update_products_terms'; 	
			if ( empty($_POST['post__in']) )
			{			
				require_once( USAM_FILE_PATH.'/admin/includes/admin_product_query.class.php' );
				$query_vars = USAM_Admin_Product_Query::get_filter();				
				$i = usam_get_total_products( $query_vars );
				$result = usam_create_system_process( sprintf($title , $i), ['terms' => $terms, 'operation' => $operation, 'args' => $query_vars], $process, $i, $process.time() );	
				$return = ['add_event' => $result];
			}
			else
			{
				$i = count($_POST['post__in']);
				$ids = array_map('intval',$_POST['post__in']);
				$query_vars = ['post__in' => $ids];
				usam_start_system_process( sprintf($title, $i), ['terms' => $terms, 'operation' => $operation, 'args' => $query_vars], $process, $i );
				$return = ['updated' => $i];
			}
			return usam_get_callback_messages( $return );
		}
	}	
	
	function controller_update_payment_gateway_sort_fields() 
	{		
		if ( !empty($_REQUEST['sort_order']) )
		{
			$fields = stripslashes_deep($_REQUEST['sort_order']);	
			foreach($fields as $key => $id) 
			{ 	
				$id = absint( $id );			
				usam_update_payment_gateway( $id, ['sort' => absint($key)]);								
			}			
		}			
	}
	
	function controller_update_delivery_service_sort_fields() 
	{		
		if ( !empty($_REQUEST['sort_order']) )
		{
			$fields = stripslashes_deep($_REQUEST['sort_order']);	
			foreach($fields as $key => $id) 
			{ 	
				$id = absint( $id );			
				usam_update_delivery_service( $id, ['sort' => absint($key)]);								
			}			
		}			
	}
	
	function controller_update_status_sort_fields() 
	{		
		if ( !empty($_REQUEST['sort_order']) )
		{	
			$fields = stripslashes_deep($_REQUEST['sort_order']);	
			foreach($fields as $key => $id) 
			{ 	
				$id = absint( $id );			
				usam_update_object_status($id, array('sort' => absint($key) ));								
			}			
		}			
	}
	
	public function controller_update_mailboxes_sort_fields()
	{		
		if ( empty($_POST['sort_order']) || !is_array($_POST['sort_order']) )
			return false;
		
		$taxonomy_order = (array)$_POST['sort_order'];		
		$i = 0;	
		foreach( $taxonomy_order as $id )
		{			
			$id = preg_replace("/[^0-9]/", '', $id);			
			usam_update_mailbox( $id, array( 'sort' => $i ) );
			$i++;
		}
	}		
	
	public function controller_update_property_sort_fields()
	{		
		if ( empty($_POST['sort_order']) || !is_array($_POST['sort_order']) )
			return false;
		
		$order = (array)$_POST['sort_order'];			
		$i = 0;	
		foreach( $order as $id )
		{			
			$id = preg_replace("/[^0-9]/", '', $id);			
			usam_update_property( $id, ['sort' => $i]);
			$i++;
		}
	}	
		
	public function controller_update_property_groups_sort_fields()
	{		
		if ( empty($_POST['sort_order']) || !is_array($_POST['sort_order']) )
			return false;
		
		$order = (array)$_POST['sort_order'];			
		$i = 0;	
		foreach( $order as $id )
		{			
			$id = preg_replace("/[^0-9]/", '', $id);			
			usam_update_property_group( $id, ['sort' => $i]);
			$i++;
		}
	}		
			
	//Сортировка терминов
	public function controller_set_taxonomy_order()
	{		
		if ( empty($_POST['sort_order']) || !is_array($_POST['sort_order']) )
			return false;
		
		$taxonomy_order = (array)$_POST['sort_order'];		
		$result = true;			
		$i = 0;			
		foreach( $taxonomy_order as $id )
		{			
			$term_id = preg_replace("/[^0-9]/", '', $id);			
			usam_update_term_metadata( $term_id, 'sort', $i );		
			$i++;
		}		
		return $result;
	}
	
	public function controller_set_taxonomy_thumbnail()
	{		
		if ( empty($_POST['term_id']) || empty($_POST['attachment_id']) )
			return false;
		
		$term_id      = absint($_REQUEST['term_id']);
		$attachment_id = absint($_REQUEST['attachment_id']);
		update_term_meta( $term_id, 'thumbnail', $attachment_id );
		
		$images = get_term_meta($term_id, 'images', true);
		if( !$images )
			$images = [];
		$images[0] = $attachment_id;
		update_term_meta( $term_id, 'images', $images );
		
		return true;
	}
		
	public function controller_review_edit() 
	{				
		global $wpdb;		
		$id    = absint($_REQUEST['id']);
		$colum = sanitize_text_field($_REQUEST['col']);
		$value = $_REQUEST['value'];		
					
		usam_update_review( $id, [$colum => $value]);		
		return $value;
	}
	
	function controller_set_variation_thumbnail() 
	{				
		if ( !empty($_POST['attachment_id']) && !empty($_POST['post_id']) )
		{	
			$attachment_id = absint($_POST['attachment_id']);	
			$post_id = absint($_POST['post_id']);		
			
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			if ( $thumbnail_id )
				wp_update_post( array( 'ID' => $thumbnail_id, 'post_parent' => 0 ) );	
							
			wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => $post_id ) );	
			return set_post_thumbnail( $post_id, $attachment_id );
		}
		return false;
	}
	
	function controller_empty_trash_post() 
	{
		if ( !empty($_REQUEST['post_type']) )		
		{
			$post_type = sanitize_text_field($_REQUEST['post_type']);		
			$args['fields'] = 'ids';	
			$args['update_post_meta_cache'] = false;	
			$args['update_post_term_cache'] = false;
			$args['cache_results'] = false;
			$args['product_meta_cache'] = false;			
			$args['posts_per_page'] = -1;
			$args['post_status'] = 'trash';
			$args['post_type'] = $post_type;
			$posts = get_posts( $args );
			$result = usam_create_system_process( __('Очистка корзины','usam'), ['post_status' => 'trash', 'post_type' => $post_type], 'delete_post', count($posts), 'delete_post' );
			return usam_get_callback_messages(['add_event' => $result]);
		}
		return usam_get_callback_messages(['ready' => 0]);
	}
	
	function controller_set_products_thumbnail() 
	{
		$product_ids = array_map('intval', $_REQUEST['product_ids'] );
		$attachment_id = absint( $_REQUEST['attachment_id'] );	
		$file = get_attached_file( $attachment_id );
		$path_parts = pathinfo( $file );
		$directory = $path_parts['dirname']; 
		$filename = $path_parts['basename']; 	
		foreach( $product_ids as $id )
		{			
			$sanitized_media_title = wp_unique_filename( $directory, $filename );
			$newfile = $directory.'/'.$sanitized_media_title;		
			if ( copy($file, $newfile) )
			{
				$file_array['name'] = $sanitized_media_title;
				$file_array['tmp_name'] = $newfile;	
				$thumbnail_id = media_handle_sideload( $file_array, $id );
				set_post_thumbnail( $id, $thumbnail_id );				
			}
		}
		return usam_get_callback_messages(['updated' => count($product_ids)]);
	}		
	
	function controller_add_participant() 
	{			
		if ( !empty($_POST['event_id']) && !empty($_POST['user_id'])  )
		{	
			$event_id = absint($_POST['event_id']);	
			$user_id = absint($_POST['user_id']);					
			
			$event = usam_get_event( $event_id );	
			if ( empty($event) )	
				return false;
			
			if ( $event['user_id'] == $user_id )
				return false;
			
			$result = usam_set_event_user( $event_id, $user_id );
			if ( $result )
			{
				$user = get_user_by('id', $user_id );
				$foto = usam_get_contact_foto( $user_id, 'user_id' );
				return array( 'name' => $user->display_name, 'foto' => $foto );
			}
		}	
		return false;
	}
	
	function controller_change_subscriber_list() 
	{		
		$i = 0;
		if ( !empty($_POST['tab']) &&  !empty($_POST['lists']) && !empty($_POST['operation']) )
		{
			$lists = array_map('intval', $_POST['lists']);	
			$tab = sanitize_title( $_POST['tab'] );
			$operation = sanitize_title($_POST['operation']);			
			if ( isset($_POST['ids']) )
				$ids = array_map('intval', $_POST['ids']);
			else
			{
				$args = ['fields' => 'id', 'meta_cache' => true];	
				if ( $tab == 'companies' )
					$ids = usam_get_companies( $args );		
				else
					$ids = usam_get_contacts( $args );	
			}	
			if ( !empty($_POST['types']) )
			{	
				$types = $_POST['types'];							
				foreach( $ids as $id )
				{	
					foreach ( $lists as $list_id  )
					{	
						foreach ( $types as $type )
						{						
							$communications = array();
							switch( $type )
							{
								case 'email':
									if ( $tab == 'companies' )
										$communications = usam_get_company_emails($id);
									else
										$communications = usam_get_contact_emails($id);
								break;
								case 'phone':
									if ( $tab == 'companies' )
										$communications = usam_get_company_phones($id);
									else
										$communications = usam_get_contact_phones($id);
								break;
							}						
							if ( !empty($communications) )
							{			
								foreach ( $communications as $communication )
								{								
									$args = ['communication' => $communication, 'id' => $list_id, 'type' => $type];
									switch( $operation )
									{
										case 'move':						
											usam_delete_subscriber_lists(['communication' => $communication]);	
											$result = usam_set_subscriber_lists( $args );										
										break;	
										case 'copy':							
											$result = usam_set_subscriber_lists( $args );
										break;
										case 'remove':									
											$result = usam_delete_subscriber_lists( $args );
										break;
										default:
											$result = false;
										break;
									}
									if ( $result )
										$i++;
								}
							}							
						}									
					}	
				}
			}
			elseif ( $operation == 'remove' )
			{				
				$args['id'] = $lists;	
				if ( !empty($_POST['types']) )
				{
					$args['type'] = array_map('sanitize_title', $_POST['types']);
					foreach( $ids as $id )
					{							
						foreach ( $args['type'] as $type )
						{
							switch( $type )
							{
								case 'email':
									if ( $tab == 'companies' )
										$args['communication'] = usam_get_company_emails($id);
									else
										$args['communication'] = usam_get_contact_emails($id);
								break;
								case 'phone':
									if ( $tab == 'companies' )
										$args['communication'] = usam_get_company_phones($id);
									else
										$args['communication'] = usam_get_contact_phones($id);
								break;
							}
						}
						if ( usam_delete_subscriber_lists( $args ) )
							$i++;							
					}
				}
				elseif ( $args )
				{
					if ( usam_delete_subscriber_lists( $args ) )
							$i++;
				}	
			}
			usam_update_mailing_statuses();
		}		
		return usam_get_callback_messages(['updated' => $i]);
	}	
		
	function controller_bulk_actions_bonus_cards() 
	{		
		$properties = $_POST['properties'];		
		$update = array();				
		foreach( $properties as $property )
		{			
			$key = sanitize_title($property['key']);	
			if ( $key )
				$update[$key] = is_array($property['value'])?stripslashes_deep($property['value']):sanitize_text_field($property['value']);
		}	
		if ( empty($_POST['ids']) )
		{
			require_once( USAM_FILE_PATH . '/includes/customer/bonus_cards_query.class.php' );
			$bonus_cards = usam_get_bonus_cards(['fields' => 'code']);				
			$i = count($bonus_cards);
			$result = usam_create_system_process( __("Добавление бонусов", "usam" ), $update, 'add_bonuses', $i, 'add_bonuses' );		
			return usam_get_callback_messages(['add_event' => $result]);	
		}	
		else
		{			
			$codes = array_map('sanitize_title', $_POST['ids']);	
			foreach ( $codes as $code )
			{
				$update['code'] = $code;
				usam_insert_bonus( $update );
			}
			return usam_get_callback_messages(['ready' => 1]);
		}			
	}	

	function controller_bulk_actions_coupons() 
	{
		$properties = $_POST['properties'];		
		$update = array();				
		foreach( $properties as $property )
		{			
			$key = sanitize_title($property['key']);			
			if ( $key )
				$update[$key] = is_array($property['value'])?stripslashes_deep($property['value']):sanitize_text_field($property['value']);
		}	
		if ( !empty($update['date_start']) )
		{
			$update['start'] = usam_get_datepicker('start', $update);
			unset($update['date_start']);
		}
		if ( !empty($update['date_end']) )
		{
			$update['end_date'] = usam_get_datepicker('end', $update);
			unset($update['date_end']);
		}
		if ( isset($update['active']) )
		{
			if( $update['active'] === '' )
				unset($update['active']);
		}		
		if ( empty($_POST['ids']) )
		{
			require_once( USAM_FILE_PATH . '/includes/basket/coupons_query.class.php' );
			$coupons = usam_get_coupons(['fields' => 'id']);				
			$i = count($coupons);
			$result = usam_create_system_process( __("Обновление купонов", "usam" ), $update, 'update_coupons_properties', $i, 'update_coupons_properties' );		
			return usam_get_callback_messages(['add_event' => $result]);	
		}	
		else
		{			
			$ids = array_map('intval', $_POST['ids']);	
			foreach ( $ids as $id )
				usam_update_coupon( $id, $update );	
			return usam_get_callback_messages(['updated' => count($ids)]);	
		}			
	}	
	
	function controller_bulk_actions_storage() 
	{
		$properties = $_POST['properties'];		
		$update = array();				
		foreach( $properties as $property )
		{			
			$key = sanitize_title($property['key']);
			if ( $key )
				$update[$key] = is_array($property['value'])?stripslashes_deep($property['value']):sanitize_text_field($property['value']);
		}	
		if ( $update['owner'] &&  $update['owner'] == '0' )
			$update['owner'] = '';
		if ( empty($_POST['ids']) )
		{
			$coupons = usam_get_storages(['fields' => 'id']);				
			$i = count($coupons);
			$result = usam_create_system_process( __("Обновление складов", "usam" ), $update, 'update_storages_properties', $i, 'update_storages_properties' );		
			return usam_get_callback_messages(['add_event' => $result]);		
		}	
		else
		{			
			$ids = array_map('intval', $_POST['ids']);	
			foreach ( $ids as $id )						
				usam_update_storage( $id, $update );
			return usam_get_callback_messages(['updated' => count($ids)]);
		}				
		
	}	
	
	function controller_bulk_actions_contacts() 
	{
		$properties = $_POST['properties'];		
		$update = array();				
		foreach( $properties as $property )
		{			
			$key = sanitize_title($property['key']);	
			if ( $key )
				$update[$key] = is_array($property['value'])?stripslashes_deep($property['value']):sanitize_text_field($property['value']);
		}			
		$title = __("Обновление свойств у %s контактов", "usam");
		$process = 'update_orders_properties'; 
		if ( empty($_POST['ids']) )
		{			
			$ids = usam_get_contacts(['fields' => 'id']);				
			$i = count($ids);
			$result = usam_create_system_process( sprintf($title , $i), ['update' => $update, 'args' => []], $process, $i, $process.time() );	
			$return = ['add_event' => $result];
		}
		else
		{
			$i = count($_POST['ids']);
			$ids = array_map('intval',$_POST['ids']);
			usam_start_system_process( sprintf($title, $i), ['update' => $update, 'args' => ['include' => $ids]], $process, $i );
			$return = ['updated' => $i];
		}
		return usam_get_callback_messages( $return );
	}		
	
	function controller_bulk_actions_orders() 
	{
		if ( !current_user_can('edit_order') )
			return usam_get_callback_messages(['access' => 1]);
		
		$properties = $_POST['properties'];		
		$update = array();				
		foreach( $properties as $property )
		{			
			$key = sanitize_title($property['key']);	
			if ( $key )
				$update[$key] = is_array($property['value'])?stripslashes_deep($property['value']):sanitize_text_field($property['value']);
		}		
		$title = __("Обновление свойств у %s заказов", "usam");
		$process = 'update_orders_properties'; 
		if ( empty($_POST['ids']) )
		{			
			$ids = usam_get_orders(['fields' => 'id']);				
			$i = count($ids);
			$result = usam_create_system_process( sprintf($title , $i), ['update' => $update, 'contact_id' => usam_get_contact_id(), 'args' => []], $process, $i, $process.time() );	
			$return = ['add_event' => $result];
		}
		else
		{
			$i = count($_POST['ids']);
			$ids = array_map('intval',$_POST['ids']);
			usam_start_system_process( sprintf($title, $i), ['update' => $update, 'contact_id' => usam_get_contact_id(), 'args' => ['include' => $ids]], $process, $i );
			$return = ['updated' => $i];
		}
		return usam_get_callback_messages( $return );
	}	
	
	function controller_bulk_actions_employees() 
	{
		return $this->controller_bulk_actions_contacts();		
	}	
	
	function controller_bulk_actions_companies() 
	{
		$properties = $_POST['properties'];		
		$update = array();				
		foreach( $properties as $property )
		{			
			$key = sanitize_title($property['key']);	
			if ( $key )
				$update[$key] = is_array($property['value'])?stripslashes_deep($property['value']):sanitize_text_field($property['value']);
		}			
		if ( empty($_POST['ids']) )
		{
			$companies = usam_get_companies( array('fields' => 'id') );				
			$i = count($companies);		
			$result = usam_create_system_process( sprintf(__("Обновление свойств у %s компаний", "usam" ), $i), $update, 'update_companies_properties', $i, 'update_companies_properties' );		
			return usam_get_callback_messages(['add_event' => $result]);		
		}	
		else
		{		
			$ids = array_map('intval', $_POST['ids']);	
			foreach ( $ids as $id )
			{
				usam_update_company( $id, $update );				
			}
			return usam_get_callback_messages(['updated' => count($ids)]);
		}
	}	
	
	function controller_change_task_participants() 
	{			
		if ( !empty($_POST['ids']) )
		{
			$i = count($_POST['ids']);
			foreach( $_POST['ids'] as $id )
			{
				$event_id = absint($id);	
				$user_id = absint($_POST['user_id']);			
				if ( $_POST['operation'] == 'add' )
					usam_set_event_user( $event_id, $user_id );
				elseif ( $_POST['operation'] == 'delete' )
					usam_delete_event_user( array( 'event_id' => $event_id, 'user_id' => $user_id, 'user_type' => 'participant' ) );
				elseif ( $_POST['operation'] == 'move' )
				{
					$users = usam_get_event_users( $event_id );
					$ok = true;
					if ( !empty($users['participant']) )
					{
						foreach ( $users['participant'] as $userid )
						{				
							if ( $userid == $user_id )
							{
								$ok = false;
								continue;
							}
							usam_delete_event_user( array( 'event_id' => $event_id, 'user_id' => $userid, 'user_type' => 'participant' ) );
						}		
					}
					if ( $ok )
						usam_set_event_user( $event_id, $user_id );
				}
			}	
		}
		return usam_get_callback_messages(['updated' => $i]);
	}		
		
	function controller_delete_event_participant() 
	{			
		if ( isset($_POST['event_id']) && isset($_POST['user_id'])  )
		{
			$event_id = absint($_POST['event_id']);	
			$user_id = absint($_POST['user_id']);			
			return usam_delete_event_user( array( 'event_id' => $event_id, 'user_id' => $user_id, 'user_type' => 'participant' ) );
		}
		else
			return  false;
	}		
			
	function controller_change_document_manager() 
	{
		if ( isset($_POST['id']) )
		{
			$id = absint($_POST['id']);
			$manager_id = absint($_POST['manager']);
			return usam_update_document($id, array( 'manager_id' => $manager_id ) );
		}
		return  false;
	}
				
	/**
	 * Добавить новый вариант вариации. Если имя вариации такое же, как существующий набор вариаций, дети для варианта термина будут добавлены внутри этого существующего набора.
	 */
	function controller_add_variation() 
	{
		$new_variation_set = stripslashes_deep($_POST['variation_set']);
		$variants = preg_split( '/\s*,\s*/', $_POST['variants'] );

		$return = array();

		$parent_term_exists = term_exists( $new_variation_set, 'usam-variation' );
		// только использовать существующий родительский ID, если термин не термин ребенок
		if ( $parent_term_exists ) 
		{
			$parent_term = get_term( $parent_term_exists['term_id'], 'usam-variation' ); 
			if ( $parent_term->parent == '0' )
				$variation_set_id = $parent_term_exists['term_id'];
		}
		if ( empty( $variation_set_id ) )
		{
			$results = wp_insert_term( $new_variation_set, 'usam-variation' );
			if ( is_wp_error( $results ) )
				return $results;
			$variation_set_id = $results['term_id'];
		}
		if ( empty( $variation_set_id ) )
			return new WP_Error( 'usam_invalid_variation_id', __('Не удается получить вариацию набора, чтобы продолжить.', 'usam') );

		foreach ( $variants as $variant ) 
		{
			$results = wp_insert_term( $variant, 'usam-variation', array( 'parent' => $variation_set_id ) );
			if ( is_wp_error( $results ) )
				return $results;
			$inserted_variants[] = $results['term_id'];
		}
		require_once( USAM_FILE_PATH . '/admin/includes/product/walker-variation-checklist.php' );

		/* Следующие 3 строки будут удалены детьми термина кэша для вариации. Без этого, новые вариации дети не будет отображаться на странице "Вариации", 
		и также не будет отображаться в wp_terms_checklist().
		*/
		clean_term_cache( $variation_set_id, 'usam-variation' );
		delete_option('usam-variation_children');
		wp_cache_set( 'last_changed', 1, 'terms' );
		_get_term_hierarchy('usam-variation');

		ob_start();
		wp_terms_checklist( (int) $_POST['post_id'], ['taxonomy' => 'usam-variation', 'descendants_and_self' => $variation_set_id, 'walker' => new USAM_Walker_Variation_Checklist( $inserted_variants ), 'checked_ontop' => false]);
		$content = ob_get_clean();

		$return = ['variation_set_id'  => $variation_set_id, 'inserted_variants' => $inserted_variants, 'content'  => $content];
		return $return;
	}
	
	function controller_show_hidden_menu_tab()
	{					
		if ( isset($_POST['page']) && isset($_POST['tab']) )
		{
			$page = sanitize_title($_POST['page']);	
			$tab = sanitize_title($_POST['tab']);	
			
			$hidden_tabs = get_user_option( 'usam_hidden_menu_tabs' );	
			if ( !isset($hidden_tabs[$page]) ) 
				return true;
			elseif ( in_array($tab, $hidden_tabs[$page]) )
			{
				$k = array_search($tab, $hidden_tabs[$page]);	
				if ( $k !== false )
					unset($hidden_tabs[$page][$k]);	
			}
			$user_id = get_current_user_id();
			return update_user_option( $user_id, 'usam_hidden_menu_tabs', $hidden_tabs );
		}
		return false;
	}	
	
	function controller_load_list_data()
	{
		$results = array();
		if ( isset($_POST['type']) )
		{			
			$type = sanitize_title($_POST['type']);		
			$number = (int)$_POST['number'];
			require_once( USAM_FILE_PATH . '/admin/includes/load_list_data.class.php' );	
			$list = new USAM_Load_List_Data();
			$results = $list->load($type, $number);
		}
		return $results;
	}
	
	function controller_total_results_report()
	{
		$results = array();
		if ( isset($_POST['type']) )
		{			
			$type = sanitize_title($_POST['type']);		
			require_once( USAM_FILE_PATH . '/admin/includes/general_results_report.class.php' );	
			$list = new USAM_General_Results_Report();
			$results = $list->load( $type );
		}
		return $results;
	}
	
	function controller_load_graph_data()
	{
		$results = array();
		if ( isset($_POST['type']) )
		{			
			$type = sanitize_title($_POST['type']);		
			require_once( USAM_FILE_PATH . '/admin/includes/load_graph_data.class.php' );	
			$list = new USAM_Load_Graph_Data();
			$results = $list->load( $type );
		}
		return $results;
	}

	function controller_show_all_menu_tab()
	{					
		if ( isset($_POST['page']) )
		{
			$page = sanitize_title($_POST['page']);				
			
			$hidden_tabs = get_user_option( 'usam_hidden_menu_tabs' );	
			if ( !isset($hidden_tabs[$page]) ) 
				return true;		
			unset($hidden_tabs[$page]);	
			$user_id = get_current_user_id();
			return update_user_option( $user_id, 'usam_hidden_menu_tabs', $hidden_tabs );
		}
		return false;
	}		
	
	function controller_sort_menu_tabs()
	{						
		if ( isset($_POST['page']) && isset($_POST['tabs']) )
		{
			$user_id = get_current_user_id();
			$page = sanitize_title($_POST['page']);	
			$tabs = $_POST['tabs'];				
			$new_sort_tabs = array();
			foreach( $tabs as $key => $tab) 
			{
				$tab = sanitize_title($tab);	
				$new_sort_tabs[] = $tab;
			}
			$menu_tabs = get_user_option( 'usam_sort_menu_tabs' );				
			$menu_tabs[$page] = $new_sort_tabs;	
			
			$hidden_tabs = get_user_option( 'usam_hidden_menu_tabs' );	
			if ( !empty($hidden_tabs[$page]) ) 
			{
				foreach( $hidden_tabs[$page] as $key => $tab) 
				{
					if ( in_array($tab,$menu_tabs[$page]) )
						unset($hidden_tabs[$page][$key]);
				}
				update_user_option( $user_id, 'usam_hidden_menu_tabs', $hidden_tabs );			
			}
			return update_user_option( $user_id, 'usam_sort_menu_tabs', $menu_tabs );
		}
		return false;
	}			
	
	function controller_add_favorites_page()
	{					
		if ( isset($_POST['title']) && isset($_POST['url']) && isset($_POST['screen_id']) )
		{
			$title = sanitize_text_field(stripslashes($_POST['title']));
			$url = sanitize_text_field($_POST['url']);	
			$screen_id = sanitize_text_field($_POST['screen_id']);	
			
			$favorite_pages = get_user_option( 'usam_favorite_pages' );	
			if ( empty($favorite_pages[$screen_id]) )
				$favorite_pages[$screen_id] = ['title' => $title, 'url' => $url];
			else
				unset($favorite_pages[$screen_id]);
			$user_id = get_current_user_id();
			return update_user_option( $user_id, 'usam_favorite_pages', $favorite_pages );
		}
		return false;
	}		
	
	function controller_hidden_menu_tab()
	{					
		if ( isset($_POST['page']) && isset($_POST['tab']) )
		{
			$page = sanitize_title($_POST['page']);	
			$tab = sanitize_title($_POST['tab']);	
			
			$hidden_tabs = get_user_option( 'usam_hidden_menu_tabs' );	
			if ( !isset($hidden_tabs[$page]) ) 
				$hidden_tabs[$page] = array();
			elseif ( in_array($tab, $hidden_tabs[$page]) ) 
				return true;
				
			$hidden_tabs[$page][] = $tab;		
			$user_id = get_current_user_id();
			return update_user_option( $user_id, 'usam_hidden_menu_tabs', $hidden_tabs );
		}
		return false;
	}		

	//Загрузка вкладки страницы
	function controller_navigate_tab()
	{						
		ob_start();	
		$page = new USAM_Page_Tabs( $_POST['page'], $_POST['tab'] );
		$page->display_current_tab();			
		$return['content'] = ob_get_clean();	
		
		return $return;
	}	
			
	/**
	 * Удалить мета продукта через AJAX
	 */
	function controller_remove_product_meta() 
	{
		$meta_id = (int) $_POST['meta_id'];
		if ( ! delete_meta( $meta_id ) )
			return new WP_Error( 'usam_cannot_delete_product_meta', __( "Не удалось удалить данные товара. Пожалуйста, попробуйте еще раз.", 'usam') );

		return array( 'meta_id' => $meta_id );
	}
		
	/**
	 * Сохранить порядок продукта после drag-and-drop сортировки
	 */
	function controller_save_product_order() 
	{
		$products = array( );
		foreach ( $_POST['post'] as $product ) {
			$products[] = (int) str_replace( 'post-', '', $product );
		}
		$failed = array();
		foreach ( $products as $order => $product_id ) 
		{
			$result = wp_update_post(['ID' => $product_id, 'menu_order' => $order]);
			if ( ! $result )
				$failed[] = $product_id;
		}
		if ( !empty( $failed ) ) 
		{
			$error_data = array( 'failed_ids' => $failed, );
			return new WP_Error( 'usam_cannot_save_product_sort_order', __( "Не удалось сохранить порядок сортировки продукции. Пожалуйста, попробуйте еще раз.", 'usam'), $error_data );
		}
		return true;
	}

	/**
	 * Создать вариации
	 */
	function controller_update_variations() 
	{
		$product_id = absint( $_REQUEST["product_id"] );	
		
		$post_data = [];
		$post_data['variations'] = isset($_POST['edit_var_val'] ) ? $_POST["edit_var_val"] : '';	
		usam_edit_product_variations( $product_id, $post_data );

		ob_start();
		usam_admin_product_listing( $product_id );
		$content = ob_get_clean();
		return array( 'content' => $content );
	}
	
	function controller_change_importance_email()
	{			
		$id = absint( $_REQUEST['id'] );	
		$importance = absint( $_REQUEST['importance'] );
		
		usam_update_email( $id, array('importance' => $importance) );		
	}
	
	function controller_change_email_folder()
	{			
		if ( !empty($_REQUEST['id']) && !empty($_REQUEST['folder'] ) )
		{		
			$folder = sanitize_title( $_REQUEST['folder'] );
			if ( is_array($_REQUEST['id']) )
			{			
				foreach( $_REQUEST['id'] as $data )
				{
					$id = absint( $data['value'] );
					usam_update_email( $id, array('folder' => $folder) );	
				}
			}
			else
			{
				$id = absint( $_REQUEST['id'] );					
				usam_update_email( $id, array('folder' => $folder) );	
			}
		}
		return $id;
	}
		
	function controller_spam_email()
	{
		if ( !empty($_REQUEST['id']) )
		{			
			$id = absint( $_REQUEST['id'] );	
			usam_spam_email( $id );
		}
	}		
	
	function controller_read_email_folder()
	{			
		if ( !empty($_REQUEST['folder_id']))
		{					
			global $wpdb;	
			$folder_id = absint( $_REQUEST['folder_id'] );
			
			$folder = usam_get_email_folder( $folder_id );			
			$result = $wpdb->update( USAM_TABLE_EMAIL, array('read' => 1), array('mailbox_id' => $folder['mailbox_id'], 'folder' => $folder['slug']), array('%d'), array('%d','%s') );
			usam_update_email_folder( $folder_id, array( 'not_read' => 0 ) );
		}		
	}

	function controller_clear_email_folder()
	{		
		if ( !empty($_REQUEST['folder_id']))
		{					
			$folder_id = absint( $_REQUEST['folder_id'] );			
			$folder = usam_get_email_folder( $folder_id );					
			usam_delete_emails(['mailbox_id' => $folder['mailbox_id'], 'folder' => $folder['slug']]);
		}
		return usam_get_callback_messages(['ready' => 1]);
	}
	
	function controller_delete_duplicate()
	{		
		$k = 0;
		if ( !empty($_REQUEST['mailbox_id']) && !empty($_REQUEST['folder_id']))
		{					
			global $wpdb;
			$mailbox_id = absint( $_REQUEST['mailbox_id'] );	
			$folder_id = absint( $_REQUEST['folder_id'] );
			$folder = usam_get_email_folder( $folder_id );
			if ( empty($folder['slug']) )
				return $k;
			
			$sql = "SELECT id, from_email, title, body FROM ".USAM_TABLE_EMAIL." WHERE CONCAT( from_email, title ) IN ( SELECT CONCAT( from_email, title ) AS x FROM `".USAM_TABLE_EMAIL."` WHERE mailbox_id='$mailbox_id' AND folder='".$folder['slug']."' GROUP BY x HAVING COUNT( x )>1 ) LIMIT 1000";	
			$results = $wpdb->get_results( $sql );	
			$count = count($results);			
			for ($i=0;$i<$count;$i++)
			{
				if ( empty($results[$i]) )
					continue;
				
				for ($j=$i+1;$j<$count;$j++)
				{
					if ( !empty($results[$j]) && strcasecmp($results[$i]->title, $results[$j]->title) == 0 && strcasecmp($results[$i]->body, $results[$j]->body) == 0 && strcasecmp($results[$i]->from_email, $results[$j]->from_email) == 0 )	
					{
						$k++;
						usam_delete_email( $results[$j]->id );
					}
				}		
			}	
		}	
		return usam_get_callback_messages(['deleted' => $k]);
	}	

	function controller_remove_email_folder()
	{		
		if ( !empty($_REQUEST['folder_id']))
		{	
			$folder_id = absint( $_REQUEST['folder_id'] );			
			usam_delete_email_folder( $folder_id );
		}
		return usam_get_callback_messages(['ready' => 1]);
	}	
	
	function controller_get_signature()
	{		
		if ( !empty($_REQUEST['id']))
		{	
			$id = absint( $_REQUEST['id'] );			
			$signature = usam_get_signature( $id );
			return $signature['signature'];
		}
		return '';
	}
		
	function controller_add_email_object()
	{		
		ob_start();
		if ( !empty($_REQUEST['id']))
		{	
			$id = absint( $_REQUEST['id'] );		
			$object_id = absint( $_REQUEST['object_id'] );		
			$object_type = sanitize_title( $_REQUEST['object_type'] );
			usam_set_email_object( $id, ['object_id' => $object_id, 'object_type' => $object_type]);
			$objects = usam_get_email_objects( $id );
			foreach ( $objects as $object ) 
			{
				if ( $object->object_type != 'email' )
				{ 
					$result = usam_get_object( $object );
					if ( !empty($result) ) 
					{
						?>
						<div class = "letter_header__row">		
							<div class = "letter_header__label"><?php _e('Прикреплено', 'usam') ?>:</div>
							<div class = "letter_header__text"><?php echo $result['name']." - <a href='".$result['url']."'>".$result['title']."</a>"; ?></div>			
						</div>
						<?php
					}
				}
			}
		}
		return ob_get_clean();
	}		
	
	function controller_add_email_folder()
	{		
		$id = array( 'slug' => '', 'id' => 0 );
		if ( !empty($_REQUEST['mailbox_id']) && !empty($_REQUEST['name']) )
		{		
			$name = sanitize_text_field( $_REQUEST['name'] );	
			$mailbox_id = absint( $_REQUEST['mailbox_id'] );	
			
			$id = usam_insert_email_folder( array('name' => $name, 'mailbox_id' => $mailbox_id) );	
			$slug = sanitize_title($name);			
		}
		return array( 'slug' => $slug, 'id' => $id );
	}
	
	function controller_delete_email_fileupload()
	{
		ob_start();
	
		if ( !empty($_REQUEST['id']) )
		{
			$id = absint($_REQUEST['id']);	
			usam_delete_file( $id, true );
		}
		else
		{
			$file_name = stripcslashes( $_REQUEST['file_id'] );		
			if ( file_exists($file_name) )
				unlink($file_name);		
		}
		return 	ob_get_clean();
	}	
		
	function controller_display_email_message()
	{		
		$email_id = absint( $_REQUEST['email_id'] );
		
		$email = usam_get_email( $email_id );		
		$mailboxes = usam_get_mailboxes( array( 'fields' => 'id', 'user_id' => get_current_user_id() ) );
		if ( in_array($email['mailbox_id'], $mailboxes) )
		{
			$result['content'] =  preg_replace_callback("/\n>+/u", 'usam_email_replace_body', $email['body'] );			
				
			ob_start();
						
			usam_email_html_header( $email_id );
			$result['header'] = ob_get_clean();
				
			$result['id'] = $email_id;
		}
		return $result;  
	}	
	
	function controller_display_email_form()
	{			
		ob_start();				
		
		require_once( USAM_FILE_PATH .'/admin/form/view-form-email.php' );	
		$item_table = new USAM_Form_email();
		$item_table->display();				
		
		$return = ob_get_contents();
		ob_end_clean();				
		return $return;
	}
	
	function controller_read_message_email()
	{
		$id = absint( $_REQUEST['email_id'] );	
		usam_update_email( $id, array('read' => 0) );						
		$html = '<a class="usam-read-link" href=""><span class="dashicons dashicons-email-alt"></span>'.__('Прочитано', 'usam').'</a>';	
		return	$html;							
	}
	
	function controller_add_contact_from_email()
	{
		$email_id = absint( $_REQUEST['id'] );	
		usam_add_contact_from_email( $email_id );
		
		ob_start();
					
		usam_email_html_header( $email_id );
		$html = ob_get_clean();
		
		return $html;
	}
	
	function controller_not_read_message_email()
	{
		$id = absint( $_REQUEST['email_id'] );	
		usam_update_email( $id, array('read' => 1) );
		$html = '<a class="usam-not_read-link" href=""><span class="dashicons dashicons-email-alt"></span>'.__('Не прочитано', 'usam').'</a>';	
		return	$html;			
	}
	
	function controller_delete_message_email()
	{
		$id = absint( $_REQUEST['email_id'] );	
		$result = $this->controller_next_email_message();	
		if ( $result['id'] == 0 )
			$result = $this->controller_previous_email_message();	
		
		usam_delete_email( $id );					
		return $result;
	}	
	
	function controller_previous_email_message()
	{		
		$result = array( 'id' => 0 );		
		$id = absint( $_REQUEST['email_id'] );	
		if ( $id == 0 )							
			return $result;
		
		$object_id = absint( $_REQUEST['object_id'] );	
		$object_type = sanitize_title( $_REQUEST['object_type'] );	
		$args = ['conditions' => ['key' => 'id', 'compare' => '>', 'value' => $id], 'number' => 2, 'order' => 'ASC', 'mailbox' => 'user', 'object_query' => []];
		switch ( $object_type ) 
		{		
			case 'orders' :	
				$args['object_query'][] = ['object_type' => 'order', 'object_id' => $object_id];
				$args['folder_not_in'] = 'deleted';
			break;
			case 'contacts' :			
				$args['emails'] = usam_get_contact_emails( $object_id );
				$args['folder_not_in'] = 'deleted';
			break;
			case 'companies' :					
				$args['emails'] = usam_get_company_emails( $object_id, true );
				$args['folder_not_in'] = 'deleted';
			break;
			case 'email' :
				$email = usam_get_email( $id );		
				$args['mailbox_id'] = $email['mailbox_id'];
				$args['folder'] = $email['folder'];
			break;
		}	
		$email = usam_get_emails( $args );			
		if ( isset($email[0]->body) )										
		{
			$result['content'] =  preg_replace_callback("/\n>+/u", 'usam_email_replace_body', $email[0]->body );			
						
			ob_start();			
			usam_email_html_header( $email[0]->id );
			$result['header'] = ob_get_clean();
			$result['id'] = $email[0]->id;
			$result['next'] = isset($email[1])?$email[1]->id:0;
		}
		return $result;  
	}	
	
	function controller_next_email_message()
	{		
		$result = array( 'id' => 0 );		
		$id = absint( $_REQUEST['email_id'] );	
		if ( $id == 0 )							
			return $result;
		
		$object_id = absint( $_REQUEST['object_id'] );	
		$object_type = sanitize_title( $_REQUEST['object_type'] );			
		$args = ['conditions' => ['key' => 'id', 'compare' => '<', 'value' => $id], 'number' => 2, 'order' => 'DESC', 'object_query' => []];
		switch ( $object_type ) 
		{		
			case 'orders' :	
				$args['object_query'][] = ['object_type' => 'order', 'object_id' => $object_id];
				$args['folder_not_in'] = 'deleted';
			break;
			case 'contacts' :			
				$args['emails'] = usam_get_contact_emails( $object_id );
				$args['folder_not_in'] = 'deleted';
			break;
			case 'companies' :					
				$args['emails'] = usam_get_company_emails( $object_id, true );
				$args['folder_not_in'] = 'deleted';
			break;		
			case 'email' :
				$email = usam_get_email( $id );		
				$args['mailbox'] = $email['mailbox_id'];
				$args['folder'] = $email['folder'];
			break;
		}		
		$email = usam_get_emails( $args );		
		if ( isset($email[0]->body) )										
		{
			$result['content'] = preg_replace_callback("/\n>+/u", 'usam_email_replace_body', $email[0]->body );						
			ob_start();			
			usam_email_html_header( $email[0]->id );
			$result['header'] = ob_get_clean();
			$result['id'] = $email[0]->id;
			$result['next'] = isset($email[1])?$email[1]->id:0;
		}
		return $result;  
	}	
	
	function controller_display_sms()
	{		
		$id = absint( $_REQUEST['id'] );	
		
		$sms = usam_get_sms( $id );		
		if ( !empty($sms) )
			usam_employee_viewing_objects( array( 'object_type' => 'sms', 'object_id' => $id, 'value' => $sms['phone'] ) );	
		
		if ( !empty($sms['sent_at']) ) 
			$sms['date'] = usam_local_date( $sms['sent_at'], 'd.m.y H:i' );
		else
			$sms['date'] = usam_local_date( $sms['date_insert'], 'd.m.y H:i' );
		return $sms;  
	}	
	
	function controller_add_keyword()
	{
		require_once( USAM_FILE_PATH .'/includes/seo/keyword.class.php' );
		$keyword = sanitize_text_field($_POST['keyword']);
		return usam_insert_keyword( array( 'keyword' => $keyword, 'source' => 'yandex', 'importance' => 0 ) );		
	}	
	
	function controller_delete_keyword()
	{
		require_once( USAM_FILE_PATH .'/includes/seo/keyword.class.php' );
		$id  = absint($_POST['id']);
		return usam_delete_keyword( $id ); 		
	}	

	function controller_seo_title_product_save()
	{			
		$i = 0;		
		if ( isset($_POST['products']) )
		{	
			$products = $_POST['products'];		
			foreach( $products as $product_id => $product )
			{
				$product_data = array();
				$product_data['ID'] = (int)$product_id;	
				if ( isset($product['product_title']) )
					$product_data['post_title'] = sanitize_text_field(stripslashes($product['product_title']));	
				if ( isset($product['product_content']) )
					$product_data['post_content'] = sanitize_textarea_field(stripslashes($product['product_content']));	
				if ( isset($product['product_excerpt']) )
					$product_data['post_excerpt'] = sanitize_textarea_field(stripslashes($product['product_excerpt']));				
				wp_update_post( $product_data );	
				$i++;
			}
		}
		return usam_get_callback_messages(['updated' => $i]);
	}	
		
	function controller_loading_information()
	{	
		require_once( USAM_FILE_PATH . '/includes/parser/product-parser.class.php' );
		$product_id = absint($_POST['product_id']);
		$url = sanitize_text_field($_POST['url']);	
		$webspy = new USAM_Product_Parser( $product_id );
		return $webspy->get_data( $url );
	}		
	
	function controller_save_variation()
	{					
		if ( empty($_POST['productmeta']) )
			return;
		
		$anonymous_function = function($a) { return false; };	
		add_filter( 'update_price_primary_product_variations', $anonymous_function );	
		$parent_id  = absint($_POST['parent_id']);
		$updated = 0;
		
		$type = usam_get_product_type_sold( $parent_id );
		
		$storages = usam_get_storages( );
		foreach ( $_POST['productmeta'] as $id => $meta ) 
		{
			$meta['virtual'] = $type;
			
			$id = absint( $id );		
			$data = ['product_type' => 'variation', 'productmeta' => $meta];			
			if ( isset($_POST['prices']) )
			{
				$data['prices'] = $_POST['prices'][$id];
			}
			foreach ( $storages as $storage )
			{
				if ( !empty($_POST['not_limited'][$id]) )
					$data['product_stock']['storage_'.$storage->id] = USAM_UNLIMITED_STOCK;
				else
					$data['product_stock']['storage_'.$storage->id] = isset($_POST['storage_'.$storage->id][$id])?$_POST['storage_'.$storage->id][$id]:0;
			}				
			$product = new USAM_Product( $id );	
			$product->set( $data );
			$product->save_product_meta( );	
			$updated++;
		}		
		$_product = new USAM_Product( $parent_id );
		$_product->save_prices( );	
		$_product->save_stocks( );	
		return usam_get_callback_messages(['updated' => $updated]);
	}	
				
	function controller_save_nav_menu_metaboxes() 
	{		
		$hidden_meta_boxes = get_user_option( 'usam_metaboxhidden_nav_menus' );
		if ( empty($hidden_meta_boxes) )
			$hidden_meta_boxes = array();	
		
		if ( isset($_POST['id']) )
		{
			$metabox = sanitize_title($_POST['id']);	 		
			$hidden = absint($_POST['hidden']);	 
			
			if ( $hidden == 0 && isset($hidden_meta_boxes[$metabox]) )
				unset($hidden_meta_boxes[$metabox]);
			else
				$hidden_meta_boxes[$metabox] = $hidden;
			
			$user_id = get_current_user_id();
			update_user_option( $user_id, 'usam_metaboxhidden_nav_menus', $hidden_meta_boxes );
		}
	}
	
	function controller_save_tab_calendar()
	{		
		$tab = sanitize_title($_POST['tab']);	
		$user_id = get_current_user_id();
		update_user_meta($user_id, 'usam_tab_calendar', $tab);
	}			
	
	function controller_edit_blank()
	{		
		$return = '';
		if ( isset($_REQUEST['blank']))
		{			
			$blank = sanitize_title($_REQUEST['blank']);			
			$company = absint($_REQUEST['company']);
			ob_start();					
				
			echo usam_get_edit_printing_forms( $blank, $company );
		
			$return = ob_get_contents();
			ob_end_clean();				
		}
		return $return;	
	}

	function controller_save_blank()
	{	
		$input = stripslashes_deep($_POST['input']);			
		$blank = sanitize_title($_POST['blank']);	
		$company = absint($_POST['company']);			
		$printing_form_options = (array)get_option( 'usam_printing_form', array() );	
		$option = array();	
		foreach( $input as $args )
		{
			$key = sanitize_title($args['name']);
			$option['data'][$key] = $args['value'];	
		}
		if ( !empty($_POST['textarea']) )
		{
			$textarea = stripslashes_deep($_POST['textarea']);
			foreach( $textarea as $args )
			{
				$key = sanitize_title($args['name']);
				$option['data'][$key] = stripslashes($args['value']);	
			}	
		}
		if ( isset($_POST['table']) )
		{ 
			$table = array();
			foreach( $_POST['table'] as $args )
			{										
				if ( !empty($args['name']) )
					$table[] = array( 'title' => sanitize_text_field($args['value']), 'name' => sanitize_title($args['name']) );					
			}				
			$option['table'] = $table;
		}	
		if ( empty($printing_form_options[$company]) )			
			$printing_form_options[$company] = array();	
		if ( empty($printing_form_options[$company][$blank]) )			
			$printing_form_options[$company][$blank] = array();	
		$printing_form_options[$company][$blank] = $option;
		update_option( 'usam_printing_form', $printing_form_options );			
	}
		
	function controller_get_mail_template() 
	{			
		if ( !empty($_POST['template']) )
		{
			$template = sanitize_text_field($_POST['template']);
			return usam_get_email_template( $template );		
		}		
		return '';
	}		
	
	function controller_test_mailbox()
	{
		$mailbox_id = absint( $_POST['id'] );
		$mailboxes = new USAM_POP3( $mailbox_id );
		$errors = $mailboxes->get_message_errors();
		if ( !empty($errors) )
		{
			$html = '';
			foreach( $errors as $error )
				$html .= $error."<br>";
		}
		else
			$html = __('Соединение установлено','usam');
		return $html;
	}
	
	public function controller_get_capabilities()
	{
		ob_start();	
		$page = new USAM_Page_Tabs( $_POST['page'], $_POST['tab'] );
		$page->display_current_tab();			
		return ob_get_clean();	
	}
			
	public function controller_get_form_confirmation_delete()
	{
		$html = "<div class='modal-body'>
		<div class='action_buttons'>				
			<button type='button' class='js-action-delete-item button-primary button' data-dismiss='modal' aria-hidden='true'>".__('Удалить', 'usam')."</button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'>".__('Отменить', 'usam')."</button>
		</div></div>";	
		return usam_get_modal_window( __('Подтвердите','usam'), 'confirmation_delete_item', $html, 'small' );
	}
	
	public function controller_save_option()
	{
		$options = stripslashes_deep( $_POST['options'] );	
		foreach ( $options as $key => $value ) 
		{  
			update_option( 'usam_'.$key, $value );
		}
		return usam_get_callback_messages(['update' => 1]);
	}	
	
	public function controller_variant_management()
	{
		$updated = 0;
		if ( !empty($_POST['variation1']) && !empty($_POST['variation2']))
		{
			$variation1 = absint($_POST['variation1']);	
			$variation2 = absint($_POST['variation2']);
			$products = usam_get_products(['fields' => 'ids', "tax_query" => [['taxonomy' => 'usam-variation', 'field' => 'id', 'terms' => [$variation1]]], 'prices_cache' => false, 'stocks_cache' => false]);		
			foreach ( $products as $product_id ) 
			{
				wp_remove_object_terms( $product_id, [$variation1], 'usam-variation' );
				wp_set_object_terms( $product_id, [$variation2], 'usam-variation', true );
			}
			$updated = count($products);
		}
		return usam_get_callback_messages(['updated' => $updated]);
	}		
}
new USAM_Admin_Ajax();	