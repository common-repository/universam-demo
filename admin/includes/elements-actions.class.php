<?php
final class USAM_Elements_Actions
{	
	private static $action = null;
	private static $records = [];
	
	public static function start( $type_item = null, $action = null )
	{						
		if ( $type_item == null )
		{
			if ( isset($_REQUEST['item']) )
				$type_item = sanitize_title($_REQUEST['item']);
			else
				return false;
		} 
		if ( $action === null )
		{
			if ( isset($_REQUEST['action']) )
				self::$action = sanitize_title($_REQUEST['action']);
			else
				return false;
		}		
		else
			self::$action = $action; 
		if ( isset($_REQUEST['cb']) )
		{
			if ( is_array($_REQUEST['cb'])) 	
				$records = $_REQUEST['cb'];			
			else 	
				$records = explode( ',', $_REQUEST['cb'] );			
			foreach ( $records as $record ) 
			{            
			   self::$records[] = sanitize_text_field( $record );
			}	
		}
		elseif ( isset($_REQUEST['id']) ) 
			self::$records[] = sanitize_text_field($_REQUEST['id']); 
	
		$result = false;
		$method = 'controller_'.$type_item;		
		if ( method_exists(__CLASS__, $method) )
			$result = self::$method( ); 
		return apply_filters( 'usam_'.$type_item.'_actions', $result, $action, self::$records );
	}	
	
	private static function actions_export( $type = '' ) 
	{	
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
		switch( self::$action )
		{	
			case 'delete':							
				foreach ( self::$records as $id )
				{				
					usam_delete_exchange_rule( $id );
				}	
				return array( 'deleted' => count(self::$records) ); 	
			break;	
			case 'download':			
				$id = self::$records[0];
				$rule = usam_get_exchange_rule( $id );		
				if ( !empty($rule) )
				{
					$file_generation = usam_get_exchange_rule_metadata( $id, 'file_generation' );
					switch( $rule['type'] )
					{	
						case 'pricelist':	
							require_once( USAM_FILE_PATH . '/includes/product/price_list.class.php' );
							$export = new USAM_Price_List( $id );
						break;
						case 'company_export':									
							require_once( USAM_FILE_PATH . '/includes/crm/company_exporter.class.php' );
							$export = new USAM_Companies_Exporter( $id );
						break;
						case 'contact_export':		
							require_once( USAM_FILE_PATH . '/includes/crm/contact_exporter.class.php' );
							$export = new USAM_Contacts_Exporter( $id );
						break;
						case 'product_export':		
							require_once( USAM_FILE_PATH . '/includes/product/product_exporter.class.php' );
							$export = new USAM_Product_Exporter( $id );
						break;
						case 'order_export':	
							if ( !current_user_can('export_order') )
								return ['access' => 1];
							require_once( USAM_FILE_PATH . '/includes/document/order_exporter.class.php' );
							$export = new USAM_Order_Exporter( $id );
						break;
						default:
							return ['access' => 1];
						break;
					}
					ob_start();
					if ( $file_generation )
					{					
						$file_path = USAM_UPLOAD_DIR."exchange/exporter_{$id}.".usam_get_type_file_exchange( $rule['type_file'], 'ext' );				
						if ( is_file( $file_path ) )
						{
							ob_start();
							readfile($file_path);
							$data = ob_get_clean();	
						}
						else
						{
							$i = $export->get_total();	
							usam_create_system_process( __("Создание прайс-листа", "usam" ).' - '.$rule['name'], $rule['id'], 'pricelist_creation', $i, 'exchange_'.$rule['type']."-".$rule['id'] );
							return ['add_event' => 1];
						}
					}	
					else
					{											
						$output = $export->start();		
						ob_start();
						if ( is_string($output) && @is_file($output) )
						{
							readfile($output);	
							$data = ob_get_clean();	
							return ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $rule['name'].".zip"];
						}
						else			
							echo $output;	
						$data = ob_get_clean();								
					}	
					if ( $rule['type_file'] == 'exel' )
						return ['download' => "data:application/vnd.ms-excel;base64,".base64_encode($data), 'title' => $rule['name'].'.'.usam_get_type_file_exchange( $rule['type_file'], 'ext' )];
					else
						return ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $rule['name'].'.'.usam_get_type_file_exchange( $rule['type_file'], 'ext' )];
				}				
			break;
			default:
				if (strpos(self::$action, 'download') !== false) 
				{					
					$str = explode("-",self::$action);		
					$item_id = absint(self::$records[0]);
					$id = absint($str[1]);							
					$rule = usam_get_exchange_rule( $id );		
					if ( !empty($rule) )
					{
						switch( $rule['type'] )
						{	
							case 'pricelist':	
								require_once( USAM_FILE_PATH . '/includes/product/price_list.class.php' );
								$export = new USAM_Price_List( $id );
							break;
							case 'company_export':									
								require_once( USAM_FILE_PATH . '/includes/crm/company_exporter.class.php' );
								$export = new USAM_Companies_Exporter( $id );
							break;
							case 'contact_export':		
								require_once( USAM_FILE_PATH . '/includes/crm/contact_exporter.class.php' );
								$export = new USAM_Contacts_Exporter( $id );
							break;
							case 'order_export':	
								if ( !current_user_can('export_order') )
									return ['access' => 1];
								require_once( USAM_FILE_PATH . '/includes/document/order_exporter.class.php' );
								$export = new USAM_Order_Exporter( $id );
							break;
							default:
								return ['access' => 1];
							break;
						}						
						$output = $export->start(['include' => $item_id]);
						ob_start();
						if ( is_string($output) && file_exists($output) )
						{	
							readfile($output);						
							unlink( $output );
							$data = ob_get_clean();	
							return ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $rule['name'].".zip"];
						}
						else
						{
							echo $output;	
							$data = ob_get_clean();		
							if ( $rule['type_file'] == 'exel' )
								return ['download' => "data:application/vnd.ms-excel;base64,".base64_encode($data), 'title' => $rule['name'].'.'.usam_get_type_file_exchange( $rule['type_file'], 'ext' )];
							else
								return ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $rule['name'].'.'.usam_get_type_file_exchange( $rule['type_file'], 'ext' )];
						}
					}						
				}
			break;
		}	
		return ['access' => 1];
	}
	
	private static function actions_properties() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_property( $id );
				return array( 'deleted' => count(self::$records) ); 
			break;	
			case 'activate':					
				foreach ( self::$records as $id )	
				{
					usam_update_property( $id, array('active' => 1) );	
				}
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'deactivate':	
				foreach ( self::$records as $id )	
				{
					usam_update_property( $id, array('active' => 0) );		
				}
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'profile':	
				foreach ( self::$records as $id )	
					usam_update_property_metadata($id, 'profile', 1 );
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'no_profile':	
				foreach ( self::$records as $id )	
					usam_update_property_metadata($id, 'profile', 0 );
				return array( 'updated' => count(self::$records) );
			break;
		}	
		return false;
	}
	
	private static function actions_import( $type ) 
	{ 					
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
		switch( self::$action )
		{		
			case 'delete':
				if ( self::$records )
					foreach ( self::$records as $id )
					{				
						usam_delete_exchange_rule( $id );
					}	
				return ['deleted' => count(self::$records)]; 
			break;	
			case 'copy':	
				$rule = usam_get_exchange_rule( self::$records[0] );				
				$id = usam_insert_exchange_rule( $rule );
				$metadata = usam_get_exchange_rule_metadata( self::$records[0] ); 	
				foreach ( $metadata as $meta ) 
				{
					if ( $meta->meta_key != 'result_exchange' )
						usam_update_exchange_rule_metadata($id, $meta->meta_key, maybe_unserialize($meta->meta_value) );
				}
				return ['id' => $id, 'form' => 'edit', 'form_name' => $rule['type']];
			break;
			case 'delete_products':	
				$result = false;
				if ( self::$records )
				{					
					require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
					$rules = usam_get_exchange_rules(['include' => self::$records]);
					foreach( $rules as $rule )
					{
						$args = ['post_status' => 'all', 'post_type' => 'usam-product', 'productmeta_query' => [['key' => 'rule_'.$rule->id, 'compare' => 'EXISTS']]];
						$i = usam_get_total_products( $args );	
						if( usam_create_system_process( sprintf(__('Удаление товаров, загруженные импортом %s','usam'),$rule->name), $args, 'delete_post', $i, 'delete_import_posts_'.$rule->id ) )
							$result = true;
					}
				}
				if ( $result )
					return ['add_event' => 1]; 
			break;
			case 'start_import':
				if ( self::$records )
				{
					$events = usam_get_system_process();				
					require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
					$rules = usam_get_exchange_rules(['include' => self::$records]);
					foreach ( $rules as $rule )
					{
						if ( $rule->exchange_option !== 'email' )
							usam_start_exchange( (array)$rule );
					}
				}
				return ['add_event' => 1]; 
			break;				
		}
	}
	
	private static function actions_documents( $type ) 
	{		
		switch( self::$action )
		{		
			case 'delete':			
				if ( self::$records )
					$i = usam_delete_documents(['include' => self::$records]);	
				return ['deleted' => $i]; 
			break;		
			case 'copy':				
				if ( current_user_can('add_'.$type) )
				{
					$document_id = usam_document_copy( self::$records[0] );	
					$document = usam_get_document( $document_id );
					return ['form' => 'edit', 'form_name' => $document['type'], 'id' => $document_id];				
				}
			break;	
			case 'invoice':			
				if ( current_user_can('add_invoice') )
				{
					$document_id = 0;
					foreach ( self::$records as $id )
					{
						usam_update_document( $id, ['status' => 'approved']);
						$document_id = usam_document_copy( $id, ['type' => 'invoice']);	
					}
					if( count(self::$records) == 1 )
						return array( 'form' => 'edit', 'form_name' => 'invoice', 'id' => $document_id  );	
					else
						return ['ready' => 1];			
				}
			break;
			case 'download':					
				$zip = new ZipArchive();		
				$file_path = wp_tempnam();	
				if ( $zip->open($file_path, ZIPARCHIVE::CREATE) === true ) 
				{					
					$data = usam_get_data_printing_forms( $type );							
					$files = [];
					foreach ( self::$records as $id )		
					{
						$file_path_tempnam = wp_tempnam();
						$files[] = $file_path_tempnam;
						$filename = usam_get_document_full_name( $data['object_name'], $id ).'.pdf';
						$html = usam_get_export_form_to_pdf( $type, $id );							
						$f = fopen( $file_path_tempnam, 'w' );
						fwrite($f, $html);	
						fclose($f);			
						$zip->addFile( $file_path_tempnam, $filename );													
					} 						
					$zip->close();		
					foreach( $files as $file )
						unlink($file);
					
					$document = usam_get_details_document( $type );
					$name = $document?$document['plural_name']:__("Документы","usam");
					
					$data = file_get_contents($file_path);
					unlink($file_path);
					return ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $name.".zip"];	
				}					
			break;	
			case 'act':
				if ( current_user_can('add_act') && self::$records )
				{
					$document_id = 0;
					require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
					$documents = usam_get_documents(['include' => self::$records, 'status__not_in' => 'notpaid', 'cache_results' => true]);
					$i = 0;
					foreach ( $documents as $document )
					{
						$child_documents = usam_get_documents(['fields' => 'id', 'child_document' => ['id' => $document->id, 'type' => $document->type], 'type' => 'act', 'number' => 1]);
						if ( empty($child_documents) )
						{
							usam_update_document( $document->id, ['status' => 'paid'] );
							$document_id = usam_document_copy( $document->id, ['type' => 'act', 'date_insert' => $document->date_insert]);							
						}
						else
							$document_id = $child_documents;
						$i++;	
					}
					if( $i == 1 )
						return ['form' => 'edit', 'form_name' => 'act', 'id' => $document_id];		
					else
						return ['created' => $i];
				}
			break;	
			case 'new_company':
				if ( current_user_can('add_'.$type) )
				{
					$type_price = usam_get_manager_type_price();
					$user_id = get_current_user_id();					
					$insert = ['name' => '', 'type_price' => $type_price, 'manager_id' => $user_id, 'customer_type' => 'company', 'type' => $type, 'status' => 'draft', 'customer_id' => absint($_REQUEST['id'])];							
					$document_id = usam_insert_document( $insert );
					return ['form' => 'edit', 'form_name' => $type, 'id' => $document_id];	
				}
			break;
			case 'new_contact':
				if ( current_user_can('add_'.$type) )
				{	
					$type_price = usam_get_manager_type_price();
					$user_id = get_current_user_id();					
					$insert = ['name' => '', 'type_price' => $type_price, 'manager_id' => $user_id, 'type' => $type, 'status' => 'draft', 'customer_type' => 'contact', 'customer_id' => absint($_REQUEST['id'])];
					$document_id = usam_insert_document( $insert );	
					return ['form' => 'edit', 'form_name' => $type, 'id' => $document_id];	
				}
			break;
			case 'new':
				if ( current_user_can('add_'.$type) )
				{	
					$type_price = usam_get_manager_type_price();
					$user_id = get_current_user_id();					
					$insert = ['name' => '', 'type_price' => $type_price, 'manager_id' => $user_id, 'customer_type' => 'company', 'type' => $type, 'status' => 'draft'];						
					$document_id = usam_insert_document( $insert );
					return ['form' => 'edit', 'form_name' => $type, 'id' => $document_id];	
				}
			break;
			default:
				if (strpos(self::$action, 'status') !== false) 
				{												
					$i = 0;
					$str = explode("-",self::$action);
					$object_status = usam_get_object_status( absint($str[1]) );
					if ( !empty(self::$records) && !empty($object_status) ) 
					{			
						usam_update_object_count_status( false );
						require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
						$documents = usam_get_documents(['include' => self::$records, 'cache_results' => true]);						
						foreach ( $documents as $document )
						{									
							if ( usam_check_document_access( $document, $document->type, 'edit' ) )
							{
								if ( usam_update_document($document->id, ['status' => $object_status['internalname']]) )
									$i++;
							}
						}		
						usam_update_object_count_status( true );
						return ['updated' => $i]; 
					}
				}
			break;
		}			
		return ['access' => 1];;
	}
	
	private static function actions_events() 
	{			
		switch( self::$action )
		{	
			case 'delete':				
				$i = 0;
				if ( self::$records )
				{
					$user_id = get_current_user_id();
					$events = usam_get_events(['include' => self::$records, 'user_work' => $user_id, 'cache_results' => true]);
					foreach ( $events as $event )
					{									
						if ( current_user_can('delete_'.$event->type) && usam_check_event_access( (array)$event, 'delete') )
						{							
							if ( usam_delete_event( $event->id ) >= 1 )
								$i++;
						}
					}
				}				
				return ['deleted' => $i]; 
			break;			
			case 'started':	
			case 'completed':	
				$i = 0;
				$user_id = get_current_user_id();
				$events = usam_get_events(['include' => self::$records, 'user_work' => $user_id, 'cache_results' => true]);	
				foreach ( $events as $event )
				{							
					if ( current_user_can('edit_status_'.$event->type) )
					{
						if ( usam_update_event( $event->id, ['status' => self::$action] ) )
							$i++;
					}
				}	
				return ['updated' => $i]; 
			break;				
		}
	}
	
	private static function actions_coupons() 
	{		
		global $wpdb;	
		switch( self::$action )
		{	
			case 'delete':			
				if ( self::$records )
					$result = $wpdb->query("DELETE FROM ".USAM_TABLE_COUPON_CODES." WHERE id IN ('".implode("','",self::$records)."')");						
				return ['deleted' => count(self::$records)];
			break;		
			case 'activate':							
				if ( self::$records )
					$result = $wpdb->query("UPDATE ".USAM_TABLE_COUPON_CODES." SET `active`='1' WHERE id IN ('".implode("','",self::$records)."')");			
				return ['updated' => count(self::$records)]; 
			break;			
			case 'deactivate':
				if ( self::$records )
					$result = $wpdb->query("UPDATE ".USAM_TABLE_COUPON_CODES." SET `active`='0' WHERE id IN ('".implode("','",self::$records)."')");			
				return ['updated' => count(self::$records)]; 
			break;					
		}	
	}
	
	private static function actions_newsletters( $type ) 
	{		
		switch( self::$action )
		{	
			case 'delete':				
				if ( self::$records )				
					foreach ( self::$records as $id ) 
					{
						usam_delete_newsletter( $id );				
					}					
				return ['deleted' => count(self::$records)]; 
			break;
			case 'copy':
				if ( self::$records )
				{
					$newsletter = usam_get_newsletter( self::$records[0] );	
					$newsletter['status'] = 0;				
					$id = usam_insert_newsletter( $newsletter );
					if ( $id )
					{						
						$settings = usam_get_newsletter_metadata( $newsletter['id'], 'settings' );
						$body = usam_get_newsletter_metadata( $newsletter['id'], 'body' );
						
						usam_update_newsletter_metadata( $id, 'settings', $settings );
						usam_update_newsletter_metadata( $id, 'body', $body );
					}
					return ['form' => 'edit', 'form_name' => $type, 'id' => $id];		
				}
			break;
			case 'sending':		
				$result = false;
				if ( self::$records )
				{
					require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
					$_newsletter = new USAM_Send_Newsletter( self::$records[0] );		
					$result = $_newsletter->send_newsletter();					
				}
				return ['ready' => $result];		
			break;
			default:								
				if ( stripos(self::$action, 'template-') !== false && self::$records )
				{
					require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
					foreach ( self::$records as $id ) 
					{
						$string = str_replace('template-', '',  self::$action);
						$array = explode('-', $string);						
						$email = '';
						if ( $array[1] == 'contact' )
							$email = usam_get_contact_metadata($id, 'email');
						elseif ( $array[1] == 'company' )
							$email = usam_get_company_metadata($id, 'email');		
						elseif ( $array[1] == 'order' )
							$email = usam_get_order_customerdata( $id, 'email' );						
						if ( $email )						
						{						
							$_newsletter = new USAM_Send_Newsletter( $array[0] );		
							$result = $_newsletter->send_email_trigger( $email, [], [['object_id' => $id, 'object_type' => $array[1]]] );	
						}
					}
				}
			break;
		}
	}
		
	private static function controller_carts() 
	{		
		switch( self::$action )
		{		
			case 'delete':	
				if ( self::$records )
					usam_delete_cart(['include' => self::$records]);
				return ['deleted' => count(self::$records)]; 
			break;					
		}	
		return false;
	}	
	
	private static function controller_bonus_cards() 
	{		
		switch( self::$action )
		{		
			case 'delete':		
				$ids = array_map( 'intval', self::$records );
				if ( !empty($ids) )
				{
					$in = implode( ', ', $ids );
					$wpdb->query( "DELETE FROM ".USAM_TABLE_BONUS_TRANSACTIONS." WHERE code IN ($in)" );		
					$wpdb->query( "DELETE FROM ".USAM_TABLE_BONUS_CARDS." WHERE code IN ($in)" );						
				}
				return ['deleted' => count(self::$records)]; 
			break;		
			case 'create_cards':	
				$users = get_users(['fields' => ['ID']]);
				$result = usam_create_system_process( __("Создание бонусных карт", "usam" ), 1, 'create_cards', count($users), 'create_cards' );
				return ['add_event' => $result];
			break;	
		}	
		return false;
	}	
	
	private static function controller_customer_accounts() 
	{		
		switch( self::$action )
		{		
			case 'delete':		
				$ids = array_map( 'intval', self::$records );
				if ( $ids )
				{
					$in = implode( ', ', $ids );
					$wpdb->query( "DELETE FROM ".USAM_TABLE_ACCOUNT_TRANSACTIONS." WHERE account_id IN ($in)" );		
					$wpdb->query( "DELETE FROM ".USAM_TABLE_CUSTOMER_ACCOUNTS." WHERE id IN ($in)" );						
				}
				return ['deleted' => count(self::$records)]; 
			break;	
			case 'create_customer_accounts':				
				$users = get_users(['fields' => ['ID']]);
				usam_create_system_process( __("Создание клиентских счетов", "usam" ), 1, 'create_customer_accounts', count($users), 'create_customer_accounts' );
				return ['add_event' => 1];
			break;				
		}	
		return false;
	}
	
	private static function controller_contacts_export() 
	{		
		return self::actions_export( 'contact_export' );
	}

	private static function controller_export() 
	{		
		return self::actions_export( );
	}	
	
	private static function controller_contacts_duplicate() 
	{	
		global $wpdb;
		switch( self::$action )
		{					
			case 'combine':
				$ids = array_map('intval', array_keys($_REQUEST['cd']));
				if ( empty($ids) )
					return false;
				$i = 0;					
				$fields = usam_get_properties( array( 'type' => 'contact', 'active' => 1, 'fields' => 'code=>name' ) );			
				foreach ( $_REQUEST['cd'] as $id => $duplicat_ids )
				{
					$duplicat_ids = array_map('intval', $duplicat_ids);
					foreach ( $duplicat_ids as $duplicat_id )
						$ids[] = $duplicat_id;					
				}
				$duplicates = usam_get_contacts(['include' => $ids, 'cache_meta' => true, 'cache_results' => true]);
				$contacts = array();
				$duplicat_contacts = array();						
				$ids = array();
				foreach ( $_REQUEST['cd'] as $id => $duplicat_ids )
				{					
					foreach ( $duplicates as $key => $data )
					{
						if ( $data->id == $id )
						{
							$contacts[] = $data;							
							unset($duplicates[$key]);
						}
						elseif ( in_array($data->id, $duplicat_ids) )
						{
							$ids[] = $data->id;
							$duplicat_contacts[$id][] = $data;
							unset($duplicates[$key]);
						}
					}
				}
				$wpdb->query("UPDATE ".USAM_TABLE_CONTACTS." SET `user_id`=0 WHERE id IN (".implode(',',$ids).") AND user_id!=0" ); //удалить раньше, чтобы дать возможность обновить user_id
				foreach ( $contacts as $contact_key => $contact_data )
				{							
					if ( isset($duplicat_contacts[$contact_data->id]) )
					{						
						$new = array();
						$duplicat_ids = array();
						foreach ( $duplicat_contacts[$contact_data->id] as $duplicat_contact_key => $duplicat_contact_data )
						{			
							$duplicat_ids[] = $duplicat_contact_data->id;
							foreach ( $duplicat_contact_data as $key => $value )
							{ 
								if ( empty($contact_data->$key) && $contact_data->$key != $value )
									$new[$key] = $value;
							}			
							foreach ( $fields as $meta_key => $data )
							{									
								$metadata = usam_get_contact_metadata( $contact_data->id, $meta_key );					
								if ( empty($metadata) )
								{ 
									$meta_value = usam_get_contact_metadata( $duplicat_contact_data->id, $meta_key );
									if ( !empty($meta_value) )
										$update = usam_update_contact_metadata( $contact_data->id, $meta_key, $meta_value );
								}
							}
							unset($duplicat_contacts[$contact_data->id][$duplicat_contact_key]);
						}
						if ( !empty($duplicat_ids) )
						{
							$in = implode(",",$duplicat_ids);
							$wpdb->query("UPDATE ".USAM_TABLE_VISITS." SET `contact_id`={$contact_data->id} WHERE contact_id IN ($in)");
							$wpdb->query("UPDATE ".USAM_TABLE_ORDERS." SET `contact_id`={$contact_data->id} WHERE contact_id IN ($in)");
							$wpdb->query("UPDATE ".USAM_TABLE_DOCUMENT_CONTACTS." SET `contact_id`={$contact_data->id} WHERE contact_id IN ($in)");
							$wpdb->query("UPDATE ".USAM_TABLE_CHAT_USERS." SET `contact_id`={$contact_data->id} WHERE contact_id IN ($in)");
							$wpdb->query("UPDATE ".USAM_TABLE_CHAT." SET `contact_id`={$contact_data->id} WHERE contact_id IN ($in)");
							$wpdb->query("UPDATE ".USAM_TABLE_CUSTOMER_REVIEWS." SET `contact_id`={$contact_data->id} WHERE contact_id IN ($in)");
							$wpdb->query("UPDATE ".USAM_TABLE_DOCUMENTS." SET `customer_id`={$contact_data->id} WHERE customer_id IN ($in)  AND customer_type='contact'");
							$wpdb->query("UPDATE ".USAM_TABLE_RIBBON_LINKS." SET `object_id`={$contact_data->id} WHERE object_id IN ($in) AND object_type='contact'");
							$wpdb->query("UPDATE ".USAM_TABLE_FILES." SET `object_id`={$contact_data->id} WHERE object_id IN ($in) AND type='contact'");							
						}
						if ( !empty($new) )
						{
							$contact = new USAM_Contact( $contact_data->id );		
							$contact->set( $new );							
							$contact->save( );		
							$i++;
						}								
					}	
					unset($contacts[$contact_key]);
				}				
				$wpdb->query( "DELETE FROM " . USAM_TABLE_CONTACT_META . " WHERE contact_id IN (".implode(',',$ids).")");
				$wpdb->query( "DELETE FROM " . USAM_TABLE_CONTACTS . " WHERE id IN (".implode(',',$ids).")" ); //удалить раньше, чтобы дать возможность обновить user_id
				return ['updated' => $i, 'deleted' => count($ids)]; 
			break;			
		}
		return false;
	}
	
	private static function controller_contacts() 
	{	
		switch( self::$action )
		{	
			case 'delete':	
				$i = 0;	
				if ( current_user_can('delete_contact') )
				{
					$i = count(self::$records);		
					if ( !empty(self::$records) )
						usam_delete_contacts(['include' => self::$records]);
				}
				return ['deleted' => $i]; 					
			break;				
			case 'sex':	
				$i = 0;		
				if ( current_user_can('edit_contact') && self::$records )
				{				
					$meta_query = [['key' => 'sex', 'compare' => 'NOT EXISTS'],['key' => 'sex', 'compare' => '=', 'value' => ''], 'relation' => 'OR'];
					$contacts = usam_get_contacts(['fields' => ['id', 'full_name'], 'include' => self::$records, 'meta_query' => $meta_query, 'conditions' => ['key' => 'appeal', 'value' => '', 'compare' => '!=']]);
					foreach ( $contacts as $id )
					{
						$results = apply_filters( 'usam_clean_name', [], $this->full_name );
						if ( !empty($results['gender']) && $results['gender'] != 'НД' )
						{
							$sex = $results['gender'] == 'М'?'m':'f';							
							if ( usam_update_contact_metadata( $id, 'sex', $sex ) )
								$i++;
						}						
					}
				}
				return ['updated' => $i]; 					
			break;
			case 'coordinates':	
				$i = 0;
				if ( current_user_can('edit_contact') && self::$records )
				{					
					$meta_query = [];					
					$meta_query[] = ['key' => 'latitude', 'compare' => 'NOT EXISTS'];		
					$meta_query[] = ['key' => 'address', 'compare' => '!=', 'value' => ''];					
					$contacts = usam_get_contacts(['fields' => 'id', 'meta_query' => $meta_query, 'include' => self::$records]);			
					foreach ( $contacts as $contact_id )
					{
						$address = usam_get_full_contact_address( $contact_id );
						if ( !empty($address) )
						{
							$coordinates = apply_filters( 'usam_upload_coordinates', [], $address );
							if ( !empty($coordinates) )
							{
								usam_update_contact_metadata( $contact_id, 'latitude', $coordinates['geo_lat'] );
								usam_update_contact_metadata( $contact_id, 'longitude', $coordinates['geo_lon'] );
								$i++;
							}	
						}										
					}	
				}
				return ['updated' => $i]; 					
			break;
			case 'delete_lists':
				$i = 0;
				if ( current_user_can('edit_contact') && self::$records )
				{
					$i = count(self::$records);
					foreach ( self::$records as $id )
					{		
						$emails = usam_get_contact_emails( $id );
						$phones = usam_get_contact_phones( $id );
						$communications = array_merge( $emails, $phones );	
						if ( $communications )
							usam_delete_subscriber_lists(['communication' => $communications]);					
					}	
				}
				return ['update' => $i]; 								
			break;
			case 'employee':
				$i = 0;
				if ( current_user_can('edit_employee') && self::$records )
				{
					$i = count(self::$records);
					foreach ( self::$records as $id )
						usam_update_contact( $id, ['contact_source' => 'employee']);	
				}
				return ['update' => $i]; 								
			break;			
		}
		return false;
	}
		
	private static function controller_companies() 
	{	
		switch( self::$action )
		{	
			case 'delete':	
				$i = 0;
				if ( current_user_can('delete_company') && self::$records )
					$i =  usam_delete_companies([ 'include' => self::$records]);
				return ['deleted' => $i]; 					
			break;	
			case 'seller':
				if ( current_user_can('edit_company') && self::$records )
				{
					foreach ( self::$records as $id )
						usam_insert_seller(['seller_type' => 'company', 'customer_id' => $id]);					
					return ['update' => self::$records ]; 
				}
				return ['update' => 0];
			break;			
			case 'delete_lists':
				$i = 0;
				if ( current_user_can('edit_company') && self::$records )
				{
					$i = count(self::$records);
					foreach ( self::$records as $id )
					{							
						$emails = usam_get_company_emails( $id );
						$phones = usam_get_company_phones( $id );
						$communications = array_merge( $emails, $phones );	
						if ( $communications )
							usam_delete_subscriber_lists(['communication' => $communications]);	
					}	
				}
				return ['update' => $i]; 		
			break;					
		}
		return false;
	}
	
	private static function controller_companies_communication_errors() 
	{	
		switch( self::$action )
		{	
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_communication_error( $id );	
				return array( 'deleted' => count(self::$records) ); 
			break;	
			case 'change_status': 
				$status = absint($_REQUEST['status']);	
				foreach ( self::$records as $id )
					usam_update_communication_error( $id, array('status' => $status ) );
				
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'activate': 				
				foreach ( self::$records as $id )
					usam_update_communication_error( $id, array('status' => 2 ) );
				
				return ['updated' => count(self::$records)]; 
			break;	
			case 'verify_email':					
				global $wpdb;
				$count = $wpdb->get_var("SELECT COUNT(*) FROM `".USAM_TABLE_COMPANY_META."` AS meta LEFT OUTER JOIN `".USAM_TABLE_COMMUNICATION_ERRORS."` AS com_error ON (meta.meta_value=com_error.communication) WHERE com_error.status!= 0 AND meta.meta_key LIKE '%email%' AND meta.meta_value!='' AND com_error.id IS NULL");
				if ( $count )
				{	
					$result = usam_create_system_process( __("Проверка электронных адресов компаний", "usam" ), 0, 'companies_verify_email', $count, 'companies_verify_email' );
					return ['add_event' => $result];
				}					
				return ['ready' => 1];				
			break;					
		}
		return false;
	}
	
	private static function controller_company_duplicate() 
	{	
		global $wpdb;
		switch( self::$action )
		{	
			case 'combine': 				
				$ids = array_map('intval', array_keys($_REQUEST['cd']));
				if ( empty($ids) )
					return false;
				$i = 0;		 	
				$fields = usam_get_properties( array( 'type' => array( 'company' ), 'active' => 1, 'fields' => 'code=>name' ) );	
				foreach ( $_REQUEST['cd'] as $id => $duplicat_ids )
				{
					$ids[] = $id;
					$duplicat_ids = array_map('intval', $duplicat_ids);
					foreach ( $duplicat_ids as $duplicat_id )
						$ids[] = $duplicat_id;
				}
				$duplicates = usam_get_companies( array('include' => $ids, 'cache_meta' => true, 'cache_results' => true ) );
				$duplicat_companies = array();
				$companies = array();		
				$ids = array();
				foreach ( $_REQUEST['cd'] as $id => $duplicat_ids )
				{					
					foreach ( $duplicates as $data )
					{
						if ( $data->id == $id )
							$companies[] = $data;							
						elseif ( in_array($data->id, $duplicat_ids) )
						{
							$ids[] = $data->id;
							$duplicat_companies[$id][] = $data;
						}
					}
				}
				foreach ($companies as $company )
				{						
					if ( isset($duplicat_companies[$company->id]) )
					{						
						$new = array();
						$duplicat_ids = array();
						foreach ( $duplicat_companies[$company->id] as $duplicat_company )
						{			
							$duplicat_ids[] = $duplicat_company->id;
							foreach ( $duplicat_company as $key => $value )
							{ 
								if ( empty($company->$key) && $company->$key != $value )
									$new[$key] = $value;
							}			
							foreach ( $fields as $meta_key => $data )
							{									
								$metadata = usam_get_company_metadata( $id, $meta_key );									
								if ( empty($metadata) )
								{
									$meta_value = usam_get_company_metadata( $duplicat_id, $meta_key );
									if ( !empty($meta_value) )
										$update = usam_update_company_metadata( $id, $meta_key, $meta_value );
								}
							}
						}
						if ( !empty($duplicat_ids) )
						{
							$in = implode(",",$duplicat_ids);
							$wpdb->query("UPDATE ".USAM_TABLE_ORDERS." SET `company_id`={$company->id} WHERE company_id IN ($in)");
							$wpdb->query("UPDATE ".USAM_TABLE_RIBBON_LINKS." SET `object_id`={$company->id} WHERE object_id IN ($in) AND object_type='company'");
							$wpdb->query("UPDATE ".USAM_TABLE_DOCUMENTS." SET `customer_id`={$company->id} WHERE customer_id IN ($in)  AND customer_type='company'");
						}
						if ( !empty($new) )
						{
							usam_update_company( $company->id, $new );	
							$i++;
						}								
					}							
				} 
				$wpdb->query( "DELETE FROM " . USAM_TABLE_COMPANY . " WHERE id IN (".implode(',',$ids).")" );	
				$wpdb->query( "DELETE FROM " . USAM_TABLE_COMPANY_META . " WHERE contact_id IN (".implode(',',$ids).")");
				return array(  array( 'updated' => $i, 'deleted' => count($ids) ) ); 
			break;				
		}
		return false;
	}
	
	private static function controller_calendars() 
	{	
		$user_id = get_current_user_id();		
		switch( self::$action )
		{	
			case 'delete':	
				$i = 0;				
				$calendars = usam_get_calendars( );			
				foreach ( self::$records as $id )
				{						
					foreach ($calendars as $key => $calendar )
					{
						if ( $id == $calendar['id'] )
						{
							if ( $calendar['user_id'] == $user_id )
							{
								unset($calendars[$key]);					
								$i++;
							}
							break;
						}
					}
				}	
				update_site_option('usam_calendars', serialize($calendars) );	
				return array( 'deleted' => $i ); 					
			break;				
		}
		return false;
	}
	
	private static function controller_contacts_communication_errors() 
	{		
		require_once( USAM_FILE_PATH . '/includes/crm/communication_error.class.php' );	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_communication_error( $id );	
				return array( 'deleted' => count(self::$records) ); 
			break;	
			case 'change_status': 
				$status = absint($_REQUEST['status']);	
				foreach ( self::$records as $id )
					usam_update_communication_error( $id, array('status' => $status ) );
				
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'activate': 				
				foreach ( self::$records as $id )
					usam_update_communication_error( $id, array('status' => 2 ) );
				
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'verify_email':					
				global $wpdb;
				$count = $wpdb->get_var("SELECT COUNT(*) FROM `".USAM_TABLE_CONTACT_META."` AS meta LEFT OUTER JOIN `".USAM_TABLE_COMMUNICATION_ERRORS."` AS com_error ON (meta.meta_value=com_error.communication) WHERE com_error.status!= 0 AND meta.meta_key LIKE '%email%' AND meta.meta_value!='' AND com_error.id IS NULL");
				if ( $count )
				{ 
					$result = usam_create_system_process( __("Проверка электронных адресов контактов", "usam" ), 0, 'contacts_verify_email', $count, 'contacts_verify_email' );
					return ['add_event' => $result];
				}					
				return ['ready' => 1];				
			break;			
		}	
		return false;
	}
	
	private static function controller_contact_group() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_group( $id );
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_invoice_group() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_group( $id );
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_company_group() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_group( $id );
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_contact_property_groups() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_property_group( $id );
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_company_property_groups() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_property_group( $id );
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_contact_properties() 
	{	
		return self::actions_properties();
	}	
	
	private static function controller_company_properties() 
	{	
		return self::actions_properties();
	}	
	
	private static function controller_order_properties() 
	{	
		return self::actions_properties();
	}
	
	private static function controller_company_export() 
	{	
		return self::actions_export( 'company_export' );
	}	

	private static function controller_distance() 
	{	
		global $wpdb;
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $value )
				{
					$location_ids = explode( '-', $value );								
					$result = $wpdb->query("DELETE FROM ".USAM_TABLE_LOCATIONS_DISTANCE." WHERE from_location_id=".$location_ids[0]." AND to_location_id=".$location_ids[1]."");	
				}
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}			
		
	private static function controller_prices() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				global $wpdb;
				if ( self::$records )
				{
					$prices = usam_get_prices(['type' => 'all', 'ids' => self::$records]);	
					
					$codes = array();
					foreach ( $prices as $value )
					{
						do_action( 'usam_type_price_before_delete', $value['id'] );
						$codes[] = 'price_'.$value['code'];
						$codes[] = 'old_price_'.$value['code'];
					}
					$meta_key = implode( "','", $codes); 				
					$deleted_rows = $wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_PRICE." WHERE meta_key IN ('$meta_key')" );	
					usam_delete_data( self::$records, 'usam_type_prices' );		
					foreach ( $prices as $value )
					{
						do_action( 'usam_type_price_delete', $value['id'] );
					}
				}				
				return ['deleted' => count(self::$records)]; 
			break;	
		}	
		return false;
	}	
	
	private static function controller_countries() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				global $wpdb;
				if ( self::$records )
					$wpdb->query( "DELETE FROM " . USAM_TABLE_COUNTRY . " WHERE code IN ('".implode("','", self::$records)."')" );						
				return ['deleted' => count(self::$records)]; 
			break;	
		}	
		return false;
	}		
	
	private static function controller_currency() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				global $wpdb;
				$wpdb->query( "DELETE FROM " . USAM_TABLE_CURRENCY . " WHERE code IN ('".implode("','", self::$records)."')" );						
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}
	
	private static function controller_sales_area() 
	{	
		switch( self::$action )
		{		
			case 'delete':					
				usam_delete_data( self::$records, 'usam_sales_area' );			
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}
		
	private static function controller_languages() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				usam_delete_data( self::$records, 'usam_languages' );			
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}
		
	private static function controller_location_type() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				global $wpdb;
				$ids = implode(',',self::$records);
				if ( self::$records )
					$result = $wpdb->query("DELETE FROM ".USAM_TABLE_LOCATION_TYPE." WHERE id IN ($ids)");	
				return ['deleted' => count(self::$records)]; 
			break;	
		}	
		return false;
	}
	
	private static function controller_location() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				if ( self::$records )
					usam_delete_locations( self::$records );				
				return ['deleted' => count(self::$records)]; 
			break;	
		}	
		return false;
	}
		
	private static function controller_documents() 
	{	
		switch( self::$action )
		{		
			case 'delete':
				$i = 0;
				foreach ( self::$records as $id )
				{				
					if ( usam_delete_document( $id ) )
						$i++;
				} 
				return array( 'deleted' => $i ); 
			break;		
			case 'copy':
				if ( self::$records )
				{
					$document_id = usam_document_copy( self::$records[0] );	
					$document = usam_get_document( $document_id );
					return ['form' => 'edit', 'form_name' => $document['type'], 'id' => $document_id];	
				}
			break;				
		}			
		return false;
	}
		
	private static function controller_invoice() 
	{	
		return self::actions_documents( 'invoice' );
	}
	
	private static function controller_order_contractor() 
	{	
		return self::actions_documents( 'order_contractor' );
	}
	
	private static function controller_checks() 
	{		
		return self::actions_documents( 'check' );
	}
	
	private static function controller_suggestions() 
	{	
		return self::actions_documents( 'suggestion' );
	}
	
	private static function controller_receipts() 
	{	
		return self::actions_documents( 'receipt' );
	}	
		
	private static function controller_movements() 
	{	
		switch( self::$action )
		{						
			default:
				return self::actions_documents( 'movement' );	
			break;				
		}			
	}
	
	private static function controller_buyer_refunds() 
	{	
		return self::actions_documents( 'buyer_refund' );
	}
	
	private static function controller_partner_orders() 
	{	
		return self::actions_documents( 'partner_order' );
	}
	
	private static function controller_decree() 
	{	
		return self::actions_documents( 'decree' );
	}
	
	private static function controller_contracts() 
	{	
		return self::actions_documents( 'contract' );
	}
	
	private static function controller_invoice_payment() 
	{	
		return self::actions_documents( 'invoice_payment' );
	}
	
	private static function controller_payments_received() 
	{	
		return self::actions_documents( 'payment_received' );
	}
	
	private static function controller_payment_orders() 
	{	
		return self::actions_documents( 'payment_order' );
	}
	
	private static function controller_proxy() 
	{	
		return self::actions_documents( 'proxy' );
	}
	
	private static function controller_acts() 
	{	
		return self::actions_documents( 'act' );
	}
	
	private static function controller_vk_users_profiles() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_social_network_profile( $id );		
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}
	
	private static function controller_fb_users_profiles() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_social_network_profile( $id );		
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_ok_groups() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_social_network_profile( $id );		
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_vk_groups() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_social_network_profile( $id );		
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_fb_groups() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
					usam_delete_social_network_profile( $id );		
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_vk_products() 
	{	
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );			
		$i = 0;
		switch( self::$action )
		{		
			case 'delete':		
				if ( !empty($_REQUEST['profile_id']) )
				{			
					$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
					$current_profile = usam_get_social_network_profile( $profile_id );
				}
				else
				{
					$current_profile = (array)usam_get_social_network_profiles(['type_social' => ['vk_group', 'vk_user'], 'number' => 1]);	
				}
				$vkontakte = new USAM_VKontakte_API( $current_profile );	
				foreach ( self::$records as $id )			
				{
					if ( $vkontakte->delete_product( $id ) )
						$i++;
				}
				return ['update' => $i, 'errors' => $vkontakte->get_errors()];
			break;	
			case 'update':									
				if ( !empty(self::$records) )
				{
					$profiles = usam_get_social_network_profiles(['type_social' => 'vk_group']);	
					foreach ( $profiles as $profile ) 					
					{
						$vkontakte = new USAM_VKontakte_API( $profile->id );	
						foreach ( self::$records as $product_id )
						{		
							if ( $vkontakte->edit_product( $product_id ) )
								$i++;
						}
					}	
					return ['update' => $i, 'errors' => $vkontakte->get_errors()];
				}
				else
				{
					$vk = new USAM_VKontakte( );
					$vk->update_products();
					return ['add_event' => 1];
				}
			break;					
		}			
		return false;
	}
	
	private static function controller_vk_wall() 
	{		
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );	
		if ( !empty($_REQUEST['profile_id']) )
		{			
			$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
			$current_profile = usam_get_social_network_profile( $profile_id );
		}
		else
		{
			$current_profile = (array)usam_get_social_network_profiles(['type_social' => ['vk_group', 'vk_user'], 'number' => 1]);
		}	
		$vkontakte = new USAM_VKontakte_API( $current_profile );	
		$i = 0;
		switch( self::$action )
		{		
			case 'delete':	
				foreach ( self::$records as $id )
					if ( $vkontakte->delete_wall( $id ) )
						$i++;
				return ['deleted' => $i, 'errors' => $vkontakte->get_errors()];
			break;				
		}
	}	
	
	private static function controller_vk_contests() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				usam_delete_data( self::$records, 'usam_vk_contest' );	
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}
	
	private static function controller_sms() 
	{	
		switch( self::$action )
		{		
			case 'delete':		
				foreach ( self::$records as $id )
				{
					usam_delete_sms( $id );
				}
				return array( 'deleted' => count(self::$records) ); 
			break;	
			case 'read':				
				foreach ( self::$records as $id )
				{
					usam_update_sms( $id, array('read' => 1) );
				}
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'send':				
				foreach ( self::$records as $id )
				{
					$sms = usam_get_sms( $id );
					$number_message = usam_send_sms( $sms['phone'], $sms['message'] );						
					$args = array( 'folder' => 'outbox' );			
					if ( $number_message )
					{				
						$args['folder'] = 'sent';
						$args['sent_at'] = date( "Y-m-d H:i:s" );	
						$args['server_message_id'] = $number_message;	
						$sent = 1;
					}
					else
						$sent = 0;
					usam_update_sms( $id, $args );					
				}
				return ['send_sms' => $sent]; 
			break;			
		}	
		return false;
	}
	
	
	private static function controller_installed_applications() 
	{
		switch( self::$action )
		{	
			case 'delete':	
				global $wpdb;				
				if ( self::$records )
				{
					$in = implode( ', ', self::$records );	
					$result = $wpdb->query("DELETE FROM ".USAM_TABLE_APPLICATION_META." WHERE service_id IN ($in)");
					$result = $wpdb->query("DELETE FROM ".USAM_TABLE_APPLICATIONS." WHERE id IN ($in)");
				}			
				return ['deleted' => count(self::$records)]; 
			break;
			case 'activate':
				foreach ( self::$records as $id ) 
					usam_update_application( $id, ['active' => 1]);					
				return ['ready' => 1];
			break;
			case 'deactivate':
				foreach ( self::$records as $id ) 
					usam_update_application( $id, ['active' => 0]);					
				return ['ready' => 1];
			break;		
		}						
	}

	private static function controller_email() 
	{ 
		switch( self::$action )
		{	
			case 'delete':	
				if ( !empty(self::$records) )
					usam_delete_emails(['include' => self::$records]);
				return array( 'deleted' => count(self::$records) ); 
			break;
			case 'new': 				
				if ( !empty($_REQUEST['m']) )
					$mailbox_id = absint($_REQUEST['m']);		
				else
				{
					$user_id = get_current_user_id(); 
					$mailbox_id = (int)usam_get_mailboxes(['fields' => 'id', 'user_id' => $user_id, 'number' => 1, 'orderby' => 'sort']);
				}								
				$id = usam_insert_email(['from_name' => '', 'from_email' => '', 'title' => '', 'body' => '', 'folder' => 'drafts', 'to_name' => '','to_email' => '', 'mailbox_id' => $mailbox_id]);
				return ['id' => $id, 'form' => 'edit', 'form_name' => 'email', 'm' => $mailbox_id]; 			
			break;			
			case 'clear': 
				$result = 0;
				if ( !empty($_REQUEST['m']) )
				{
					$mailbox_id = absint($_REQUEST['m']);		
					$result = usam_delete_emails(['fields' => 'id', 'folder' => 'deleted', 'mailbox_id' => $mailbox_id]);
				}
				return ['deleted' => $result];
			break;	
			case 'clearing_email':	// Удалить письма по заданному периоду					
				$mailbox_id = absint($_REQUEST['m']);
				if ( empty($_REQUEST['m']) )
					return ['ready' => 0];	
				
				$day = !empty($_REQUEST['clearing_day'])?sanitize_text_field($_REQUEST['clearing_day']):'';			
				if ( empty($day) )
					return ['ready' => 0];		
				
				$args = [];
				$args['date_query'] = ['before' => date('Y-m-d H:i:s', strtotime('-'.$day.' days')), 'inclusive' => true];		
				$args['fields'] = 'id';		
				$args['mailbox'] = $mailbox_id;				
				$result = usam_delete_emails( $args );
				return ['deleted' => $result];		
			break;				
			case 'add_contact':				
				usam_add_contact_from_email( self::$records );
				return array( 'ready' => 1 ); 
			break;	
			case 'spam':								
				usam_spam_email( self::$records );
				return array( 'deleted' => count(self::$records) );
			break;			
			case 'read':				
				foreach ( self::$records as $id )	
				{
					usam_update_email( $id, array('read' => 1) );
				}
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'not_read':				
				foreach (  self::$records as $id )	
				{
					usam_update_email( $id, array('read' => 0) );
				}
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'important':				
				foreach ( self::$records as $id )	
				{
					usam_update_email( $id, array('importance' => 1) );
				}
				return array( 'updated' => count(self::$records) ); 
			break;							
			case 'not_important':				
				foreach ( self::$records as $id )	
				{
					usam_update_email( $id, array('importance' => 0) );
				}
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'send':				
				$i = 0;
				foreach ( self::$records as $id )
				{
					$_email = new USAM_Email( $id );
					if ( $_email->send_mail() )
						$i++;
				}
				return ['send_email' => $i]; 
			break;		
			case 'reply': 						
				$_email = new USAM_Email( self::$records[0] );					
				$data = $_email->get_data();
				
				$new_data = ['from_name' => $data['to_name'], 'from_email' => $data['to_email'], 'folder' => 'drafts', 'to_name' => $data['from_name'], 'to_email' => $data['from_email'], 'sent_at' => null, 'type' => 'sent_letter', 'mailbox_id' => $data['mailbox_id'], 'title' => $data['title']];	
				
				if ( !empty($data['body']) )
					$new_data['body'] = stripslashes('<br><br><br>'.usam_local_date($data['date_insert'], 'd M Yг., H:i').' '.__('пользователь','usam').' &#8220;'.$data['from_name'].'&#8221; '.__('написал','usam').':<br><blockquote>'.$data['body']."</blockquote>");
				else
				{
					$new_data['body'] = usam_get_manager_signature_email( $data['mailbox_id'] );								
				}				
				if ( !empty($_REQUEST['template']) ) 
				{					
					$style = new USAM_Mail_Styling( $new_data['mailbox_id'] );
					$new_data['body'] = $style->get_message( $new_data['body'] );	
				}
				$id = usam_insert_email( $new_data );
				usam_set_email_object( $id, ['object_id' => self::$records[0], 'object_type' => 'email']);
				usam_update_email( self::$records[0], ['read' => 1]);
				return ['id' => $id, 'form' => 'edit', 'form_name' => 'email', 'm' => $data['mailbox_id']]; 
			break;
			case 'forward':
				$_email = new USAM_Email( self::$records[0] );				
				$data = $_email->get_data();							
			
				$data['body'] = "<br><br><br><br>-----------------------".__('Переданное вам сообщение','usam')."---------------------------<br><br>".
				"From: ".$data['from_email']." [mailto:".$data['from_email']."]".$data['from_name']."<br>
				Sent: ".usam_local_date($data['date_insert'],'l d.m.Y H:i')."<br>
				To: ".$data['to_email']."<br>
				Subject: ".$data['title']."<br><br>		
				".$data['body']."<br><br>
				----------------------------------------------------------------------";
				
				$new_data = ['from_name' => '', 'from_email' => '', 'folder' => 'drafts', 'to_name' => '', 'to_email' => '', 'type' => 'sent_letter', 'body' => $data['body'], 'mailbox_id' => $data['mailbox_id'], 'title' => $data['title']];				
				$new_id = usam_insert_email( $new_data );							
				$attachments = usam_get_email_attachments( self::$records[0] );
				$new_size = 0;
				foreach ( $attachments as $attachment )	
				{	
					usam_add_file_from_files_library( USAM_UPLOAD_DIR.$attachment->file_path, ['object_id' => $new_id, 'title' => $attachment->title, 'type' => $attachment->type]);
				}
				return ['id' => $new_id, 'form' => 'edit', 'form_name' => 'email', 'm' => $data['mailbox_id']];
			break;	
			case 'send_message': 		
				$sent = 0;
				foreach ( self::$records as $id )
				{
					$_email = new USAM_Email( $id );
					if ( $_email->send_mail() )
						$sent++;
				}
				return ['send_email' => $sent]; 
			break;				
			case 'email_print': 		
				require_once( USAM_FILE_PATH . '/admin/includes/mail/print.php' );
			break;	
			case 'download':		
				usam_create_system_process( __("Загрузить письма", "usam" ), 1, 'download_email_pop3_server', 10, 'download_email_pop3_server' );
				return array( 'add_event' => 1 );
			break;		
			case 'download_all': 	
				$email_attachments = usam_get_email_attachments( self::$records[0] );	
				if ( !empty($email_attachments) )
				{ 
					$zip = new ZipArchive();		
					$file_name = "email_attachments.zip";
					$file_path = USAM_FILE_DIR.$file_name;
					if ( $zip->open($file_path, ZIPARCHIVE::CREATE) === true ) 
					{ 
						foreach ( $email_attachments as $file ) 
						{ 
							if (file_exists(USAM_UPLOAD_DIR.$file->file_path))
								$zip->addFile( USAM_UPLOAD_DIR.$file->file_path, basename(USAM_UPLOAD_DIR.$file->file_path) );
						}
						$zip->close();
						if (file_exists($file_path))
						{				
							usam_download_file( $file_path, $file_name );	
							unlink( $file_path );
							exit();
						}
					}
				}
				return array( 'ready' => 0 ); 
			break;					
		}						
	}			
	
	private static function controller_order_status() 
	{
		$user_id = get_current_user_id(); 
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{	
				case 'delete':			
					foreach ( self::$records as $id )	
						 usam_delete_object_status( $id );				
					return ['deleted' => count(self::$records)]; 
				break;			
			}
		}
	}	

	private static function controller_contact_status() 
	{
		$user_id = get_current_user_id(); 
		if ( current_user_can('setting_crm') )
		{	
			switch( self::$action )
			{	
				case 'delete':			
					foreach ( self::$records as $id )	
						 usam_delete_object_status( $id );				
					return ['deleted' => count(self::$records)]; 
				break;			
			}
		}
	}	
	
	private static function controller_company_status() 
	{
		$user_id = get_current_user_id(); 
		if ( current_user_can('setting_crm') )
		{	
			switch( self::$action )
			{	
				case 'delete':			
					foreach ( self::$records as $id )	
						 usam_delete_object_status( $id );				
					return ['deleted' => count(self::$records)]; 
				break;			
			}
		}
	}
	
	private static function controller_lead_status() 
	{
		$user_id = get_current_user_id(); 
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{	
				case 'delete':			
					foreach ( self::$records as $id )	
						 usam_delete_object_status( $id );				
					return array( 'deleted' => count(self::$records) ); 
				break;			
			}	
		}
	}	
	
	private static function controller_contacting_status() 
	{
		if ( usam_check_current_user_role('administrator') ) 
		{	
			switch( self::$action )
			{	
				case 'delete':			
					foreach ( self::$records as $id )	
						 usam_delete_object_status( $id );				
					return array( 'deleted' => count(self::$records) ); 
				break;			
			}	
		}
	}	
	
	private static function controller_orders() 
	{			 
		switch( self::$action )
		{	
			case 'delete':		
				if ( current_user_can('delete_order') )
				{
					usam_delete_orders(['include' => self::$records]);									
					return ['deleted' => count(self::$records)]; 
				}
				else
					return ['deleted' => 0]; 
			break;	
			case 'copy': 
				if ( current_user_can('add_order') )
				{
					$new_order_id = usam_order_copy( self::$records[0], ['source' => 'manager', 'status' => 'job_dispatched']);
					return ['form' => 'view', 'form_name' => 'order', 'id' => $new_order_id];	
				}
			break;	
			case 'coordinates':	
				$i = 0;								
				$orders = usam_get_orders(['meta_query' => [['key' => 'latitude', 'compare' => 'NOT EXISTS']], 'include' => self::$records]);			
				foreach( $orders as $order )
				{
					if ( usam_check_document_access( $order, 'order', 'edit' ) )
					{
						$property_types = usam_get_order_property_types( $order->id );	
						if ( !empty($property_types['delivery_address']) ) 
						{ 
							$coordinates = apply_filters( 'usam_upload_coordinates', [], $property_types['delivery_address']['full_address'] );	
							if ( !empty($coordinates) )
							{
								usam_update_order_metadata( $order->id, 'latitude', $coordinates['geo_lat'] );
								usam_update_order_metadata( $order->id, 'longitude', $coordinates['geo_lon'] );
								$i++;
							}											
						}
					}
				}
				return ['updated' => $i]; 					
			break;			
			case 'new_company':
				if ( current_user_can('add_order') )
				{
					$customer_id = absint($_REQUEST['id']);
					$user_id = get_current_user_id(); 
					$type_price = usam_get_manager_type_price();					
					$payers = usam_get_group_payers(['type' => 'company']);
					$type_payer = !empty($payers)?$payers[0]['id']:0;
					$args =  ['totalprice' => 0, 'type_payer' => $type_payer, 'type_price' => $type_price, 'source' => 'manager', 'company_id' => $customer_id, 'manager_id' => $user_id];
					$company = usam_get_company( $customer_id );
					$metas = usam_get_company_metas( $customer_id );	
					$args['user_ID'] = !empty($company)?$company['user_id']:0;						
					$order_id = usam_insert_order( $args );
					
					$new_customer_data = usam_get_webform_data_from_CRM( $metas, 'order',  $payers[0]['id'] );	
					usam_add_order_customerdata( $order_id, $new_customer_data );						
					return ['form' => 'edit', 'form_name' => 'order', 'id' => $order_id];
				}
			break;
			case 'new_contact':
				if ( current_user_can('add_order') )
				{	
					$customer_id = absint($_REQUEST['id']);
					$user_id = get_current_user_id(); 
					$type_price = usam_get_manager_type_price();
					$payers = usam_get_group_payers(['type' => 'contact']);
					$type_payer = !empty($payers)?$payers[0]['id']:0;			
					$args =  ['totalprice' => 0, 'type_payer' => $type_payer, 'type_price' => $type_price, 'source' => 'manager', 'contact_id' => $customer_id, 'manager_id' => $user_id];
					$contact = usam_get_contact( $customer_id );
					$metas = usam_get_contact_metas( $customer_id );	
					$args['user_ID'] = !empty($contact)?$contact['user_id']:0;						
					$order_id = usam_insert_order( $args );
					
					$new_customer_data = usam_get_webform_data_from_CRM( $metas, 'order',  $payers[0]['id'] );	
					usam_add_order_customerdata( $order_id, $new_customer_data );		
					return ['form' => 'edit', 'form_name' => 'order', 'id' => $order_id];
				}
			break;
			case 'new':					
				if ( current_user_can('add_order') )
				{
					$user_id = get_current_user_id(); 
					$type_price = usam_get_manager_type_price();					
					$types_payers = usam_get_group_payers();	
					$type_payer = !empty($payers)?$payers[0]['id']:0;	
					$args = ['totalprice' => 0, 'type_payer' => $type_payer, 'user_ID' => 0, 'type_price' => $type_price, 'source' => 'manager', 'manager_id' => $user_id];
					$id = usam_insert_order( $args );	
					return ['form' => 'edit', 'form_name' => 'order', 'id' => $id];
				}
			break;		
			case 'recalculate_order': 	// Пересчитать заказ					
				$orders = usam_get_orders(['meta_query' => [['key' => 'latitude', 'compare' => 'NOT EXISTS']], 'include' => self::$records, 'cache_results' => true]);			
				foreach( $orders as $order )
				{
					if ( usam_check_document_access( $order, 'order', 'edit' ) )
					{
						if ( !usam_check_object_is_completed( $order->status, 'order' ) )
						{
							$cart = new USAM_CART();
							$cart->set_order( $order->id );	
						}
					}
				}
				return ['ready' => 1, 'reload' => true ];
			break;		
			case 'motify_order_status_mail': 		
				if ( current_user_can('send_email') )	
				{	
					foreach ( self::$records as $order_id )
					{
						$notification = new USAM_Сustomer_Notification_Change_Order_Status( $order_id );			
						$email_sent = $notification->send_mail();
					}
					return ['send_email' => $email_sent]; 
				}
			break;			
			case 'motify_order_status_sms': 		
				if ( current_user_can('send_sms') )	
				{
					foreach ( self::$records as $order_id )
					{
						$notification = new USAM_Сustomer_Notification_Change_Order_Status( $order_id );	
						$sms_sent = $notification->send_sms();	
					}
					return ['send_email' => $sms_sent]; 
				}
			break;
			case 'order_return': 
				if ( current_user_can('add_buyer_refund') && self::$records )	
				{	
					$orders = usam_get_orders(['include' => self::$records]);
					foreach ( $orders as $order )
					{
						$products = usam_get_products_order( $order->id ); 
						$args = ['type' => 'buyer_refund', 'type_price' => $order->type_price, 'bank_account_id' => $order->bank_account_id];
						if ( !empty($order->company_id) )
						{
							$args['customer_type'] = 'company';
							$args['customer_id'] = $order->company_id;
						}
						else
						{
							$args['customer_id'] = $order->contact_id;	
							$args['customer_type'] = 'contact';						
						}
						$id = usam_insert_document($args, $products, [], [['document_id' => $order->id, 'document_type' => 'order']]);	
					}						
					if ( count($orders) )
						return ['form_name' => 'buyer_refund', 'form' => 'edit', 'id' => $id];
					return ['ready' => 1]; 
				}
				return ['ready' => 0]; 
			break;	
				// Выгрузить чеки на FTP
			case 'export_order_ftp':						
				if ( current_user_can('export_order') )
				{
					foreach ( self::$records as $order_id )
					{
						$exchange_FTP = new USAM_Exchange_FTP( );
						$result = $exchange_FTP->export_order( $order_id );
					}
					$result = ['ready' => $result]; 
					$errors = $exchange_FTP->get_errors();
					if ( $errors )
						$result['errors'] = $errors;								
					return $result;
				}
			break;				
		}	
	}	
	
	private static function controller_payment() 
	{
		global $wpdb;
		switch( self::$action )
		{	
			case 'delete': 
				if ( current_user_can('delete_payment') )
				{ 					
					if ( self::$records )
						usam_delete_payments(['include' => self::$records]); 		
					return ['deleted' => count(self::$records)]; 
				}
			break;	
			default:						
				if ( !empty(self::$records) && current_user_can('edit_status_payment') )
				{	
					if (strpos(self::$action, 'status') !== false) 
					{
						$str = explode("-",self::$action);
						$object_status = usam_get_object_status( absint($str[1]) );	 
						if ( !empty($object_status) ) 
						{			
							usam_update_object_count_status( false );
							require_once( USAM_FILE_PATH . '/includes/document/payments_query.class.php' );	
							$documents = usam_get_payments(['include' => self::$records, 'cache_results' => true]);						
							$i = 0;
							foreach ( $documents as $document )
							{		
								if ( usam_update_payment_document($document->id, ['status' => $object_status['internalname']]) )
									$i++;
							}	
							usam_update_object_count_status( true );
							return ['updated' => $i];
						}					
					}
				}					
			break;
		}	
	}	
	
	private static function controller_leads() 
	{
		require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
		switch( self::$action )
		{	
			case 'delete':	 
				if ( current_user_can('delete_lead') )
				{ 					
					usam_delete_leads(['include' => self::$records]);				
					return ['deleted' => count(self::$records)]; 
				}
			break;	
			case 'copy': 
				if ( current_user_can('add_lead') )
				{
					$new_order_id = usam_order_copy( self::$records[0], ['source' => 'manager', 'status' => 'job_dispatched']);
					return ['form' => 'view', 'form_name' => 'lead', 'id' => $new_order_id];	
				}
			break;			
			default:
				if ( current_user_can('edit_status_lead') )
				{	
					if (strpos(self::$action, 'status') !== false) 
					{
						$str = explode("-",self::$action);
						$status = usam_get_object_status( absint($str[1]) );	 
						if ( !empty(self::$records) && !empty($status) ) 
						{			
							usam_update_object_count_status( false );
							foreach ( self::$records as $id )
							{
								$result = usam_update_lead( $id, ['status' => $status['internalname']]);
							}
							usam_update_object_count_status( true );
							return ['updated' => count( self::$records )]; 
						}					
					}
				}				
			break;				
		}	
	}	
		
	private static function controller_purchase_rules() 
	{
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{	
				case 'delete':							
					usam_delete_data(self::$records, 'usam_purchase_rules');							
					return array( 'deleted' => count(self::$records) ); 
				break;			
			}	
		}
	}	
	
	private static function controller_types_payers() 
	{
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{	
				case 'delete':							
					usam_delete_data(self::$records, 'usam_types_payers', false );					
					return array( 'deleted' => count(self::$records) ); 
				break;			
			}	
		}
	}	
	
	private static function controller_order_property_groups() 
	{
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{	
				case 'delete':							
					foreach ( self::$records as $id )
					{				
						usam_delete_property_group( $id );	
					}				
					return array( 'deleted' => count(self::$records) ); 
				break;			
			}	
		}
	}
	
	private static function controller_shipping() 
	{
		global $wpdb;
		switch( self::$action )
		{	
			case 'delete':
				if ( current_user_can('setting_document') )
				{
					$services = usam_get_delivery_services(['include' => self::$records, 'active' => 'all']);
					$handlers = [];
					foreach ( $services as $service )
						$handlers[] = $service->handler;
						
					$services = usam_get_delivery_services(['fields' => 'handler', 'handler' => $handlers, 'exclude' => self::$records, 'active' => 'all', 'delivery_option' => 1]);
					$handlers = array_diff($handlers, $services);
					if ( $handlers )
						usam_delete_storages(['fields' => 'id', 'owner' => $handlers, 'active' => 'all']);
					$in = implode( ', ', self::$records );
					$wpdb->query("DELETE FROM ".USAM_TABLE_DELIVERY_SERVICE_META." WHERE delivery_id IN ($in)");
					$wpdb->query( "DELETE FROM ".USAM_TABLE_DELIVERY_SERVICE." WHERE id IN ($in)" );				
					return ['deleted' => count(self::$records)]; 
				}
			break;									
			case 'copy':	
				$service = usam_get_delivery_service( self::$records[0] );				
				$id = usam_insert_delivery_service( $service );
				$metadata = usam_get_delivery_service_metadata( self::$records[0] ); 	
				foreach ( $metadata as $meta ) 
				{
					usam_add_delivery_service_metadata($id, $meta->meta_key, $meta->meta_value );
				}
				return ['id' => $id, 'form' => 'edit', 'form_name' => 'shipping'];
			break;
			case 'activate':
				if ( current_user_can('setting_document') )
				{
					foreach ( self::$records as $id )
					{								
						usam_update_delivery_service($id, ['active' => 1]);
					}
					return ['ready' => 1]; 
				}
			break;		
			case 'delete_storage':	
				$services = usam_get_delivery_services(['include' => self::$records, 'active' => 'all']);
				$handler = [];
				foreach ( $services as $service )
					$handler[] = $service->handler;
				if ( $handler )
					usam_delete_storages(['fields' => 'id', 'owner' => $handler, 'active' => 'all']);
				return ['ready' => 1]; 
			break;	
			case 'deactivate':
				if ( current_user_can('setting_document') )
				{
					foreach ( self::$records as $id )
					{								
						usam_update_delivery_service($id, ['active' => 0]);
					}
					return array( 'ready' => 1 ); 
				}
			break;		
		}
	}	
	
	private static function controller_view_grouping() 
	{
		global $wpdb;
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{	
				case 'delete':
					usam_delete_data(self::$records, 'usam_order_view_grouping');					
					return array( 'deleted' => count(self::$records) ); 
				break;		
			}	
		}
	}
	
	private static function controller_payment_gateway() 
	{
		global $wpdb;
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{	
				case 'delete':
					$in = implode( ', ', self::$records );					
					$wpdb->query("DELETE FROM ".USAM_TABLE_PAYMENT_GATEWAY_META." WHERE payment_id IN ($in)");	
					$wpdb->query( "DELETE FROM ".USAM_TABLE_PAYMENT_GATEWAY." WHERE id IN ($in)" );
					return array( 'deleted' => count(self::$records) ); 
				break;
				case 'activate':
					foreach ( self::$records as $id )
					{								
						usam_update_payment_gateway($id, ['active' => 1]);
					}
					return array( 'ready' => 1 ); 
				break;
				case 'deactivate':	
					foreach ( self::$records as $id )
					{								
						usam_update_payment_gateway($id, ['active' => 0]);
					}		
					return array( 'ready' => 1 ); 					
				break;		
				case 'copy':
					$insert = usam_get_payment_gateway( self::$records[0] );
					$id = usam_insert_payment_gateway( $insert );
					$metadata = usam_get_payment_gateway_metadata( self::$records[0] ); 	
					foreach ( $metadata as $meta ) 
					{
						usam_update_payment_gateway_metadata($id, $meta->meta_key, $meta->meta_value );
					}
					return ['form' => 'edit', 'form_name' => 'payment_gateway', 'id' => $id];				
				break;					
			}	
		}
	}
	
	private static function controller_orders_export() 
	{		
		return self::actions_export( 'order_export' );
	}
	
	private static function controller_product_importer() 
	{
		return self::actions_import('product_import');
	}
	
	private static function controller_order_import() 
	{
		return self::actions_import('order_import');
	}
	
	private static function controller_contact_import() 
	{
		return self::actions_import('contact_import');
	}
	
	private static function controller_company_import() 
	{
		return self::actions_import('company_import');
	}
	
	private static function controller_mailboxes() 
	{		
		switch( self::$action )
		{	
			case 'delete':
				foreach ( self::$records as $id )
				{				
					usam_delete_mailbox( $id );		
				}	
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
	}
	
	private static function controller_email_filters() 
	{		
		switch( self::$action )
		{	
			case 'delete':		
				require_once( USAM_FILE_PATH . '/includes/mailings/email_filter.class.php' );
				foreach ( self::$records as $id )
				{				
					usam_delete_email_filter( $id );						
				}					
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
	}
	
	private static function controller_signatures() 
	{		
		switch( self::$action )
		{	
			case 'delete':
				global $wpdb;
				$folder = !empty($_REQUEST['f']) ? sanitize_title($_REQUEST['f']):'inbox';
				$mailbox_id = !empty($_REQUEST['m']) ? absint($_REQUEST['m']):0;
				$in = implode( ', ', self::$records );
				$wpdb->query( "DELETE FROM ".USAM_TABLE_SIGNATURES." WHERE id IN ($in)" );
				return array( 'deleted' => count(self::$records), 'f' => $folder, 'm' => $mailbox_id ); 
			break;	
		}	
	}
	
	private static function controller_taxes() 
	{		
		switch( self::$action )
		{	
			case 'delete':				
				global $wpdb;
				$in = implode( ',', self::$records );	
				$result = $wpdb->query("DELETE FROM ".USAM_TABLE_TAXES." WHERE id IN ($in)");
				$i = $result >= 1 ? count(self::$records) : 0;
				return ['deleted' => $i]; 
			break;	
			case 'activate':					
				$i = 0;
				foreach ( self::$records as $id )	
				{
					$_tax = new USAM_Tax( $id );
					if ( $_tax->set(['active' => 1]) )
						$i++;
					$_tax->save( );
				}
				return ['updated' => $i]; 
			break;
			case 'deactivate':	
				$i = 0;				
				foreach ( self::$records as $id )	
				{
					$_tax = new USAM_Tax( $id );
					if ( $_tax->set(['active' => 0]) )
						$i++;
					$_tax->save( );
				}
				return ['updated' => $i]; 
			break;
		}	
	}
	
	private static function controller_underprice() 
	{		
		switch( self::$action )
		{	
			case 'delete':				
				usam_delete_data( self::$records, 'usam_underprice_rules' );	
				usam_recalculate_price_products();						
				return array( 'deleted' => self::$records ); 
			break;	
		}	
	}	
	
	private static function controller_sliders() 
	{		
		switch( self::$action )
		{	
			case 'delete':				
				global $wpdb;
				$in = implode( ',', self::$records );					
				$wpdb->query("DELETE FROM ".USAM_TABLE_SLIDES." WHERE slider_id IN ($in)");
				$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SLIDER." WHERE id IN ($in)"); 
				$i = $result >= 1 ? count(self::$records) : 0;
				return array( 'deleted' => $i ); 
			break;	
			case 'activate':					
				foreach ( self::$records as $id )	
				{
					usam_update_slider( $id, array('active' => 1) );	
				}
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'deactivate':	
				foreach ( self::$records as $id )	
				{
					usam_update_slider( $id, array('active' => 0) );		
				}
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'copy':							
				$new = usam_get_slider( self::$records[0] );
				$id = usam_insert_slider( $new );	
				return ['form' => 'edit', 'form_name' => 'slider', 'id' => $id];
			break;
		}	
	}
	
	private static function controller_sites() 
	{		
		switch( self::$action )
		{	
			case 'delete':				
				global $wpdb;
				$in = implode( ',', self::$records );					
				$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SITES." WHERE id IN ($in)"); 
				$i = $result >= 1 ? count(self::$records) : 0;
				return array( 'deleted' => $i ); 
			break;	
		}	
	}
	
	private static function controller_keywords() 
	{		
		require_once( USAM_FILE_PATH .'/includes/seo/keyword.class.php' );		
		if ( !current_user_can('view_seo_setting') )
			return ['ready' => 0];	
		switch( self::$action )
		{	
			case 'delete':				
				global $wpdb;
				$in = implode( ',', self::$records );					
				$wpdb->query( "DELETE FROM ".USAM_TABLE_STATISTICS_KEYWORDS." WHERE keyword_id IN ('$in')" );
				$result = $wpdb->query( "DELETE FROM ".USAM_TABLE_KEYWORDS." WHERE id IN ('$in')" );	
				$i = $result >= 1 ? count(self::$records) : 0;
				return ['deleted' => $i]; 
			break;	
			case 'add_keywords':
				if ( !empty($_POST['keywords']) )
				{			
					$keywords = sanitize_textarea_field(stripslashes($_POST['keywords']));					
					$keywords = explode("\n", $keywords);					
					foreach($keywords as $keyword)					
					{ 
						if ( !empty($keyword) )
							usam_insert_keyword(['keyword' => $keyword, 'check' => 1]);
					}
				}	
				return ['ready' => 1];				
			break;	
			case 'analyze':
				$ids = array_map( 'intval', self::$records );			
				require_once( USAM_FILE_PATH . '/includes/seo/keywords_query.class.php' );	
				$keywords = usam_get_keywords(['include' => $ids]);					
				require_once( USAM_FILE_PATH . '/includes/seo/seo-analysis.class.php' );	
				require_once( USAM_FILE_PATH . '/includes/parser/parser.function.php' );
				 
				foreach( $keywords as $keyword )
				{					
					if ( $keyword->link == '' )
						continue;
					$URL_OBJ = usam_get_url_object( $keyword->link );
					if( $URL_OBJ )
					{ 	
						$content = preg_replace('#(<script.*</script>)#sU', '', $URL_OBJ['content']);
						$content = preg_replace('#(<style.*</style>)#sU', '', $content);
						$content = mb_strtolower($content);
						$count = substr_count($content, $keyword->keyword); 
						
						usam_update_keyword( $keyword->id, ['count' => $count]);	
					}
				}
			break;				
			case 'check':					
				foreach( self::$records as $id )
				{					
					usam_update_keyword( $id, ['check' => 1]);	
				}
				return ['updated' => count(self::$records)];
			break;	
			case 'do_not_check':	
				foreach( self::$records as $id )
				{					
					usam_update_keyword( $id, array('check' => 0) );	
				}
				return array( 'updated' => count(self::$records) ); 
			break;			
			case 'importance':					
				foreach( self::$records as $id )
				{					
					usam_update_keyword( $id, array('importance' => 1) );	
				}
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'do_not_importance':	
				foreach( self::$records as $id )
				{					
					usam_update_keyword( $id, array('importance' => 0) );							
				}
				return array( 'updated' => count(self::$records) ); 
			break;			
		}	
	}
	
	private static function controller_basket() 
	{		
		switch( self::$action )
		{	
			case 'delete':				
				foreach ( self::$records as $id )
				{
					usam_delete_discount_rule( $id );					
				}				
				return array( 'deleted' => count(self::$records) ); 
			break;	
			case 'copy':				
				$id = usam_copy_discount_rule( self::$records[0] );
				return array( 'form' => 'edit','form_name' => 'basket_discount','id' => $id ); 
			break;
			case 'activate':						
				$new_rule['active'] = 1;  				
				foreach ( self::$records as $id )
				{								
					usam_update_discount_rule( $id, $new_rule);							
				}				
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'deactivate':				
				$new_rule['active'] = 0;		
				foreach ( self::$records as $id )
				{								
					usam_update_discount_rule( $id, $new_rule);							
				}					
				return array( 'updated' => count(self::$records) ); 				
			break;						
		}	
	}
	
	private static function controller_discount() 
	{		
		switch( self::$action )
		{	
			case 'delete':						
				$product_ids = usam_get_product_discount_ids(  self::$records );	
				foreach ( self::$records as $id )
				{
					usam_delete_discount_rule( $id );					
				}				
				if ( !empty($product_ids) )			
					usam_recalculate_price_products( array('post__in' => $product_ids) );
				
				return array( 'deleted' => count(self::$records) ); 		
			break;
			case 'copy':		
				$id = usam_copy_discount_rule( self::$records[0] );
				return array( 'form' => 'edit','form_name' => 'product_discount','id' => $id ); 
			break;	
			case 'activate':						
				$new_rule['active'] = 1;  
				$rules = usam_get_discount_rules(['include' =>  self::$records]);
				$recalculate = false;
				$current_time = time();	
				foreach ( $rules as $rule )
				{				
					if ( $rule->active && ( empty($rule->start_date) || strtotime($rule->start_date) <= $current_time )	)			
						$recalculate = true;
					usam_update_discount_rule( $rule->id, $new_rule);				
				}		
				if ( $recalculate )
					usam_recalculate_price_products();					
				return array( 'updated' => count(self::$records) ); 	
			break;	
			case 'deactivate':				
				$new_rule['active'] = 0;		
				foreach (  self::$records as $id )
				{
					usam_update_discount_rule( $id, $new_rule);				
				}	
				$product_ids = usam_get_product_discount_ids(  self::$records );	
				if ( !empty($product_ids) )			
					usam_recalculate_price_products( array('post__in' => $product_ids) );	
				
				return ['updated' => count(self::$records)]; 			
			break;
		}	
	}
	
	private static function controller_fix_price() 
	{		
		switch( self::$action )
		{	
			case 'delete':						
				$ids = [];
				foreach ( self::$records as $id )
				{						
					$product_ids = usam_get_products(['fields' => 'ids', 'price_meta_query' => [['key' => 'fix_price_'.$id, 'compare' => "EXISTS"]], 'update_post_term_cache' => false, 'stocks_cache' => false, 'prices_cache' => false]);		
					$ids = array_merge( $ids, $product_ids );
					usam_delete_discount_rule( $id );
				}					
				if ( $ids )
					usam_recalculate_price_products(['post__in' => $ids]);				
				return ['deleted' => count(self::$records)]; 		
			break;
			case 'copy':		
				$id = usam_copy_discount_rule( self::$records[0] );
				return array( 'form' => 'edit','form_name' => 'product_discount','id' => $id ); 
			break;	
			case 'activate':						
				$new_rule['active'] = 1;  
				$rules = usam_get_discount_rules( array( 'include' =>  self::$records ) );
				$current_time = time();	
				foreach ( $rules as $rule )
				{				
					if ( $rule->active && ( empty($rule->start_date) || strtotime($rule->start_date) <= $current_time )	)			
						usam_recalculate_price_products(['price_meta_query' => [['key' => 'fix_price_'.$rule->id, 'compare' => "EXISTS"]]]);
					usam_update_discount_rule( $rule->id, $new_rule);				
				}					
				return ['updated' => count(self::$records)]; 	
			break;	
			case 'deactivate':				
				$new_rule['active'] = 0;		
				foreach (  self::$records as $id )
				{
					if ( usam_update_discount_rule( $id, $new_rule) )
						usam_recalculate_price_products(['price_meta_query' => [['key' => 'fix_price_'.$id, 'compare' => "EXISTS"]]]);						
				}					
				return ['updated' => count(self::$records)]; 			
			break;
		}	
	}
	
	private static function controller_storage() 
	{		
		switch( self::$action )
		{							
			case 'delete':				
				$result = usam_delete_storages(['include' => self::$records]);
				$i = $result >= 1 ? count(self::$records) : 0;
				return ['deleted' => $i]; 
			break;		
			case 'activate':						
				foreach ( self::$records as $id )
				{				
					usam_update_storage( $id, ['active' => 1]);						
				}
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'deactivate':				
				foreach ( self::$records as $id )
				{				
					usam_update_storage( $id, array('active' => 0) );			
				}	
				return array( 'updated' => count(self::$records) ); 					
			break;	
			case 'activate_issuing':						
				foreach ( self::$records as $id )
				{				
					usam_update_storage( $id, array('issuing' => 1) );						
				}
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'deactivate_issuing':				
				foreach ( self::$records as $id )
				{				
					usam_update_storage( $id, array('issuing' => 0) );			
				}	
				return array( 'updated' => count(self::$records) ); 					
			break;	
			case 'activate_shipping':						
				foreach ( self::$records as $id )
				{				
					usam_update_storage( $id, array('shipping' => 1) );						
				}
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'deactivate_shipping':				
				foreach ( self::$records as $id )
				{				
					usam_update_storage( $id, array('shipping' => 0) );			
				}	
				return array( 'updated' => count(self::$records) ); 					
			break;			
		}	
	}
	
	private static function controller_telephony() 
	{		
		switch( self::$action )
		{	
			case 'delete':						
				require_once( USAM_FILE_PATH . '/includes/crm/call.class.php' );
				foreach ( self::$records as $id )
				{
					usam_delete_call( $id );					
				}								
				return array( 'deleted' => count(self::$records) ); 		
			break;
			case 'download':				
				$attachments = usam_get_files( array( 'object_id' => self::$records[0], 'type' => 'telephony' ) );				
				if ( !empty($attachments) )
				{
					$zip = new ZipArchive();		
					$file_name = __('Запись','usam').".zip";
					$file_path = USAM_FILE_DIR.$file_name;
					if ( $zip->open($file_path, ZIPARCHIVE::CREATE) === true ) 
					{
						foreach ( $attachments as $file ) 
						{ 
							if (file_exists(USAM_UPLOAD_DIR.$file->file_path))
								$zip->addFile( USAM_UPLOAD_DIR.$file->file_path, basename(USAM_UPLOAD_DIR.$file->file_path) );
						} 
						$zip->close();		
						if (file_exists($file_path))
						{				
							usam_download_file( $file_path, $file_name );	
							unlink( $file_path );
							exit();
						}
					}
				}			
			break;						
		}	
	}
	
	private static function controller_chat() 
	{		
		switch( self::$action )
		{	
			case 'delete':						
				if ( !empty(self::$records) )
					usam_delete_dialogs(['include' => self::$records]);
				return ['deleted' => count(self::$records)]; 		
			break;				
		}	
	}
	
	private static function controller_messengers() 
	{		
		switch( self::$action )
		{	
			case 'delete':							
				foreach ( self::$records as $id )
					usam_delete_social_network_profile( $id );	
				return array( 'deleted' => count(self::$records) ); 		
			break;	
		}	
	}
	
	private static function controller_chat_bot_commands() 
	{		
		switch( self::$action )
		{	
			case 'delete':							
				$command = usam_get_chat_bot_command( self::$records[0] );
				foreach ( self::$records as $id )
					usam_delete_chat_bot_command( $id );	
				return array( 'deleted' => count(self::$records), 'n' => $command['template_id'] ); 		
			break;				
		}	
	}
	
	private static function controller_chat_bots() 
	{		
		switch( self::$action )
		{	
			case 'delete':							
				foreach ( self::$records as $id )
					usam_delete_сhat_bot_template( $id );	
				return array( 'deleted' => count(self::$records) ); 		
			break;	
		}	
	}
	
	private static function controller_advertising_campaigns() 
	{		
		switch( self::$action )
		{	
			case 'delete':							
				foreach ( self::$records as $id )
					usam_delete_advertising_campaign( $id );	
				return array( 'deleted' => count(self::$records) ); 		
			break;	
			case 'copy':							
				$advertising_campaign = usam_get_advertising_campaign( self::$records[0] );
				$advertising_campaign['transitions'] = 0;
				$advertising_campaign['date_insert'] = date( "Y-m-d H:i:s" );	
				$id = usam_insert_advertising_campaign( $advertising_campaign );	
				return ['form' => 'edit', 'form_name' => 'advertising_campaign', 'id' => $id];
			break;
		}	
	}
	
	private static function controller_lists() 
	{		
		switch( self::$action )
		{	
			case 'delete':							
				global $wpdb;
				$wpdb->query( "DELETE FROM ".USAM_TABLE_SUBSCRIBER_LISTS." WHERE list IN (".implode(',',self::$records).")");
				$wpdb->query( "DELETE FROM ".USAM_TABLE_MAILING_LISTS." WHERE id IN (".implode(',',self::$records).")");
				return array( 'deleted' => count(self::$records) ); 		
			break;	
			case 'cleaning':							
				global $wpdb;
				$wpdb->query( "DELETE FROM ".USAM_TABLE_SUBSCRIBER_LISTS." WHERE list IN (".implode(',',self::$records).")");
				return array( 'deleted' => count(self::$records) ); 	
			break;
		}	
	}
	
	private static function controller_plan() 
	{		
		require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan.class.php' );		
		switch( self::$action )
		{	
			case 'delete':							
				foreach ( self::$records as $id )
					usam_delete_sales_plan( $id );	
				return array( 'deleted' => count(self::$records) ); 		
			break;	
			case 'copy':					
				$new = usam_get_sales_plan( self::$records[0] );
				$amounts = usam_get_sales_plan_amounts( self::$records[0] );
				$id = usam_insert_sales_plan( $new );						
				foreach( $amounts as $object_id => $price )
				{					
					$amount['plan_id'] = $id;
					$amount['object_id'] = $object_id;
					$amount['price'] = $price;					
					usam_save_sales_plan_amounts( $amount );
				} 		
				return array( 'form' => 'edit', 'form_name' => 'plan', 'id' => $id);				
			break;
		}	
	}
	
	private static function controller_pricelist() 
	{	
		return self::actions_export( 'pricelist' );
	}
	
	private static function controller_price_analysis() 
	{			
		switch( self::$action )
		{	
			case 'delete':							
				global $wpdb;
				$in = implode( ', ', self::$records );
				$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCTS_COMPETITORS." WHERE id IN ($in)" );		
				return array( 'deleted' => count(self::$records) ); 		
			break;				
		}	
	}
	
	private static function controller_product_exporter() 
	{			
		return self::actions_export( 'product_export' );
	}
	
	private static function controller_projects() 
	{			
		return self::actions_events();
	}
	
	private static function controller_rates() 
	{			
		switch( self::$action )
		{	
			case 'delete':		
				require_once( USAM_FILE_PATH .'/includes/directory/currency_rate.class.php' );
				foreach ( self::$records as $id )
				{
					usam_delete_currency_rate( $id );
				}
				return array( 'deleted' => count(self::$records) ); 
			break;				
		}	
	}
	
	private static function controller_trading_platforms() 
	{		
		require_once( USAM_FILE_PATH . '/includes/exchange/feed.class.php');
		switch( self::$action )
		{	
			case 'delete':
				foreach ( self::$records as $id )
					usam_delete_feed( $id ); 			
				return ['deleted' => count(self::$records)];
			break;
			case 'copy':	
				$service = usam_get_feed( self::$records[0] );				
				$id = usam_insert_feed( $service );
				$metadata = usam_get_feed_metadata( self::$records[0] ); 	
				foreach ( $metadata as $meta ) 
				{
					usam_update_feed_metadata($id, $meta->meta_key, $meta->meta_value );
				}
				return ['id' => $id, 'form' => 'edit', 'form_name' => 'trading_platform'];
			break;
		}	
	}
	
	private static function controller_subscriptions() 
	{		
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'edit_subscription' ) )	
		{
			require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php'  );
			switch( self::$action )
			{	
				case 'delete':							
					foreach ( self::$records as $id )
						usam_delete_subscription( $id ); 			
					return ['deleted' => count(self::$records)];		
				break;	
			}	
		}
	}
	
	private static function controller_subscription_renewal() 
	{		
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'edit_subscription' ) )	
		{
			require_once(USAM_FILE_PATH . '/includes/document/subscription_renewal.class.php');
			switch( self::$action )
			{	
				case 'delete':							
					foreach ( self::$records as $id )
						usam_delete_subscription_renewal( $id ); 			
					return ['deleted' => count(self::$records)];		
				break;	
			}	
		}
	}
	
	private static function controller_delivery_documents() 
	{		
		return self::controller_shipping_documents( );
	}	
	
	private static function controller_shipping_documents() 
	{		
		switch( self::$action )
		{	
			case 'delete':							
				if ( !current_user_can('delete_shipped') )
					return ['access' => 1];				
				usam_update_object_count_status( false );
				foreach( self::$records as $document_id )
				{
					$shipped = new USAM_Shipped_Document( $document_id );
					$shipped->remove_all_product_from_reserve();
					wp_cache_delete( $document_id, 'usam_document_shipped' );	
					usam_delete_shipped_document( $document_id );						
				}
				usam_update_object_count_status( true );				
				return ['deleted' => count(self::$records)];
			break;				
			case 'transfer_transport_company':
				if ( !current_user_can('export_shipped') )
					return ['access' => 1];
				$i = 0;
				foreach ( self::$records as $id )
				{		
					$shipped_document = usam_get_shipped_document( $id );
					$merchant_instance = usam_get_shipping_class( $shipped_document['method'] );
					if ( $merchant_instance->create_order( $id ) )
						$i++;
				}
				return ['ready' => $i]; 				
			break;		
			case 'courier':
				if ( !current_user_can('edit_shipped') )
					return ['access' => 1];
				$courier = absint($_POST['courier']);
				$i = 0;
				foreach ( self::$records as $id )
				{			
					if ( usam_update_shipped_document( $id, ['courier' => $courier]) )					
						$i++;
				}				
				return ['ready' => $i]; 				
			break;				
			default: 
				if ( !current_user_can('edit_shipped') )
					return ['access' => 1];
				if ( strpos(self::$action, 'status') !== false) 
				{
					$str = explode("-",self::$action);			
					if ( usam_get_object_status_by_code($str[1], 'shipped') ) 
					{			
						usam_update_object_count_status( false );
						foreach ( self::$records as $id )
						{
							$result = usam_update_shipped_document( $id, ['status' => $str[1]]);
						}
						usam_update_object_count_status( true );
						return ['updated' => count(self::$records)]; 	
					}					
				}
			break;		
		}	
	}
		
	private static function controller_webforms() 
	{		
		require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
		switch( self::$action )
		{	
			case 'delete':
				foreach ( self::$records as $key => $id )
				{
					usam_delete_webform( $id );	
				}
				return ['deleted' => count(self::$records)]; 
			break;		
			case 'copy':
				$webform = usam_get_webform( self::$records[0] );
				$id = usam_insert_webform( $webform );				
				return ['form' => 'edit', 'form_name' => 'webform', 'id' => $id];
			break;
			case 'activate':						
				$data['active'] = 1;  
				foreach ( self::$records as $id )
				{				
					usam_update_webform( $id, $data );						
				}
				return array( 'updated' => count(self::$records) ); 
			break;	
			case 'deactivate':				
				$data['active'] = 0;		
				foreach ( self::$records as $id )
				{				
					usam_update_webform( $id, $data );			
				}	
				return array( 'updated' => count(self::$records) ); 					
			break;		
		}	
	}
	
	private static function controller_webform_property_groups() 
	{	
		switch( self::$action )
		{	
			case 'delete':
				foreach ( self::$records as $key => $id )
				{
					usam_delete_property_group($id);							
				}
				return array( 'deleted' => count(self::$records) ); 
			break;		
		}	
	}
	
	private static function controller_webform_properties() 
	{	
		switch( self::$action )
		{	
			case 'delete':
				foreach ( self::$records as $key => $id )
				{
					usam_delete_property($id);							
				}
				return array( 'deleted' => count(self::$records) ); 
			break;		
		}	
	}	
	
	private static function controller_tasks() 
	{		
		return self::actions_events();
	}
	
	private static function controller_affairs() 
	{		
		return self::actions_events();
	}
	
	private static function controller_contacting() 
	{		
		switch( self::$action )
		{	
			case 'delete':				
				$i = 0;
				if ( self::$records && current_user_can('delete_contacting') )
				{					
					require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
					$i = usam_delete_contactings(["include" => self::$records]);
				}				
				return ['deleted' => $i]; 
			break;			
			case 'started':	
			case 'completed':	
				require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
				require_once(USAM_FILE_PATH.'/includes/crm/contactings_query.class.php');
				if( self::$records && current_user_can('delete_contacting') )
				{
					$i = 0;
					$items = usam_get_contactings(['include' => self::$records, 'cache_results' => true]);	
					foreach ( $items as $item )
					{							
						if ( usam_update_contacting( $item->id, ['status' => self::$action] ) )
							$i++;
					}
				}				
				return ['updated' => $i]; 
			break;				
		}
	}	
	
	private static function controller_convocation() 
	{		
		return self::actions_events();
	}
	
	private static function controller_constructor() 
	{		
		require_once( USAM_FILE_PATH . '/admin/includes/filter.class.php' );
		switch( self::$action )
		{	
			case 'delete':
				$i = 0;
				foreach ( self::$records as $key => $id )
				{			
					$filter = new USAM_Filter( $id );			
					if ( $filter->delete() )
						$i++;
				}						
				return array( 'deleted' => $i ); 
			break;	
		}	
	}
	
	private static function controller_accumulative() 
	{		
		switch( self::$action )
		{	
			case 'delete':						
				usam_delete_data( self::$records, 'usam_accumulative_discount' );								
				return array( 'deleted' => count(self::$records) ); 			
			break;	
			case 'copy':		
				$item = usam_get_data( self::$records[0], 'usam_accumulative_discount' );		
				$item['active'] = 0;	
				$item['date_insert'] = date( 'Y-m-d H:i:s' );	
				$id = usam_add_data( $item, 'usam_accumulative_discount' );
				return array( 'form' => 'edit', 'form_name' => 'accumulative', 'id' => $id);	
			break;	
		}	
	}
	
	private static function controller_certificates() 
	{		
		return self::actions_coupons( );
	}
	
	private static function controller_coupons() 
	{		
		return self::actions_coupons( );
	}
	
	private static function controller_crosssell() 
	{		
		switch( self::$action )
		{	
			case 'delete':			
				usam_delete_data( self::$records, 'usam_crosssell_conditions' );
				return array( 'deleted' => count(self::$records) ); 		
			break;	
			case 'activate':						
				$new_rule['active'] = 1;  
				foreach ( self::$records as $id )
				{				
					usam_edit_data( $new_rule, $id, 'usam_crosssell_conditions' );							
				}
				return array( 'updated' => count(self::$records) ); 	
			break;	
			case 'deactivate':				
				$new_rule['active'] = 0;		
				foreach ( self::$records as $id )
				{				
					usam_edit_data( $new_rule, $id, 'usam_crosssell_conditions' );							
				}	
				return array( 'updated' => count(self::$records) ); 				
			break;	
		}	
	}
	
	private static function controller_publishing_rules() 
	{		
		switch( self::$action )
		{	
			case 'delete':			
				usam_delete_data( self::$records, 'usam_vk_publishing_rules' );
				return array( 'deleted' => count(self::$records) ); 		
			break;
		}	
	}
		
	private static function controller_product_day() 
	{		
		global $wpdb;
		require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
		switch( self::$action )
		{	
			case 'delete':			
				$ids = array_map( 'intval', self::$records );
				usam_delete_data( $ids, 'usam_product_day_rules' );			
				
				$products_ids = usam_get_products_day(['rule_id' => $ids, 'status' =>  array( 1 ), 'fields' => 'product_id']);		
				if ( !empty($products_ids) )
				{
					usam_recalculate_price_products_ids( $products_ids );					
					wp_cache_delete( 'usam_active_products_day' );	
				}
				$result = $wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_DAY." WHERE rule_id IN (".implode(',',$ids).")" );				
				return ['deleted' => count(self::$records)]; 		
			break;	
			case 'change_product_day':
				$pday = new USAM_Work_Product_Day();
				$pday->change_product_day();
				return ['ready' => 1]; 
			break;				
			case 'copy':		
				$item = usam_get_data( self::$records[0], 'usam_product_day_rules' );		
				$item['active'] = 0;	
				$item['date_insert'] = date( 'Y-m-d H:i:s' );	
				$id = usam_add_data( $item, 'usam_product_day_rules' );
				return ['form' => 'edit', 'form_name' => 'product_day', 'id' => $id];
			break;	
		}	
	}
	
	private static function controller_notification() 
	{		
		switch( self::$action )
		{	
			case 'delete':			
				usam_delete_data( self::$records, 'usam_notifications' );		
				return array( 'deleted' => count(self::$records) ); 		
			break;	
		}	
	}
	
	private static function controller_search_engine_location() 
	{		
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'view_seo_setting' ) )			
		{
			switch( self::$action )
			{	
				case 'delete':			
					usam_delete_data( self::$records, 'usam_search_engine_location' );		
					return array( 'deleted' => count(self::$records) ); 		
				break;	
			}	
		}
	}
	
	private static function controller_search_engine_region() 
	{		
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'view_seo_setting' ) )			
		{
			switch( self::$action )
			{	
				case 'delete':			
					global $wpdb;
					$in = implode( ', ', self::$records );	
					$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SEARCH_ENGINE_REGIONS." WHERE id IN ($in)");
					$i = $result ? count(self::$records) : 0;
					return array( 'deleted' => $i ); 		
				break;	
			}	
		}
	}
	
	private static function controller_positions() 
	{		
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'view_seo_setting' ) )			
		{
			switch( self::$action )
			{	
				case 'start': 
					delete_transient( 'usam_start_query_position_site' );
					require_once(USAM_FILE_PATH.'/includes/seo/checking_site_position.class.php');	
					usam_query_position_site();	
					return ['ready' => 1]; 					
				break;	
			}	
		}
	}
	
	private static function controller_reviews() 
	{		
		switch( self::$action )
		{	
			case 'delete':
				$i = 0;
				if ( !empty(self::$records) )
				{
					require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
					$reviews = usam_get_customer_reviews(['fields' => array('id', 'status'), 'include' => self::$records]);
					foreach( $reviews as $review )
					{
						if ( $review->status == 3 )
						{
							$i++;
							usam_delete_review($review->id);
						}
						else
							usam_update_review( $review->id, array('status' => 3) );		
					}
				}
				return array( 'deleted' => $i ); 	
			break;				
			case 'approvereview':
				foreach (  self::$records as $key => $id )
				{					
					usam_update_review( $id, array('status' => 2) );
				}
				return array( 'updated' => count(self::$records) ); 		
			break;
			case 'unapprovereview':				
				foreach (  self::$records as $key => $id )
				{
					usam_update_review( $id, array('status' => 1) );
				}
				return array( 'updated' => count(self::$records) ); 				
			break;				
		}
	}	
		
	private static function controller_parser() 
	{		
		switch( self::$action )
		{	
			case 'delete':
				foreach(self::$records as $id )
				{
					usam_delete_parsing_site( $id );	
				}	
				return ['deleted' => self::$records]; 	
			break;		
			case 'delete_products':	
				$result = false;
				if ( self::$records )
				{
					require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');
					$sites = usam_get_parsing_sites(['include' => self::$records]);		
					$ids = [];
					foreach ( $sites as $site )
					{
						if ( $site->site_type == 'supplier' )
						{
							$args = ['post_status' => 'all', 'post_type' => 'usam-product', 'productmeta_query' => [['key' => 'webspy_link', 'value' => '('.$site->domain.')', 'compare' => 'RLIKE']]];
							$i = usam_get_total_products( $args );	
							if ( $i )
							{							
								if( usam_create_system_process( sprintf(__('Удаление товаров, загруженные прасингом %s','usam'),$site->name), $args, 'delete_post', $i, 'delete_parsing_posts_'.$site->id ) )
									$result = true;
							}
						}
						else
							$ids[] = $site->id;
					}
					if ( $ids )
					{
						global $wpdb;
						$wpdb->query("DELETE FROM ".USAM_TABLE_COMPETITOR_PRODUCT_PRICE." WHERE competitor_product_id IN (".implode(",",$ids).")");
						$wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCTS_COMPETITORS." WHERE site_id IN (".implode(",",$ids).")");		
					}
				}
				return ['add_event' => $result]; 
			break;
			case 'update_products':	
				$i = 0;
				foreach(self::$records as $id )
				{	
					if ( usam_start_parsing_site( $id, 'update' ) )
						$i++;
				}
				return ['add_event' => $i]; 
			break;			
			case 'download':					
				$i = 0;
				foreach(self::$records as $id )
				{			
					if ( usam_start_parsing_site( $id, 'insert' ) )
						$i++;
				}
				return ['add_event' => $i]; 
			break;		
			case 'copy':					
				$i = 0;
				foreach(self::$records as $id )
				{			
					$site = usam_get_parsing_site( $id );				
					$new_id = usam_insert_parsing_site( $site );
					$metadata = usam_get_parsing_site_metadata( $id ); 	
					foreach ( $metadata as $meta ) 
					{
						if ( $meta->meta_key != 'products_added' && $meta->meta_key != 'products_update' && $meta->meta_key != 'count_urls' && $meta->meta_key != 'links_processed' )
							usam_update_parsing_site_metadata($new_id, $meta->meta_key, maybe_unserialize($meta->meta_value) );
					}
				}
				return ['add_event' => $i]; 
			break;				
		}
	}
	
	private static function controller_loyalty_programs() 
	{		
		switch( self::$action )
		{	
			case 'delete':
				usam_delete_data(self::$records, 'usam_bonuses_rules');
				return array( 'deleted' => self::$records ); 	
			break;
			case 'copy':		
				$item = usam_get_data( self::$records[0], 'usam_bonuses_rules' );		
				$item['active'] = 0;	
				$item['date_insert'] = date( 'Y-m-d H:i:s' );	
				$id = usam_add_data( $item, 'usam_bonuses_rules' );
				return array( 'form' => 'edit', 'form_name' => 'rule_bonuses', 'id' => $id);			
			break;				
		}
	}
	
	private static function controller_rules_coupons() 
	{		
		global $wpdb;
		switch( self::$action )
		{	
			case 'delete':				
				foreach(self::$records as $id )
				{
					$item = usam_get_data( $id, 'usam_coupons_roles' );		
					if ( !empty($item['coupon_id']) )
						$result = $wpdb->query("DELETE FROM ".USAM_TABLE_COUPON_CODES." WHERE id = '".$item['coupon_id']."'");	
				}			
				usam_delete_data( self::$records, 'usam_coupons_roles' );						
				return array( 'deleted' => self::$records ); 
			break;
			case 'copy':		
				$item = usam_get_data( self::$records[0], 'usam_coupons_roles' );		
				$item['active'] = 0;	
				$item['date_insert'] = date( 'Y-m-d H:i:s' );	
				$id = usam_add_data( $item, 'usam_coupons_roles' );
				return array( 'form' => 'edit', 'form_name' => 'rule_coupon_'.$item['rule_type'], 'id' => $id);		
			break;			
		}
	}
	
	private static function controller_sms_newsletters() 
	{		
		return self::actions_newsletters( 'sms_newsletter' );
	}
	
	private static function controller_email_newsletters() 
	{		
		return self::actions_newsletters( 'email_newsletter' );
	}
	
	private static function controller_trigger_email_newsletters() 
	{		
		return self::actions_newsletters( 'email_newsletter' );
	}	
	
	private static function controller_departments() 
	{	
		switch( self::$action )
		{	
			case 'delete':				
				if ( current_user_can('delete_department') )
				{
					foreach(self::$records as $id )
					{
						usam_delete_department( $id );	
					}
					return ['deleted' => self::$records]; 
				}					
				return ['access' => 1];
			break;		
		}
	}
		
	private static function controller_employees() 
	{				
		switch( self::$action )
		{	
			case 'delete':
				$i = 0;	
				if ( current_user_can('delete_employee') )
				{
					foreach ( self::$records as $id )
					{				
						if ( usam_update_contact( $id, ['contact_source' => 'orher']) )					
							$i++;
					}
				}
				return array( 'deleted' => $i );
			break;			
			case 'process_chat':			
				$i = 0;	
				if ( current_user_can('edit_employee') )
				{						
					foreach ( self::$records as $id )
					{
						if ( usam_update_contact_metadata($id, 'online_consultant', 1) )
							$i++;
					}
				}
				return array( 'updated' => $i );
			break;	
			case 'not_process_chat':	
				$i = 0;	
				if ( current_user_can('edit_employee') )
				{						
					foreach ( self::$records as $id )
					{
						if ( usam_update_contact_metadata($id, 'online_consultant', 0) )
							$i++;
					}
				}
				return array( 'updated' => $i );
			break;
			case 'process_webform':
				$i = 0;	
				if ( current_user_can('edit_employee') )
				{						
					foreach ( self::$records as $id )
					{
						if ( usam_update_contact_metadata($id, 'webform', 1) )
							$i++;
					}
				}
				return array( 'updated' => $i );	
			break;
			case 'not_process_webform':		
				$i = 0;	
				if ( current_user_can('edit_employee') )
				{						
					foreach ( self::$records as $id )
					{
						if ( usam_update_contact_metadata($id, 'webform', 0) )
							$i++;
					}
				}					
				return array( 'updated' => $i );	
			break;	
		}
	}
		
	private static function controller_my_files() 
	{		
		switch( self::$action )
		{	
			case 'delete':
				if ( !empty(self::$records) )
				{
					usam_delete_files( array( 'include' => self::$records, 'cache_results' => false ) );
					return array( 'deleted' => count(self::$records) ); 
				}
			break;	
		}
	}
	
	private static function controller_files() 
	{		
		switch( self::$action )
		{	
			case 'delete':
				if ( !empty(self::$records) )
				{
					usam_delete_files( array( 'include' => self::$records, 'cache_results' => false ) );
					return array( 'deleted' => count(self::$records) ); 
				}
			break;	
		}
	}
	
	private static function controller_banners() 
	{		
		require_once( USAM_FILE_PATH . '/includes/theme/banner.class.php' );
		switch( self::$action )
		{	
			case 'delete':
				$i = 0;
				foreach ( self::$records as $id )
				{			
					if ( usam_delete_banner( $id ) )					
						$i++;
				}
				return array( 'deleted' => $i ); 				
			break;	
			case 'activate':					
				foreach ( self::$records as $id )	
				{
					usam_update_banner( $id, array('status' => 'active') );	
				}
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'deactivate':	
				foreach ( self::$records as $id )	
				{
					usam_update_banner( $id, array('status' => 'draft') );		
				}
				return array( 'updated' => count(self::$records) ); 
			break;
			case 'copy':
				foreach ( self::$records as $id )
				{
					$banner = usam_get_banner( $id );
					$banner_id = usam_insert_banner( $banner );
					
					$banner_locations = usam_get_banner_location( $id );	
					if ( $banner_locations )
						usam_set_banner_location( $banner_id, $banner_locations );
				}
				return array( 'form' => 'edit', 'form_name' => 'banner', 'id' => $banner_id);
			break;
		}
	}
	
	private static function controller_products_on_internet() 
	{		
		require_once( USAM_FILE_PATH . '/includes/product/products_on_internet.class.php' );
		switch( self::$action )
		{			
			case 'delete':
				$i = 0;
				foreach ( self::$records as $id )
				{			
					if ( usam_delete_product_internet( $id ) )					
						$i++;
				}
				return ['deleted' => $i]; 
			break;			
			case 'products_search':	
				$i = usam_get_total_products();		
				usam_create_system_process( __("Поиск товаров в интернете", "usam" ), [], 'internet_product_search', $i, 'internet_product_search', 1 );
				return ['add_event' => 1];
			break;		
			case 'cleaning':	
				global $wpdb;
				$wpdb->query( "TRUNCATE TABLE `" . USAM_TABLE_PRODUCTS_ON_INTERNET . "`" ); 
				return ['ready' => 1];
			break;			
		} 		
	}
	
	private static function controller_sellers() 
	{		
		switch( self::$action )
		{			
			case 'delete':
				$i = 0;
				foreach ( self::$records as $id )
				{			
					if ( usam_delete_seller( $id ) )					
						$i++;
				}
				return array( 'deleted' => $i ); 
			break;			
		} 
	}
		
	private static function controller_commissions() 
	{		
		switch( self::$action )
		{			
			case 'delete':
				$i = 0;
				foreach ( self::$records as $id )
				{			
					if ( usam_delete_marketplace_commission( $id ) )					
						$i++;
				}
				return array( 'deleted' => $i ); 
			break;			
		} 
	}
	
	private static function controller_marking_codes() 
	{		
		require_once(USAM_FILE_PATH.'/includes/product/marking_code.class.php');
		switch( self::$action )
		{			
			case 'delete':
				$i = 0;
				foreach ( self::$records as $id )
				{			
					if ( usam_delete_marking_code( $id ) )					
						$i++;
				}
				return array( 'deleted' => $i ); 
			break;			
		} 
	}	
	
	private static function controller_ftp_settings() 
	{				
		switch( self::$action )
		{			
			case 'ftp_test':
				$ftp = new USAM_FTP();
				$result = [];
				if ( !$ftp->ftp_open() )	
				{								
					foreach( $ftp->get_errors() as $error ) 			
						$result['errors'][] = $error;
				}
				else
					$result['messages'][] = __('Соединение установлено успешно!','usam');	 
				return $result; 
			break;	
		} 
	}		
	
	private static function controller_units() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				usam_delete_data( self::$records, 'usam_units_measure', false );			
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}
	
	private static function controller_balance_information() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				usam_delete_data( self::$records, 'usam_balance_information', false );			
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}
	
	private static function controller_phones() 
	{	
		switch( self::$action )
		{		
			case 'delete':						
				usam_delete_data( self::$records, 'usam_phones' );			
				return array( 'deleted' => count(self::$records) ); 
			break;	
		}	
		return false;
	}	
	
	private static function controller_tools()
	{
		global $wpdb;		
		switch( self::$action )
		{	
			case 'backup_bd':						
				if ( !current_user_can('manage_options') )
					return ['access' => 1];
		
				require_once( USAM_FILE_PATH . '/includes/technical/mysql_backup.class.php' );
				try 
				{				
					$sql_dump = new USAM_MySQL_Backup();
					foreach ( $sql_dump->tables_to_dump as $key => $table ) 
					{
						if ( $wpdb->prefix != substr( $table,0 , strlen( $wpdb->prefix ) ) )
							unset( $sql_dump->tables_to_dump[ $key ] );
					}
					ob_start();
					$sql_dump->execute();
					$data = ob_get_clean();					
					unset( $sql_dump );
					$result = ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => DB_NAME.'.sql'];	
				} 
				catch ( Exception $e ) 
				{
					$result['errors'][] = $e->getMessage();
				}
				return $result;
			break;
			case 'backup_themes':						
				if ( !current_user_can('manage_options') )
					return ['access' => 1];
					
				$my_theme = wp_get_theme();				
				$wp_theme_path = get_stylesheet_directory();	
				$directory = USAM_BACKUP_DIR.'theme'; 	
				$file_name = $my_theme->get( 'Template' ).".zip";
				$file_path = $directory.'/'.$file_name;					
				if ( !is_dir($directory) )
					mkdir($directory, 0775);
	
				$result = usam_zip_folder( $wp_theme_path, $file_path );
				if ( $result === true )					
				{
					$data = file_get_contents($file_path);							
					$result = ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $my_theme->get('Name').".zip"];	
				}
				else
					$result = ['errors' => $result];
				return $result;	
			break;	
			case 'move_themes':					
				require_once( USAM_FILE_PATH . '/admin/includes/theming.class.php' ); // перенос шаблонов в тему				
				$current_theme_files = usam_list_product_templates( USAM_THEMES_PATH );	
				$themes_location = array_diff( $current_theme_files, self::$records );	
				foreach( $themes_location as $template ) 
				{
					unlink(USAM_THEMES_PATH.$template);
				}				
				if ( self::$records )
				{
					$theming = new USAM_Theming( self::$records ); 				
					$errors = $theming->get_errors();
					if ( !empty($errors) )
						return ['errors' => [$errors]];
				}
				return ['ready' => 1];
			break;				
		}
	}
	
	private static function controller_nuke()
	{
		global $wpdb;		
		switch( self::$action )
		{	
			case 'directory':						
				require_once( USAM_FILE_PATH . '/admin/db/db-install/system_default_data.php' );
				new USAM_Load_System_Default_Data( self::$records );
				return ['ready' => 1]; 
			break;
			case 'delete_products':						
				remove_all_actions('pre_get_posts');
				$posts = usam_get_posts(['post_type' => 'usam-product', 'fields' => 'ids', 'nopaging' => true]);
				if ( usam_create_system_process( __('Удалить товары','usam'), ['post_status' => 'all', 'post_type' => 'usam-product'], 'delete_post', count($posts), 'delete_post' ) )
					return ['add_event' => 1]; 
			break;
			case 'delete_product_variations':		
				$variations = $wpdb->get_results("SELECT `ID` FROM `" . $wpdb->posts . "` WHERE `post_type` = 'usam-product' AND `post_parent` <> 0");
				if( $variations ) 
				{
					foreach( $variations as $variation ) 
					{
						if( $variation->ID )
							wp_delete_post( $variation->ID, true );
					}
				}
				// Terms
				$variations = get_terms( array( 'taxonomy' => 'usam-variation', 'hide_empty' => false ) );
				if( !empty($variations) ) {
					
					foreach( $variations as $variation )
					{
						if( $variation->term_id )
						{
							wp_delete_term( $variation->term_id, 'usam-variation' );
						}
					}
				}
				delete_option( 'usam-variation_children' );
				return ['ready' => 1]; 
			break;			
			case 'delete_images':					
				$posts = usam_get_posts(['post_type' => 'attachment', 'fields' => 'ids', 'nopaging' => true]);
				usam_create_system_process( __('Удалить изображения','usam'), ['post_status' => 'all', 'post_type' => 'attachment'], 'delete_post', count($posts), 'delete_post' );	
				return ['add_event' => 1]; 				
			break;		
			case 'delete_orders':								
				$count = usam_get_orders(['fields' => 'count', 'number' => 1]);
				usam_create_system_process( __('Удалить заказы','usam'), [], 'delete_orders', $count, 'delete_orders' );				
				return ['add_event' => 1]; 
			break;
			case 'delete_coupons':
				$wpdb->query( "TRUNCATE TABLE `" . USAM_TABLE_COUPON_CODES . "`" );
			break;
			case 'delete_pages':
				$system_page = get_option( 'usam_system_page', false );
				foreach( $system_page as $page ) 
				{
					wp_delete_post( $page['id'], true );
				}
				return ['ready' => 1]; 
			break;
			case 'delete_options':								
				// $wpdb->query( "DELETE FROM `" . $wpdb->prefix . "options` WHERE `option_name` LIKE 'usam_%'" );
				
			break;			
			// WordPress
			case 'delete_posts':
				$posts = usam_get_posts(['post_type' => 'post', 'fields' => 'ids', 'nopaging' => true]);
				if ( usam_create_system_process( __('Удалить посты','usam'), ['post_status' => 'all', 'post_type' => 'post'], 'delete_post', count($posts), 'delete_post'.time() ) )
					return ['add_event' => 1]; 
			break;			
			case 'delete_links':
				$wpdb->query( "TRUNCATE TABLE `" . $wpdb->prefix . "links`" );
				return ['ready' => 1]; 
			break;
			case 'delete_comments':
				$comments = get_comments();
				if( $comments ) 
				{
					foreach( $comments as $comment ) 
					{
						if( $comment->comment_ID )
							wp_delete_comment( $comment->comment_ID, true );
					}
				}
				return ['ready' => 1]; 
			break;
			default:
				$action = str_replace('delete_', '', self::$action);
				if ( taxonomy_exists($action) )
				{
					self::delete_taxonomy( $action );
					return ['ready' => 1]; 
				}
			break;
		}
	}
	
	private static function delete_taxonomy( $term_taxonomy ) 
	{
		$terms = get_terms(['taxonomy' => $term_taxonomy, 'hide_empty' => false]);
		if( !empty($terms) )
		{
			foreach( $terms as $term ) 
				wp_delete_term( $term->term_id, $term_taxonomy );
		}
	}
	
	private static function controller_log()
	{	
		switch( self::$action )
		{
			case 'delete':		
				$i = 0;
				foreach ( self::$records as $name )
				{
					if ( file_exists(USAM_UPLOAD_DIR .'Log/'.$name) && unlink(USAM_UPLOAD_DIR .'Log/'.$name) )
						$i++;
				}		
				return ['deleted' => $i]; 
			break;
			case 'empty':		
				foreach ( self::$records as $name )
				{
					$handle = fopen(USAM_UPLOAD_DIR .'Log/'.$name, 'w');
					if ( $handle )	
						$handle = fclose($handle);			
				}
				return ['ready' => $handle]; 				
            break;  
			case 'save':	
				$settings = ['autorefresh' => 0, 'display' => 'filo'];
				if ( isset($_REQUEST['autorefresh']) )
					$settings['autorefresh'] = 1;				
				if ( isset($_REQUEST['display']) )
					$settings['display'] = $_REQUEST['display'] == 'fifo' ? 'fifo' : 'filo';
				
				$user_id = get_current_user_id();
				update_user_meta( $user_id, 'usam_page_tab_log', $settings );
				return ['ready' => 1]; 				
            break;  
		}
	}
	
	private static function controller_posts()
	{			
		switch( self::$action )
		{
			case 'trash':		
				$i = 0;
				if ( !empty(self::$records) )
				{
					$posts = usam_get_posts(['update_post_term_cache' => false, 'cache_results' => true, 'post_type' => 'any', 'post__in' => self::$records]);								
					wp_defer_term_counting( true );
					foreach ( $posts as $post )
						wp_trash_post( $post->ID );						
					wp_defer_term_counting( false );	
					return ['deleted' => count(self::$records)]; 
				}
				return ['updated' => 0]; 
			break;	
			case 'untrash':		
				$i = 0;
				if ( !empty(self::$records) )
				{
					$posts = usam_get_posts(['update_post_term_cache' => false, 'cache_results' => true, 'post_type' => 'any', 'post_status' => 'trash', 'post__in' => self::$records]);
					wp_defer_term_counting( true );
					foreach ( $posts as $post )
						wp_untrash_post( $post->ID );						
					wp_defer_term_counting( false );	
					return ['updated' => count(self::$records)]; 
				}
				return ['updated' => 0]; 
			break;			
			case 'empty_trash':		
				if ( !empty($_REQUEST['post_type']) )		
				{
					$post_type = sanitize_text_field($_REQUEST['post_type']);		
					$args['fields'] = 'ids';	
					$args['update_post_meta_cache'] = false;	
					$args['update_post_term_cache'] = false;
					$args['cache_results'] = false;
					$args['product_meta_cache'] = false;
					$args['post_status'] = 'trash';
					$args['post_type'] = $post_type;
					$posts = usam_get_posts( $args );
					if( usam_create_system_process( __('Очистка корзины','usam'), ['post_status' => 'trash', 'post_type' => $post_type], 'delete_post', count($posts), 'emptying_trash' ) )
						return ['add_event' => 1]; 
				}
				return false; 
			break;
			case 'delete':			
				$args['fields'] = 'ids';
				$args['post__in'] = self::$records;				
				$args['update_post_meta_cache'] = false;	
				$args['update_post_term_cache'] = false;
				$args['cache_results'] = false;
				$args['product_meta_cache'] = false;
				$args['post_type'] = 'any';	
				$posts = usam_get_posts( $args );	
				if( usam_create_system_process( __('Удаление','usam'), ['post__in' => self::$records], 'delete_post', count($posts), 'delete_posts_'.time() ) )
					return ['add_event' => 1]; 
			break;		
			case 'regenerate_thumbnails':				
				require_once(USAM_FILE_PATH.'/includes/media/regenerate-thumbnails.class.php');	
				$i = 0;
				if ( self::$records )
				{
					$posts = usam_get_posts(['update_post_term_cache' => false, 'cache_results' => true, 'post_type' => 'any', 'post__in' => self::$records]);		
					foreach ( $posts as $post )
					{
						$regenerator = USAM_Regenerate_Thumbnails::get_instance( $post->ID );			
						if ( !is_wp_error( $regenerator ) )	
						{							
							if ( $regenerator->regenerate(['delete_unregistered' => true]) )
								$i++;
						}
					}			
				}	
				return usam_get_callback_messages(['updated' => $i]);
			break;				
		}
	}
	
	private static function controller_product_variations()
	{	
		switch( self::$action )
		{
			case 'show':											
				$i = 0;
				foreach ( self::$records as $id )
				{
					if ( wp_update_post(['ID' => $id, 'post_status' => 'publish']) )
						$i++;
				}
				return ['updated' => $i]; 
			break;
			case 'draft':											
				$i = 0;
				foreach ( self::$records as $id )
				{
					if ( wp_update_post(['ID' => $id, 'post_status' => 'draft']) )
						$i++;
				}
				return ['updated' => $i]; 
			break;	
			case 'trash':											
				$i = 0;
				$post_type_object = get_post_type_object( 'usam-product' );
				foreach ( self::$records as $id )
				{
					if ( current_user_can( $post_type_object->cap->delete_post, $id ) )
					{
						if ( wp_trash_post( $id ) )
							$i++;
					}
				}
				return ['trashed' => $i]; 
			break;	
			case 'delete':											
				$i = 0;
				$post_type_object = get_post_type_object( 'usam-product' );
				foreach ( self::$records as $id )
				{
					if ( current_user_can( $post_type_object->cap->delete_post, $id ) )
					{
						if ( wp_delete_post( $id, true ) )
							$i++;
					}
				}
				return ['trashed' => $i]; 
			break;			
			case 'untrash':											
				$i = 0;
				$post_type_object = get_post_type_object( 'usam-product' );
				foreach ( self::$records as $id )
				{
					if ( current_user_can( $post_type_object->cap->delete_post, $id ) )
					{
						if ( wp_untrash_post( $id ) )
							$i++;
					}
				}
				return ['updated' => $i]; 
			break;	
			case 'delete_all_variations':											
				global $wpdb;
				$post_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_status='trash' AND post_type='usam-product' AND post_parent IN (".implode(",",self::$records).")" );
				$post_type_object = get_post_type_object( 'usam-product' );
				foreach ( $post_ids as $id )
				{
					if ( current_user_can( $post_type_object->cap->delete_post, $id ) )
					{
						wp_delete_post( $id, true );
					}
				}
				return ['ready' => 1]; 
			break;					
			case 'delete_image':											
				$i = 0;				
				if ( self::$records )
				{
					$post_type_object = get_post_type_object( 'usam-product' );
					$posts = usam_get_posts(['post_type' => 'attachment', 'post_parent__in' => self::$records]);	
					foreach ( $posts as $post )
					{						
						if ( current_user_can( $post_type_object->cap->delete_post, $post->ID ) )
						{
							if ( wp_delete_attachment( $post->ID, true ) )
								$i++;
						}
					}
				}
				return ['updated' => $i];
			break;			
		}
	}	
		
	private static function controller_competitors_products() 
	{		
		switch( self::$action )
		{			
			case 'delete':				
				global $wpdb;
				$result = $wpdb->query("DELETE FROM ".USAM_TABLE_COMPETITOR_PRODUCT_PRICE." WHERE competitor_product_id IN (".implode(",",self::$records).")");
				$result = $wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCTS_COMPETITORS." WHERE id IN (".implode(",",self::$records).")");
				return ['deleted' => count(self::$records)]; 
			break;			
		} 
	}
	
	private static function controller_licenses() 
	{		
		switch( self::$action )
		{			
			case 'delete':				
				global $wpdb;
				$result = $wpdb->query("DELETE FROM ".USAM_TABLE_LICENSES." WHERE id IN (".implode(",",self::$records).")");
				return ['deleted' => count(self::$records)]; 
			break;			
		} 
	}
	
	private static function controller_parser_url() 
	{		
		global $wpdb;
		switch( self::$action )
		{			
			case 'delete':
				$result = $wpdb->query("DELETE FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE id IN (".implode(",",self::$records).")");
				return ['deleted' => count(self::$records)]; 
			break;	
			case 'not_processed':
				$wpdb->query("UPDATE ".USAM_TABLE_PARSING_SITE_URL." SET status='0' WHERE id IN (".implode(",",self::$records).")");
				return ['update' => count(self::$records)]; 
			break;		
		} 
	}	
	
	private static function controller_triggers() 
	{	
		switch( self::$action )
		{	
			case 'delete':				
				require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
				foreach(self::$records as $id )
				{
					usam_delete_trigger( $id );	
				}
				return ['deleted' => self::$records]; 
			break;		
		}
	}
	
	private static function controller_showcases() 
	{	
		switch( self::$action )
		{	
			case 'delete':				
				require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
				foreach(self::$records as $id )
				{
					usam_delete_showcase( $id );	
				}
				return ['deleted' => self::$records]; 
			break;		
		}
	}	
}
?>