<?php
/* Класс 1C API 

/api/1c?type=sale&mode=query
/api/1c?type=sale&mode=info
/api/1c?type=sale&mode=init
/api/1c?type=act&mode=query
/api/1c?type=catalog&mode=deactivate&timestamp=1668507895
/api/1c?filename=import.xml&mode=import&type=catalog
/api/1c?filename=Srvr_Server_Ref_VM_catalogimport.xml&mode=import&type=catalog
/api/1c?filename=priceLists.xml&mode=import&type=catalog
/api/1c?filename=goods.xml&mode=import&type=catalog
/api/1c?filename=groups.xml&mode=import&type=catalog
/api/1c?filename=propertiesOffers.xml&mode=import&type=catalog
/api/1c?filename=units.xml&mode=import&type=catalog
/api/1c?filename=offers.xml&mode=import&type=catalog
/api/1c?filename=orders-0b91e14b-5160-4e18-be8a-e95e7acc5174_.xml&mode=import&type=sale
*/

require_once(USAM_FILE_PATH.'/includes/technical/system_report.class.php');

final class USAM_1C_API 
{
	private static $instance; 
	private $settings; 
	private $type = null;
	private static $is_transaction = false;
	private static $is_error = false;
	private $charset = 'UTF-8'; //windows-1251
	private $version_1c = '2.09';	
	private $handler = null;
	private $system_report_id = null;	
	private $is_full = true;
	private $number = 100;
	
	public function __construct() { }
	
	public function exchange()
	{		
		if ( !defined('USAM_SAVE_IMPORT_FILES') )
			define('USAM_SAVE_IMPORT_FILES', false );
		
		session_start();
		
		$this->settings = get_option('usam_1c', ['active' => 0, 'version_1c' => '2.09', 'schema_version' => 0]);
		$this->version_1c = isset($this->settings['version_1c'])?$this->settings['version_1c']:'2.09';
		if ( empty($this->settings['active']) )
			return false;	
		
		ob_start( array($this, '_1c_output_callback') );		
		if (!$_GET && isset($_SERVER['REQUEST_URI'])) 
		{
			$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
			parse_str($query, $_GET);
		}
		if ( empty($_REQUEST['type']) && empty($_REQUEST['mode']) ) 
		{
			_e("Как настроить интеграцию с 1С читайте в документации","usam");
			exit;
		}		
		if (empty($_REQUEST['type'])) 
			$this->set_error("No type");
		if (empty($_REQUEST['mode'])) 
			$this->set_error("No mode");	
		
		add_action( 'auth_cookie_expiration', [&$this, 'auth_cookie_expiration'], 100, 3 );			
		$mode = sanitize_title($_REQUEST['mode']);
		$this->type = sanitize_title($_REQUEST['type']);
		$method = 'mode_'.$mode;	
						
		if ( method_exists($this, $method) )
		{				
			if ( $mode != 'checkauth' )
			{				
				$this->check_auth();	
				$this->insert_system_report(['operation' => $mode]);					
			}
			$result = $this->$method();
			if ( $mode != 'checkauth' )
			{
				$this->close_system_report();
			}
			exit($result);
		}
		else 
			$this->set_error( sprintf("Unknown mode %s", $mode) );		
	}
	
	public function auth_cookie_expiration( $expiration, $user_id, $remember ) 
	{
		return DAY_IN_SECONDS;
	}
	
	public static function get_instance() 
	{
		if ( ! self::$instance ) 
			self::$instance = new USAM_1C_API();		
		return self::$instance;
	}
	
	function _1c_output_callback( $buffer ) 
	{
		if ( !headers_sent() ) 
		{
			$is_xml = @$_GET['mode'] == 'query';
			$content_type = !$is_xml || self::$is_error ? 'text/plain' : 'text/xml';
			header("Content-Type: $content_type; charset=$this->charset");
		}
		if ($this->charset == 'UTF-8') 
			$buffer = "\xEF\xBB\xBF$buffer";
		else
			$buffer = mb_convert_encoding($buffer, $this->charset, 'UTF-8');				
		return $buffer;
	}		
	
	private function mode_checkauth() 
	{ 
		if ( isset($_GET['HTTP_AUTHORIZATION']) )
		{
			if(preg_match('/^Basic\s+(.*)$/i', $_GET['HTTP_AUTHORIZATION'], $user_pass))
			{
				list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':',base64_decode($user_pass[1]));				
			}
		}
		else
		{
			foreach (array('HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION') as $server_key) 
			{
				if (!isset($_SERVER[$server_key])) 
					continue;			
				list(, $auth_value) = explode(' ', $_SERVER[$server_key], 2);
				$auth_value = base64_decode($auth_value);
				list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $auth_value);

				break;                                                                  
			}
		}	
		if ( !isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) ) 
			$this->set_error("No authentication credentials");
	
		$user = wp_authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']); // Авторизация пользователя
		if( is_wp_error($user) ) 
			$this->wp_error( $user );	

		if ( !user_can( $user->ID, 'universam_api' ) )
			$this->set_error("No permissions");			

		$expiration = time() + apply_filters('auth_cookie_expiration', DAY_IN_SECONDS, $user->ID, false);
		$auth_cookie = wp_generate_auth_cookie($user->ID, $expiration);			
		exit("success\nusam-auth\n$auth_cookie\n".session_id()."\ntimestamp=". time()."\n");
	}

	function check_auth() 
	{
		if( !empty($_COOKIE['usam-auth']) ) 
		{
			$user = wp_validate_auth_cookie($_COOKIE['usam-auth'], 'auth');
			if (!$user) 
				$this->set_error("Invalid cookie");
		}
		else 
		{
			$user = wp_get_current_user();
			if ( !$user->ID ) 
				$this->set_error( __("Пользователь для обмена не авторизован","usam"));
		}
		$this->check_permissions( $user );
	}		
	
	public function check_permissions( $user ) 
	{	
		if ( !user_can( $user, 'universam_api' ) )
			$this->set_error("No permissions");	
	}

	private function is_debug( )
	{	
		return false;
	}
	
	public function set_error( $error )
	{					
		self::$is_error = true;		
		$last_char = substr($error, -1);
		if (!in_array($last_char, array('.', '!', '?'))) 
			$error .= '.';		
		echo "$error\n";		
		if ( $this->is_debug() )
		{
			echo "\n";
			debug_print_backtrace();

			$info = [			
				"Request URI" => $this->request_uri(),
				"Server API" => PHP_SAPI,
				"Memory limit" => ini_get('memory_limit'),
				"Maximum POST size" => ini_get('post_max_size'),
				"PHP version" => PHP_VERSION,
				"WordPress version" => get_bloginfo('version'),
				"Plugin version" => USAM_VERSION,
			];
			echo "\n";
			foreach ($info as $info_name => $info_value) 
			{
				echo "$info_name: $info_value\n";
			}
		}
		$this->wpdb_end();
		$this->close_system_report( $error );	
		wp_logout();		
		exit;
	}
	
	function wp_error( $wp_error, $only_error_code = null ) 
	{
		$messages = array();
		foreach ($wp_error->get_error_codes() as $error_code) 
		{
			if ($only_error_code && $error_code != $only_error_code)
				continue;

			$wp_error_messages = implode(", ", $wp_error->get_error_messages($error_code));
			$wp_error_messages = strip_tags($wp_error_messages);
			$messages[] = sprintf("%s: %s", $error_code, $wp_error_messages);
		}
		$this->set_error(implode("; ", $messages), "WP Error");
	}
		
	function request_uri() 
	{
		$uri = 'http';
		if (@$_SERVER['HTTPS'] == 'on') $uri .= 's';
		$uri .= "://{$_SERVER['SERVER_NAME']}";
		if ($_SERVER['SERVER_PORT'] != 80) $uri .= ":{$_SERVER['SERVER_PORT']}";
		if (isset($_SERVER['REQUEST_URI'])) $uri .= $_SERVER['REQUEST_URI'];

		return $uri;
	}
	
	private function insert_system_report( $args = [] ) 
	{
		$args['type'] = '1c-'.$this->type;
		$this->system_report_id = usam_insert_system_report( $args );
	}
	
	function update_system_report( $update ) 
	{
		if ( $this->system_report_id ) 			
			usam_update_system_report( $this->system_report_id, $update);
	}
	
	function close_system_report( $description = '' ) 
	{
		if ( $this->system_report_id )
		{ 
			$results = [];
			if ( self::$is_error )
				$results = ['status' => 'error'];	
			else
			{
				if ( $this->handler )
					$results = $this->handler->get_results();
				$results['status'] = 'completed';
			}
			if ( isset($_GET['filename']) )
				$results['filename'] = $_GET['filename'];
			$results['type'] = '1c-'.$this->type;
			$results['end_date'] = date( "Y-m-d H:i:s" );
			$results['description'] = $description;
			usam_update_system_report( $this->system_report_id, $results);
		}
	}
	
	function set_transaction_mode() 
	{
		global $wpdb;	
		
		$this->disable_time_limit();

		register_shutdown_function(array($this, 'transaction_shutdown')); 

		$wpdb->show_errors(false); 

		self::$is_transaction = true;
		$wpdb->query("START TRANSACTION");
		$this->check_wpdb_error();
	}
	
	function check_wpdb_error() 
	{
		global $wpdb;
		if( !$wpdb->last_error ) 
			return;
		$this->set_error(sprintf("%s for query \"%s\"", $wpdb->last_error, $wpdb->last_query), "DB Error", true);
		$this->wpdb_end(false, true);
		exit;
	}		
	
	function wpdb_end( $is_commit = false, $no_check = false ) 
	{
		global $wpdb;	

		if ( self::$is_transaction == false )
			return;
		self::$is_transaction = false;

		$sql_query = !$is_commit ? "ROLLBACK" : "COMMIT";
		$wpdb->query($sql_query);

		if ( !$no_check ) 
			$this->check_wpdb_error();

		if ( $this->is_debug() ) 
			echo "\n" . strtolower($sql_query);
	}	
	
	function disable_time_limit()
	{
		$disabled_functions = explode(',', ini_get('disable_functions'));
		if (!in_array('set_time_limit', $disabled_functions)) 
			@set_time_limit(0);
	}
	
	function transaction_shutdown() 
	{
		$zip_paths = glob(USAM_EXCHANGE_DIR . $this->type."/*.zip");
		if ( $zip_paths )
		{
			foreach ($zip_paths as $zip_path) 
				@unlink($zip_path);	
		}	
		$error = error_get_last();
		$is_commit = $error['type'] > E_PARSE;
		$this->wpdb_end( $is_commit );
	}
	
//В случае успешного получения и записи заказов "1С:Предприятие" передает на сайт запрос вида 
	function mode_success( ) 
	{
		$args = ['order' => 'DESC', 'fields' => 'id', 'meta_query' => [['key' => 'exchange', 'value' => 0]], 'number' => $this->number, 'status' => 'all'];
		if (version_compare('2.09', $this->version_1c) == 0)
		{
			require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
			$documents_id = usam_get_documents( $args );			
			foreach ($documents_id as $id) 
			{
				usam_update_document_metadata($id, 'exchange', 1);
				usam_update_document_metadata($id, 'date_exchange', date("Y-m-d H:i:s"));	
			}
		}			  
		$orders_id = usam_get_orders( $args );
		foreach ($orders_id as $id) 
		{
			usam_update_order_metadata($id, 'exchange', 1);
			usam_update_order_metadata($id, 'date_exchange', date("Y-m-d H:i:s"));				
		}
		return 'success';
	}
	
	function mode_init() 
	{
		@exec("which unzip", $_, $status);
		$is_zip = @$status === 0 || class_exists('ZipArchive');
		
		$file_limits = array( usam_filesize_to_bytes(ini_get('post_max_size')), usam_filesize_to_bytes(ini_get('memory_limit')) );		
		@exec("grep ^MemFree: /proc/meminfo", $output, $status);
		if (@$status === 0 && $output) 
		{
			$output = preg_split("/\s+/", $output[0]);
			$file_limits[] = intval($output[1] * 1000 * 0.7);
		}		
		$file_limit = min($file_limits);		
		$update_system_report = ['operation' => 'init'];
		if ( !$is_zip ) 
		{
			$output = "zip=no\nfile_limit=$file_limit";
			$update_system_report['description'] = 'Требуется ZIP расширения PHP, чтобы использовать ZIP файлы из 1С. Загрузка идет без сжатия';
		}
		else
			$output = "zip=yes\nfile_limit=$file_limit";
		
		if( !empty($this->settings['schema_version']) )
			$output .= "\nsessid=".session_id()."\nversion=$this->version_1c";
			
		//$output = "\nsessid=".session_id()."\nversion=$this->version_1c"; // Отключено. Если СтрЧислоСтрок(ОтветСервера) <> 2 Тогда  Не удалось прочитать ответ сервера. Параметры обмена не получены	
		
		$this->update_system_report( $update_system_report );
		return $output;
	}
	
	function mode_file( ) 
	{
		$dir = USAM_EXCHANGE_DIR .$this->type;
		if( !is_dir($dir) )
		{			
			if ( !mkdir($dir, 0777, true) )
				$this->set_error( sprintf("Failed to create directories for file %s", $filename) );
		}	
		if ( isset($_GET['filename']) )
		{ 			
			$filename = $_GET['filename'];		
			$filepath = $dir.'/'.ltrim($filename, "./\\");			
			$dir = dirname($filepath);
			if( !is_dir($dir) )
			{
				if ( !mkdir($dir, 0777, true) )
					$this->set_error( "Не удалось создать каталог" );
			}			
			$input_file = fopen("php://input", 'r');			
			$temp_path = "$filepath~";			
			$temp_file = fopen($temp_path, 'w');
			stream_copy_to_stream($input_file, $temp_file);			
			if ( is_file($filepath))
			{
				$temp_header = file_get_contents($temp_path, false, null, 0, 32);
				if (strpos($temp_header, "<?xml ") !== false) 
					unlink($filepath);
			}	
			$temp_file = fopen($temp_path, 'r');
			$file = fopen($filepath, 'a');
			stream_copy_to_stream($temp_file, $file);
			
			fclose($temp_file); 		
			@unlink($temp_path);	
		}
		if ($this->type == 'sale') 
		{ 			
			$ext = pathinfo($filepath, PATHINFO_EXTENSION);
			if ( $ext == 'zip' )
			{
				$zip = new ZipArchive();
				$result = $zip->open( $filepath );				
				if ($result !== true) 
				{
					$this->set_error( sprintf("Failed open archive %s with error code %d", $filepath, $result) );
				}
				$zip->extractTo( $dir ) or $this->set_error(sprintf("Failed to extract from archive %s", $filepath));
				$zip->close() or $this->set_error(sprintf("Failed to close archive %s", $filepath));
				@unlink($filepath);
			/*	foreach ( glob("$dir/*.xml") as $filepath) // Ищет файлы в каталоге
				{						
					$this->import( $filepath );
				}*/
			//	rmdir($dir);
			}
			else
			{
				$this->import( $filepath );
			}
			
			/*
			$this->unpack_files( );		
			foreach ( glob("$dir/*.xml") as $filepath) // Ищет файлы в каталоге
			{
				$filename = basename($filepath); 						
				if ( stripos($filename, 'order') !== false )
				{						
					$this->import( $filename, 'orders' );
				}
			}*/
		}
		return 'success';
	}
	
	private function file_saving( $filepath ) 
	{
		if ( USAM_SAVE_IMPORT_FILES )
		{ 
			$dir = USAM_EXCHANGE_DIR .'archive';
			if( !is_dir($dir) )
			{			
				if ( !mkdir($dir, 0777, true) )
				{
					@unlink( $filepath );
					return;
				}
			}
			$info = pathinfo($filepath);			
			$newfilepath = $dir.'/'.$info['filename'].$this->system_report_id.'.'.$info['extension'];			
			rename($filepath, $newfilepath);
		}
		else
			@unlink( $filepath );
	}
	
	private function mode_info( ) 
	{
		$dom = new DOMDocument();
		$dom->loadXML("<?xml version='1.0' encoding='utf-8'?><Справочник></Справочник>");
		$xml = simplexml_import_dom($dom);		
		require_once( USAM_FILE_PATH . "/includes/exchange/1C/xml/".$this->type.".php" );	
        return $xml->asXML();
	}
		
	private function mode_import( ) 
	{
		if ( isset($_GET['filename']) )
		{
			$filename = $_GET['filename']; 
			if ($this->type == 'catalog') 
				$this->unpack_files( );						
			$this->import( USAM_EXCHANGE_DIR . $this->type."/$filename" );					
		}
		return 'success';
	}		
	
	private function unpack_files( ) 
	{
		$data_dir = USAM_EXCHANGE_DIR . $this->type;
		$zip_paths = glob("$data_dir/*.zip");
		if ( !$zip_paths )
			return;

		$command = sprintf("unzip -qqo -x %s -d %s", implode(' ', array_map('escapeshellarg', $zip_paths)), escapeshellarg($data_dir));
		@exec($command, $_, $status);
		
		if (@$status !== 0) 
		{
			foreach ($zip_paths as $zip_path)
			{ 			 
				$zip = new ZipArchive();
				$result = $zip->open( $zip_path );
				if ($result !== true) 
				{
					foreach ($zip_paths as $zip_path) 
						@unlink($zip_path);	
					$this->set_error( sprintf("Failed open archive %s with error code %d", $zip_path, $result) );
				}
				$zip->extractTo($data_dir) or $this->set_error(sprintf("Failed to extract from archive %s", $zip_path));
				$zip->close() or $this->set_error(sprintf("Failed to close archive %s", $zip_path));
			}
		}	
		foreach ($zip_paths as $zip_path) 
			@unlink($zip_path);			
	}		
	
	function mode_query( ) 
	{
		if ( usam_is_license_type('LITE') || usam_is_license_type('FREE') )
		{
			return 'купите лицензию';
		}		
		$this->disable_time_limit();
		$args = ['order' => 'DESC', 'number' => $this->number, 'exchange' => true, 'status__not_in' => ['incomplete_sale'], 'cache_results' => true, 'cache_meta' => true, 'cache_order_shippeds' => true, 'cache_order_payments' => true, 'cache_order_products' => true, 'conditions' => ['key' => 'number_products', 'value' => 0, 'compare' => '!=']]; 
		$orders = usam_get_orders( $args );
		echo '<?xml version="1.0" encoding="' . $this->charset . '"?>';
		?>
		<КоммерческаяИнформация xmlns="urn:1C.ru:commerceml_2" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ВерсияСхемы="<?php echo $this->version_1c; ?>" ДатаФормирования="<?php echo date_i18n("c") ?>">
		<?php
			if (version_compare('2.09', $this->version_1c) == 0)
			{
				$args = ['order' => 'DESC', 'type' => $this->type, 'number' => $this->number, 'cache_results' => false, 'cache_meta' => true, 'cache_bank_accounts' => true, 'meta_query' => [['key' => 'exchange', 'compare' => "NOT EXISTS"]],
		//'number' => 2,
				];  		
				require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
				$documents = usam_get_documents( $args );				
				
				require_once( USAM_FILE_PATH . "/includes/exchange/1C/xml/documents-{$this->version_1c}.php" );	
				if ( !empty($orders) )
					require_once( USAM_FILE_PATH . "/includes/exchange/1C/xml/orders-{$this->version_1c}.php" );				
				
				foreach ($documents as $document)
					usam_update_document_metadata($document->id, 'exchange', 0);
			}
			elseif ( !empty($orders) )
				require_once( USAM_FILE_PATH . "/includes/exchange/1C/xml/orders-{$this->version_1c}.php" );	
		?>
		</КоммерческаяИнформация>
		<?php
		foreach ($orders as $order) 
			usam_update_order_metadata($order->id, 'exchange', 0);
		return '';
	}	
		
	function mode_deactivate( ) 
	{
		if ( !empty($_GET["timestamp"]) )
		{
			global $wpdb;
			$timestamp = date('Y-m-d H:i:s', $_GET["timestamp"]);
			
			$query_vars = ['paged' => 1, 'post_type' => 'usam-product', 'post_status' => 'publish', 'productmeta_query' => ['relation' => 'OR', ['key' => '1c_unloading_date', 'compare' => '<', 'value' => $timestamp, 'type' => 'DATETIME'], ['key' => '1c_unloading_date', 'compare' => 'NOT EXISTS'] ]];
			$i = usam_get_total_products( $query_vars );
			$result = usam_create_system_process( sprintf(__("Обновление свойств у %s товаров", "usam" ), $i), ['update' => ['post_status' => 'draft'], 'args' => $query_vars], 'update_system_products_attribute', $i, 'update_system_products_attribute'.time() );
			wp_logout();
			return 'success';
		}
		else
			return "failure";
	}
	
	function mode_complete( ) 
	{
		wp_logout();
		return 'success';
	}
	
	private function import( $filepath ) 
	{
		global $usam_names, $usam_depth;		
				
		$filename = basename($filepath);
		if ( !file_exists($filepath)) 
			$this->set_error( sprintf(__("Файл %s не загружен в %s", "usam"), $filename, USAM_EXCHANGE_DIR . $this->type) );
		
		try 
		{ 			
			$fp = fopen($filepath, 'r') or $this->set_error(sprintf("Failed to open file %s", $filename));
			flock($fp, LOCK_EX) or $this->set_error(sprintf("Failed to lock file %s", $filename));
		}
		catch(Exception $e) 
		{ 
			$this->set_error(sprintf("Failed to open file %s", $filename));
			return false;
		}
		$namespace = preg_replace("/^([a-zA-Z]+).+/", '$1', $filename);
		if ( !in_array($namespace, ['import', 'offers', 'orders', 'documents', 'prices', 'rests', 'contragents', 'storages', 'priceLists', 'units', 'goods', 'propertiesGoods', 'propertiesOffers', 'groups']))
			$this->set_error(sprintf("Unknown import file type: %s", $namespace));

		$is_synchronization = $this->xml_parse_head( $fp );
		$usam_names = array();
		$usam_depth = -1;
		require_once sprintf(USAM_FILE_PATH . "/includes/exchange/1C/%s.php", $namespace);				
		$class = "USAM_{$namespace}_Element_Handler_".$this->version_1c;		
		if ( !class_exists($class) )
		{			
			$class = "USAM_{$namespace}_Element_Handler";		
			if ( !class_exists($class) )
				$this->set_error( sprintf("Класс %s не существует", $class) );	
		}
		
		$this->set_transaction_mode();
			
/*-----------отключить счетчики------------------*/		
		
		if ( in_array($namespace, ['import', 'offers', 'prices', 'rests', 'storages', 'priceLists', 'units', 'goods', 'propertiesGoods', 'propertiesOffers', 'groups']) )
			usam_start_import_products();
		else
			usam_update_object_count_status( false );		
/*-----------отключить счетчики------------------*/				
		
		$this->handler = new $class();
		$this->xml_parse($fp);		
				
/*-----------включить счетчики------------------*/
		
		if ( in_array($namespace, ['import', 'offers', 'prices', 'rests', 'storages', 'priceLists', 'units', 'goods', 'propertiesGoods', 'propertiesOffers', 'groups']) )
			usam_end_import_products();
		else
			usam_update_object_count_status( true );		

/*-----------включить счетчики------------------*/		
		
		flock($fp, LOCK_UN) or $this->set_error(sprintf("Failed to unlock file %s", $filename));
		fclose($fp) or $this->set_error(sprintf("Failed to close file %s", $filename));			
		$this->wpdb_end( true );		
		$this->file_saving( $filepath );
	}
		
	private function xml_parse_head( $fp ) 
	{
		$this->is_full = null;
		$synchronization = null;
		while (($buffer = fgets($fp)) !== false) 
		{ 
			if( strpos($buffer, " СинхронизацияТоваров=") !== false ) 
				$synchronization = true;
			if( strpos($buffer, " СодержитТолькоИзменения=") === false && strpos($buffer, "<СодержитТолькоИзменения>") === false ) 
				continue;
			$this->is_full = strpos($buffer, " СодержитТолькоИзменения=\"false\"") !== false || strpos($buffer, "<СодержитТолькоИзменения>false<") !== false;
			break;
		}
		$meta_data = stream_get_meta_data($fp);
		$filename = basename($meta_data['uri']);
		
		rewind($fp) or $this->set_error( sprintf("Failed to rewind on file %s", $filename) );
		return $synchronization;
	}
	
	function xml_parse( $fp ) 
	{
		$parser = xml_parser_create('UTF-8');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_element_handler($parser, [$this, 'xml_start_element_handler'], [$this, 'xml_end_element_handler']);
		xml_set_character_data_handler($parser, [$this, 'xml_character_data_handler']);			
		$meta_data = stream_get_meta_data($fp);
		$filename = basename($meta_data['uri']);
		while( !($is_final = feof($fp)) ) 
		{  			
			if( ($data = fread($fp, 4096)) === false ) 
				$this->set_error(sprintf("Failed to read from file %s", $filename));		
			if( !xml_parse($parser, $data, $is_final) )
			{
				$message = sprintf("%s in %s on line %d", xml_error_string(xml_get_error_code($parser)), $filename, xml_get_current_line_number($parser));
				$this->set_error($message, "XML Error");
			}			
		}				
		xml_parser_free($parser);		
	}
	
	function xml_start_element_handler($parser, $name, $attrs) 
	{
		global $usam_names, $usam_depth;
		static $element_number = 0;
		$usam_names[] = $name;
		$usam_depth++;			
		$this->handler->start_element_handler($this->is_full, $usam_names, $usam_depth, $name, $attrs);
		$element_number++;
		if ($element_number > 1000)
		{
			$element_number = 0;
			wp_cache_flush();
		}
	}
	
	function xml_end_element_handler($parser, $name) 
	{
		global $usam_names, $usam_depth;
		$this->handler->end_element_handler($this->is_full, $usam_names, $usam_depth, $name);
		array_pop($usam_names);
		$usam_depth--;				
	}
	
	function xml_character_data_handler($parser, $data) 
	{ 
		global $usam_names, $usam_depth;	
		$this->handler->character_data_handler($this->is_full, $usam_names, $usam_depth, $usam_names[$usam_depth], $data);		
	}
	
	function get_document_name( $document_type )
	{
		$documents = ['order' => 'Заказ товара', 'invoice' => 'Счет на оплату', 'shipped' => 'Отпуск товара', 'invoice-texture' => 'Счет-фактура', 'refund' => 'Возврат товара', 'products-commission' => 'Передача товара на комиссию', 'Возврат комиссионного товара', 'Отчет о продажах комиссионного товара', 'Выплата наличных денег', 'Возврат наличных денег', 'Выплата безналичных денег', 'Возврат безналичных денег', 'Передача прав', 'act' => 'Акт'];
		if ( isset($documents[$document_type]) )
			$name = $documents[$document_type];
		else
			$name = 'Прочие';
		return $name;
	}
}
$_1c = USAM_1C_API::get_instance();
$_1c->exchange();
	

function usam_check_wpdb_error() 
{
	$_1c = USAM_1C_API::get_instance();
	$_1c->check_wpdb_error();	
}

function usam_check_wp_error( $wp_error )
{
	if ( is_wp_error($wp_error) )
	{
		$_1c = USAM_1C_API::get_instance();
		$_1c->wp_error( $wp_error );			
	}
}

function usam_error( $error )
{ 	
	$_1c = USAM_1C_API::get_instance();
	$_1c->set_error( $error );	
}