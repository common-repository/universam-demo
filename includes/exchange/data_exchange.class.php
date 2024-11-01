<?php
final class USAM_Exchange
{
	private $rule; 
	public  $errors  = [];
	private $connect = false;
	private $ftp = false;
	private $column_setup_error = false;
	private $name_table = 'exchange_rule'; 
	
	public function __construct( $id )
	{				
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php' );
		if ( is_numeric($id) )
		{
			$this->rule = usam_get_exchange_rule( $id );			
			$metadatas = usam_get_exchange_rule_metadata( $id );
			foreach($metadatas as $metadata )
				$this->rule[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
		}
		elseif ( is_array($id) )
			$this->rule = $id;				
		
		if (  !empty($this->rule['type']) )
			$this->name_table = $this->rule['type']."_".$this->rule['id'];
	}
	
	public function get_errors( )
	{	
		return $this->errors;
	}
	
	private function set_error( $error )
	{			
		$this->errors[]  =  sprintf( __('Обмен данными. Ошибка: %s'), $error );
	}
	
	private function set_log_file( )
	{	
		if ( !empty($this->errors) )
		{
			usam_log_file( $this->errors );
			$this->errors = array();
		}
	}
		
	private function ftp_open()
	{				
		$this->ftp = new USAM_FTP();
		$result	= $this->ftp->ftp_open();		
		if ( !$result )	
		{			
			foreach( $this->ftp->get_errors() as $error ) 			
				$this->set_error( $error );	
		}	
		return $result;
	}
	
	private function ftp_get( $remote_file )
	{
		$result = false;	
		if ( $this->ftp_open() )
		{	
			$result = $this->ftp->ftp_get( $remote_file );
			if ( $result && !empty($this->rule['delete_file']) )
				$this->ftp->ftp_delete( $remote_file );				
			$this->ftp->ftp_close();
			if ( !$result )		
				$this->set_error( __("Файл не скопирован c FTP","usam") );
		}
		return $result;
	}
		
	//Загрузить файл по ссылке
	private function get_file_url( )
	{
		set_time_limit(3000);
		$result = false;
		$file_path = USAM_UPLOAD_DIR."exchange/".basename($this->rule['file_data']);
		$file = fopen($this->rule['file_data'], 'rb', false, stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]));
		if ( $file ) 
		{
			$newf = fopen($file_path, 'wb');
			if ($newf) 
			{
				while(!feof($file)) {
					fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
				}
				$result = $file_path;
			}
		}
		if ($file) 
			fclose($file);
		if ($newf) 
			fclose($newf);
		return $result;
	}
				
	private function compare_data( $key, $value )
	{		
		$result = true;		
		if( !empty($this->rule['exception'][$key]) && !empty($this->rule['exception'][$key]['value']) )
		{
			$data = explode($this->rule['splitting_array'],$this->rule['exception'][$key]['value']);				
			foreach( $data as $r )
			{
				if ( usam_compare_data( $this->rule['exception'][$key]['comparison'], $value, $r ) )
				{
					$result = false;
					break;
				}
			}
		}
		return $result;
	}
	
	private function create_table( $columns, $longtext )
	{	
		global $wpdb;
		$db_columns = [];		
		$new_colums = [];
		if ( !empty($this->rule['columns2']) )
		{
			foreach($this->rule['columns2'] as $k => $column)
			{
				if ( $column )
					$columns[] = $column;
			}
		}
		foreach( $columns as $column ) 
		{
			if ( $column )
			{
				$db_columns[$column] = in_array($column, $longtext) ? "`$column` longtext NOT NULL DEFAULT ''" : "`$column` text NOT NULL DEFAULT ''";
				$new_colums[] = $column;
			}
		}
		$db_columns[] = '`index_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT';
		$db_columns[] = "PRIMARY KEY (`index_id`)";
		$wpdb->query("CREATE TABLE {$this->name_table} (".implode(",",$db_columns).") CHARSET=$wpdb->charset;");
		return $new_colums;
	}
	
	private function define_delimiter_file( $file_path )
	{	
		$lines = [];
		$handle = fopen($file_path, "r");
		$i = 0;
		while(!feof($handle))
		{
			$lines[] = trim(fgets($handle));
			$i++;
			if ( $i > 10 )
				break;
		}
		fclose($handle);
		return usam_define_delimiter_file( $this->row_encoding($lines) );
	}
	
	private function encoding( $value )
	{		
		if ( $this->rule['encoding'] != 'utf-8' )
		{			
			switch( $this->rule['encoding'] )
			{				
				case 'windows-1256':
				case 'windows-1251':
					return iconv($this->rule['encoding'], "utf-8//TRANSLIT//IGNORE", $value);
				break;	
				case 'utf-8-bom': //Удалить BOM из строки
					if(substr($value, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf))
						return substr($value, 3);	
				break;	
				case '': 					
				case 'auto': 						
					$encoding = mb_detect_encoding($value, "auto");	
					if ( $encoding == 'ASCII' )
						return mb_convert_encoding($value, 'UTF-8', 'windows-1251');	
					elseif ( $encoding && $encoding != 'UTF-8' )
						return mb_convert_encoding($value, 'UTF-8', $encoding);						
				break;									
				default:
					return mb_convert_encoding($value, 'UTF-8', $encoding);
				break;
			}
		}
		return $value;
	}
	
	private function row_encoding( $row )
	{		
		$results = [];
		foreach ( $row as $key => $value)
			$results[$key] = $this->encoding( $value );
		return $results;
	}
	
	private function read_txt_file( $file_path, $longtext )
	{
		global $wpdb;
		set_time_limit(3000);	
		$delimiter = usam_get_type_file_exchange( $this->rule['type_file'], 'delimiter' );	
		if ( !$delimiter )
			$delimiter = $this->define_delimiter_file( $file_path );
		else
			$delimiter = str_replace('"', '', $delimiter);
	
		$f_export = fopen( $file_path, 'r' );
		$length = filesize( $file_path );			
		if ( $this->rule['headings'] )
		{
			$row = $this->row_encoding( fgetcsv($f_export, $length, $delimiter) );	
			$columns = [];
			foreach($this->rule['columns'] as $key => $name)		
			{
				foreach($row as $value)	
				{
					if ($name == $value)
						$columns[] = $key;
				}
			}	
		}
		else
			$columns = $this->rule['columns'];	
		if ( !$columns )
		{
			$this->set_error( __("Колонки импорта не указаны.","usam" ) );	
			return 0;
		}			
		$load_data = !empty($this->rule['start_line'])?$this->rule['start_line']-1:0;
		if ( $wpdb->get_var("show tables like '{$this->name_table}'") )
			$load_data = $wpdb->get_var("SELECT COUNT(*) FROM `{$this->name_table}`");	
		else
			$new_colums = $this->create_table($columns, $longtext);				
		$i = 0;
		$error = 0;		
		$j = 0;	
		while ($row = fgetcsv($f_export, $length, $delimiter))
		{
			$j++;
			if ( $load_data != 0 && $load_data >= $j )
				continue;
			
			$items = [];			
			$insert = true;
			foreach ($columns as $key => $column)
			{		
				if ( !$column )
					continue;
				$items[$column] = isset($row[$key]) ? stripcslashes(esc_sql( $this->encoding($row[$key])) ):'';					
				if ( !$this->compare_data($key, $items[$column]) )
				{
					$insert = false;
					break;
				}
			}		
			if ( $insert && !empty($items) )
			{		
				if ( $this->insert( $items ) )
					$i++;
				else
					$error++;
			}	
			if ( $this->rule['end_line'] && $j >= $this->rule['end_line'] )
				break;
		}		
		if ( $error )
		{
			$this->column_setup_error = true;
			$this->set_error( usam_get_plural($error, ['ошибка загрузки данных', 'ошибки загрузки данных', 'ошибок загрузки данных'] ) );
		}
		fclose($f_export);	
		return $i+$load_data;		
	}
	
	private function read_exel_file( $file_path, $longtext )
	{
		global $wpdb;
		set_time_limit(3000);
		$file_data = usam_read_exel_file( $file_path );	
 
		$file_data = apply_filters( 'usam_import_exel_data', $file_data, $this->rule );	
		$i = 0;
		$error = 0;
		if ( $this->rule['headings'] )
		{
			foreach ($this->rule['columns'] as $key => $name)		
			{
				foreach ($file_data[0] as $value)	
				{
					if ($name == $value)
						$columns[] = $key;
				}
			}
			unset($file_data[0]);
		}
		else
			$columns = $this->rule['columns'];			
		if ( !$columns )
		{
			$this->set_error( __("Колонки импорта не указаны.","usam" ) );	
			return 0;
		}
		//$this->rule['encoding'] = $this->rule['encoding']?$this->rule['encoding']:'utf-8-bom';
		
		$load_data = !empty($this->rule['start_line'])?$this->rule['start_line']:0;
		if ( $wpdb->get_var("show tables like '{$this->name_table}'") )
			$load_data = $wpdb->get_var("SELECT COUNT(*) FROM `{$this->name_table}`");	
		else
			$new_colums = $this->create_table($columns, $longtext);			
		foreach ($file_data as $j => $row)
		{			
			$j++;
			if ( $load_data != 0 && $load_data >= $j )
				continue;			
			
			$items = array();			
			$insert = true;
			foreach( $columns as $key => $column)
			{									
				if ( !$column )
					continue;				
				$items[$column] = isset($row[$key]) ? stripcslashes( esc_sql( $this->encoding($row[$key]) ) ):'';
				if ( !$this->compare_data($key, $items[$column]) )
				{
					$insert = false;
					break;
				}
			}
			if ( $insert )
			{					
				if ( $this->insert( $items ) )
					$i++;
				else
					$error++;	
			}	
			if ( $this->rule['end_line'] && $j >= $this->rule['end_line'] )
				break;
		}	
		if ( $error )
		{
			$this->column_setup_error = true;
			$this->set_error( usam_get_plural($error, ['ошибка загрузки данных', 'ошибки загрузки данных', 'ошибок загрузки данных']) );
		}
		return $i+$load_data;		
	}
	
	private function insert( $items )
	{
		global $wpdb;		
		foreach($this->rule['columns'] as $k => $column)
		{
			if ( !empty($this->rule['columns2'][$k]) )
				$items[$this->rule['columns2'][$k]] = $items[$column];
		}				
		$items = apply_filters( 'usam_import_data', $items, $this->rule );
		return $wpdb->insert($this->name_table, $items);
	}
		
	public function preparation_exchange_data( )
	{		
		global $wpdb;
		if ( !empty($this->rule) )
		{			
			if ( usam_get_exchange_rule_metadata($this->rule['id'], 'process') != 'load_data' )
			{
				$this->rule['start_date'] = date('Y-m-d H:i:s');
				$this->rule['end_date'] = '';
				if ( !empty($this->rule['id']) )
				{						
					usam_update_exchange_rule( $this->rule['id'], ['start_date' => date('Y-m-d H:i:s'), 'end_date' => '']);
					usam_update_exchange_rule_metadata( $this->rule['id'], 'process', 'load_data' );
				}
				$wpdb->query("DROP TABLE IF EXISTS {$this->name_table}");
			}				
			$result_load_data = $this->load_data();
			if ( $result_load_data !== false )
				$count = $wpdb->get_var("SELECT COUNT(*) FROM `{$this->name_table}`");	
			else
				$count = 0;			
		
			usam_update_exchange_rule_metadata( $this->rule['id'], 'result_exchange', [] );	
			if( $count )
			{	
				usam_update_exchange_rule_metadata( $this->rule['id'], 'process', 'start_exchange' );
				$data = empty($this->rule['id'])?$this->rule:$this->rule['id'];						
				usam_create_system_process( __('Импорт','usam').' - '.$this->rule['name'], $data, 'start_exchange', $count, 'exchange_'.$this->rule['type']."-".$this->rule['id'] );					
			}
			else
			{
				if ( $this->column_setup_error )
					usam_update_exchange_rule_metadata( $this->rule['id'], 'process', 'column_setup_error' );
				else			
					usam_update_exchange_rule_metadata( $this->rule['id'], 'process', 'no_data' );
			}
			$this->set_log_file( );
		}
	}
		
	private function load_data()
	{		
		$file_path = false;		
		if ( $this->rule['exchange_option'] == 'ftp' )
			$file_path = $this->ftp_get( $this->rule['file_data'] );
		elseif ( $this->rule['exchange_option'] == 'url' )
			$file_path = $this->get_file_url( );
		elseif ( $this->rule['exchange_option'] == 'folder' )
		{
			$file = usam_get_files(['folder_id' => $this->rule['file_data'], 'number' => 1, 'orderby' => 'id', 'order' => 'DESC']);
			if ( !empty($file['file_path']) )
				$file_path = USAM_UPLOAD_DIR.$file['file_path'];	
		}
		elseif ( $this->rule['exchange_option'] == 'file' )
		{		
			if ( !empty($this->rule['file_data']['file_path']) )
				$file_path = USAM_UPLOAD_DIR.$this->rule['file_data']['file_path'];	
		}
		elseif ( $this->rule['exchange_option'] == 'local' || !empty($this->rule['file_data']) )
			$file_path =  USAM_UPLOAD_DIR."exchange/".$this->rule['file_data'];					
		$result = false;
		if ( $file_path )
		{
			$longtext = ['post_content','post_excerpt','additional_units','crosssell','similar'];
			if ( is_file($file_path) ) 	
			{					
				switch ( usam_get_extension( $file_path ) ) 
				{
					case 'xls':	
					case 'xlsx':					
						$result = $this->read_exel_file( $file_path, $longtext );
					break;
					case 'txt':
					case 'csv':			
						$result = $this->read_txt_file( $file_path, $longtext );
					break;	
				}			
			}		
			if ( $result )
				do_action('after_load_data', $this->rule, $file_path );
			if ( $this->rule['exchange_option'] == 'ftp' )
				unlink($file_path);	
			elseif ( !empty($this->rule['delete_file']) )
			{								
				if ( $this->rule['exchange_option'] == 'folder' )
					usam_delete_file( $file['id'], true );
				else
					unlink($file_path);			
			}			
		}	
		return $result;
	}
	
	public function start_exchange( $page )
	{
		global $wpdb;					
		if ( !defined('USAM_AMOUNT_IMPORTED_DATA') )
			$number = !empty($this->rule['type_import']) && $this->rule['type_import'] == 'insert'?5000:2000;	// $wpdb->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
		else
			$number = (int)USAM_AMOUNT_IMPORTED_DATA;
		$data = $wpdb->get_results("SELECT * FROM `{$this->name_table}` LIMIT ".$number, ARRAY_A);
		$count = 0;		
		$after_exchange = false;
		if ( !empty($data) )
		{
			$method = "exchange_".$this->rule['type'];	
			$this->rule['max_time'] = ini_get("max_execution_time") - 10;
			if ( $this->rule['max_time'] <= 0 )
				$this->rule['max_time'] = 300;
				
			if ( method_exists($this, $method) )
				$results = $this->$method( $data );
			else
				$results = apply_filters( 'usam_start_exchange_method', null, $data, $this->rule );				
			if ( $results )
			{
				if ( !empty($results['records']) )
				{
					$count = $results['records'];
					$wpdb->query("DELETE FROM `{$this->name_table}` LIMIT $count");							
				}	
				if ( !empty($this->rule['id']) && isset($results['add']) )
				{
					$result_exchange = usam_get_exchange_rule_metadata( $this->rule['id'], 'result_exchange' );
					if ( !empty($result_exchange) )
					{				
						$result_exchange['add'] += $results['add'];
						$result_exchange['update'] += $results['update'];
						$result_exchange['records'] += $results['records'];
					}
					else
						$result_exchange = $results;		
					usam_update_exchange_rule_metadata( $this->rule['id'], 'result_exchange', $result_exchange );					
				}
			}
			$number = $wpdb->get_var("SELECT COUNT(*) FROM `{$this->name_table}`");
			if( !$wpdb->last_error && !$number )
				$after_exchange = true;
		}		
		if ( $after_exchange )
		{				
			$wpdb->query("DROP TABLE IF EXISTS `{$this->name_table}`");			
			$args = $this->get_args_after_exchange_product_import();
			if ( $args )
			{
				$total = usam_get_total_products( $args );	
				$data = empty($this->rule['id'])?$this->rule:$this->rule['id'];
				usam_create_system_process( __('Обработка завершения импорта','usam').' - '.$this->rule['name'], $data, 'after_exchange', $total, 'after_exchange_'.$this->rule['type']."-".$this->rule['id'] );
			}
		}
		$this->set_log_file( );
		return $count;
	}
	
	private function exchange_product_import( $data )
	{
		require_once( USAM_FILE_PATH . '/includes/product/product_importer.class.php' );							
		$importer = new USAM_Product_Importer( $this->rule );		
		return $importer->start( $data );		
	}
	
	public function after_exchange( $page )
	{
		$count = 0;
		$method = "after_exchange_".$this->rule['type'];					
		if ( method_exists($this, $method) )
		{	
			$count = $this->$method( $page );	
		}			
		usam_update_exchange_rule($this->rule['id'], ['end_date' => date('Y-m-d H:i:s')]);
		return $count;
	}
	
	private function get_args_after_exchange_product_import( )
	{
		$args = [];
		if ( isset($this->rule['not_updated_products_stock']) && is_numeric($this->rule['not_updated_products_stock']) || !empty($this->rule['not_updated_products_status']) )
		{
			$key = 'rule_'.$this->rule['id'];			
			if ( isset($this->rule['selection_raw_data']) && $this->rule['selection_raw_data'] == 'template' )
				$productmeta_query = [['key' => $key, 'compare' => '<', 'value' => $this->rule['start_date'], 'type' => 'DATETIME']];
			else
				$productmeta_query = [['relation' => 'OR', ['key' => $key, 'compare' => '<', 'value' => $this->rule['start_date'], 'type' => 'DATETIME'], ['key' => $key, 'compare' => 'NOT EXISTS'] ]];
			$args = ['fields' => 'ids', 'productmeta_query' => $productmeta_query, 'stocks_cache' => false, 'prices_cache' => false];
		}
		return $args;
	}
	
	private function after_exchange_product_import( $page )
	{
		global $wpdb;
		
		$count = 0;
		if ( isset($this->rule['not_updated_products_stock']) && is_numeric($this->rule['not_updated_products_stock']) || !empty($this->rule['not_updated_products_status']) )
		{
			$args = $this->get_args_after_exchange_product_import();		
			$args['posts_per_page'] = 2000;
			$args['paged'] = $page;
			if ( isset($this->rule['not_updated_products_stock']) && is_numeric($this->rule['not_updated_products_stock']) )
				$args['stocks_cache'] = true;
			$ids = usam_get_products( $args );			
			if ( !empty($ids) )
			{
				if ( !empty($this->rule['not_updated_products_status']))
				{
					$wpdb->query("UPDATE `{$wpdb->posts}` SET post_status='".$this->rule['not_updated_products_status']."' WHERE post_status!='".$this->rule['not_updated_products_status']."' AND ID IN(".implode(',',$ids).")");
				}
				if ( isset($this->rule['not_updated_products_stock']) && is_numeric($this->rule['not_updated_products_stock']) )
				{
					$storages = usam_get_storages();					
					$recalculate_product_ids = array();
					foreach ( $ids as $product_id )
					{
						foreach ( $storages as $storage)
						{		
							if ( $storage->code != '' )
							{							
								if ( usam_update_product_stock($product_id, 'storage_'.$storage->id, 0) )
									$recalculate_product_ids[] = $product_id;
							}
						}									
					}
					if ( !empty($recalculate_product_ids) )
						usam_recalculate_stock_products( $recalculate_product_ids );
				}
			}	
			$count = count($ids);			
		}
		return $count;
	}
	
	private function exchange_company_import( $data )
	{			
		require_once( USAM_FILE_PATH . '/includes/crm/company_importer.class.php' );							
		$importer = new USAM_Company_Importer( $this->rule );
		return $importer->start( $data );	
	}
	
	private function exchange_contact_import( $data )
	{										
		require_once( USAM_FILE_PATH . '/includes/crm/contact_importer.class.php' );							
		$importer = new USAM_Contact_Importer( $this->rule );
		return $importer->start( $data );	
	}
	
	private function exchange_order_import( $data )
	{										
		require_once( USAM_FILE_PATH . '/includes/document/order_importer.class.php' );							
		$importer = new USAM_Order_Importer( $this->rule );
		return $importer->start( $data );	
	}
	
	private function exchange_location_import( $data )
	{										
		require_once( USAM_FILE_PATH . '/includes/exchange/location_importer.class.php' );							
		$importer = new USAM_Location_Importer( $this->rule );	
		return $importer->start( $data );
	}
	
	private function exchange_brands( $data )
	{										
		require_once( USAM_FILE_PATH . '/includes/exchange/terms_importer.class.php' );							
		$importer = new USAM_Terms_Importer( $this->rule );	
		return $importer->start( $data );
	}
	
	private function exchange_category( $data )
	{										
		require_once( USAM_FILE_PATH . '/includes/exchange/terms_importer.class.php' );							
		$importer = new USAM_Terms_Importer( $this->rule );	
		return $importer->start( $data );
	}	
}
?>