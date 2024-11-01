<?php
/**
 * Обработка событий
 */		

function usam_get_system_process( $args = [] ) 
{
	global $wpdb;
	$option = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'usam_set_events' ) );
	if ( empty($option))
		$events = [];
	else
		$events = maybe_unserialize( $option );	
	
	if( $args )
	{
		foreach( $events as $id => $event )
		{  
			$events[$id]['priority'] = (int)$events[$id]['priority'];
			if ( isset($args['id_like']) )
			{
				if ( stripos($id, $args['id_like'] ) === false )
					unset($events[$id]);
			}
			elseif ( isset($args['id']) )
			{
				if ( $id != $args['id'] )
					unset($events[$id]);
			}
			if ( isset($args['status']) )
			{
				if ( $args['status'] != $event['status'] )
					unset($events[$id]);
			}
		}
		if ( !empty($args['orderby']) )
		{
			$orderby = $args['orderby'];
			$order = isset($args['order'])&&$args['order']=='ASC'?'ASC':'DESC';		
			$comparison = new USAM_Comparison_Object( $orderby, $order );
			uasort( $events, [$comparison, 'compare'] );
		}
	}
	return $events;
}

function usam_check_process_is_running( $event_id ) 
{
	static $events = null;
	if ( $events === null )
		$events = usam_get_system_process( );		
	if ( isset($events[$event_id]) )
		$result = true;
	else
		$result = false;
	return $result;
}

function usam_update_system_process( $id, $update ) 
{
	$events = usam_get_system_process( );	
	if ( isset($events[$id]) )
	{		
		$events[$id] = array_merge( $events[$id], $update );	
		$result = update_option( 'usam_set_events', $events );	
	}
	else
		$result = false;
	return $result;
}

function usam_delete_system_process( $id ) 
{
	$events = usam_get_system_process( );
	if ( isset($events[$id]) )
	{
		unset($events[$id]);
		update_option( 'usam_set_events', $events );		
	}	
}

function usam_create_system_process( $title, $data, $callback, $count, $id, $priority = 5 ) 
{		
	$events = usam_get_system_process( );		
	$events[$id] = ['priority' => $priority, 'title' => $title, 'data' => $data, 'callback' => $callback, 'count' => (int)$count, 'done' => 0, 'launch_number' => 0, 'status' => 'wait', 'date_insert' => date("Y-m-d H:i:s")];	
	$result = update_option( 'usam_set_events', $events );	
	if( ! wp_next_scheduled( 'usam_start_process' ) )
		wp_schedule_single_event(time(), 'usam_start_process');			
	return $result;
}

function usam_start_system_process( $title, $data, $callback, $count ) 
{		
	$process = ['priority' => 0, 'title' => $title, 'data' => $data, 'callback' => $callback, 'count' => (int)$count, 'done' => 0, 'launch_number' => 0, 'status' => 'wait', 'date_insert' => date("Y-m-d H:i:s")];
	$events = new USAM_System_Process();
	$events->start_process( $process );
}

function usam_start_processes() 
{	
	$events = new USAM_System_Process();
	$events->start_events();	
}
add_action( 'usam_start_process', 'usam_start_processes', 10, 1 );

class USAM_System_Process
{		
	private $number = 1000;
	private $event = array();	
	private $f_log = false;	
	
	/*function file_fopen( $file_name = '' )
	{			
		$file_path  = USAM_UPLOAD_DIR.'Log/'.current_time("m-Y").'_'.$file_name.'.txt';	
		$this->f_log = fopen($file_path,"a");		
	}
	
	private function file_fwrite( $file_name, $text ) 
	{							
		if ( empty($text) )
			return false;
		
		$this->fopen( $file_name );
		
		$text = "[".current_time("d-m-Y H:i:s")."] ".$text;		
		$text .= "\r\n";
		return fwrite($this->f_log, $text );
	}
		
	public function file_fclose() 
	{
		if ( $this->f_log !== false )
		{			
			fclose($this->f_log);
		}
	}	*/
	
	public function start_events()
	{			
		if ( defined('USAM_SYSTEM_PROCESS') && USAM_SYSTEM_PROCESS ) 
			return false;
		
		$events = usam_get_system_process(['orderby' => 'priority', 'order' => 'DESC']);
		if ( !empty($events) )
		{	
			define('USAM_SYSTEM_PROCESS', true );	
			
			set_time_limit(1800);
			register_shutdown_function(['USAM_System_Process', 'shutdown']);
			if ( function_exists( 'ini_get' ) )
				$max_execution_time = ini_get('max_execution_time');
			if ( empty($max_execution_time) )
				$max_execution_time = 300;			
			$time = time();
			$priority = 0;
			foreach ( $events as $key => $event )
			{  				
				if ( $event['status'] === 'pause' )
					continue;				
				
				if( $event['status'] === false || $event['status'] === 'wait' || (empty($event['start_cycle']) || strtotime($event['start_cycle'])+$max_execution_time < $time) )
				{
					if ( $priority === 0 )
						$priority = $event['priority'];
					elseif ( $priority != $event['priority'] )
						continue;					
					
					$this->create_process( $event );
					usam_update_system_process( $key, $this->event );					
					
					usam_log_file( __('Процесс запущен', 'usam').' [ '.$key.' ]    '.$this->event['title'], 'system_process', false );
									
					$result = $this->fire_callback();		
					if ( !is_wp_error($result) )
					{ 			
						$this->event['done'] = $result['done']+$this->event['done'];
						if ( isset($result['launch_number']) )
							$this->event['launch_number'] = $result['launch_number'];						
						if ( $this->event['done'] >= $this->event['count'] || $result['done'] == 0 )
						{								
							do_action('usam_process_completed', $key, $this->event );
							
							usam_log_file( __('Процесс завершен', 'usam').' [ '.$key.' ]    '.$event['title'], 'system_process', false );				
							
							usam_delete_system_process( $key );			
							unset($events[$key]);	
						}			
						else
						{											
							$events_db = usam_get_system_process( );							
							if ( isset($events_db[$key]) )
							{
								if ( isset($result['data']) )
									$this->event['data'] = $result['data'];								
								$this->event['status'] = $events_db[$key]['status'] == 'pause' ? 'pause': 'wait';
								usam_update_system_process( $key, $this->event );
								usam_log_file( __('Выполнен цикл', 'usam').' [ '.$key.' ]    '.$event['title'], 'system_process', false );
							}
						}													
					}	
					else
					{		
						usam_log_file( __('Вызвана неверная функция.', 'usam') );
						usam_delete_system_process( $key );	
						unset($events[$key]);
					} 
				}	
			}
		}						
	}
	
	public function create_process( $event )
	{
		$this->event = $event;						
		$this->event['launch_number']++;	
		$this->event['status'] = 'start';	
		$this->event['start_cycle'] = date("Y-m-d H:i:s");
	}
	
	public function start_process( $event )
	{
		$this->create_process( $event );
		$result = $this->fire_callback();
		return $result;
	}
	
	protected function is_process_completed( $done )
	{
		if ( $done+$this->event['done'] >= $this->event['count'] || $done == 0 )
			return true;
		else
			return false;
	}
	
	public static function shutdown( )
	{
	//	$error = error_get_last();
	//	if (!empty($error['type']) && $error['type'] === E_ERROR) 
		
		$events = usam_get_system_process();
		if ( !empty($events) )
		{	 
			$update = false;
			foreach( $events as $key => $event )
			{
				if ( $events[$key]['status'] == 'start' )
				{
					$events[$key]['status'] = 'wait';	
					$update = true;
				}
			}
			if ( $update )
				update_option( 'usam_set_events', $events );
			if( ! wp_next_scheduled('usam_start_process') )
				wp_schedule_single_event(time(), 'usam_start_process');			
		}		
	}
	
	protected function fire_callback()
	{					
		if ( is_callable($this->event['callback']) )
			$result = call_user_func_array($this->event['callback'], [$this->event['data'], $this->number, &$this->event]);
		elseif ( is_string($this->event['callback']) )
		{					
			$callback = "controller_".$this->event['callback'];		
			if ( method_exists( $this, $callback )) 
				$result = $this->$callback();	
			else
				$result = new WP_Error( 'usam_invalid_events_callback', sprintf( __( "Неверный вызов функции: %s.", 'usam'), $callback) );				
		}
		else
			$result = new WP_Error( 'usam_invalid_events_callback', __( "Неверный вызов функции", 'usam') );
		return $result;
	}	
	
	protected function controller_pricelist_creation( )
	{			
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
		if ($this->event['launch_number'] == 1 )
			usam_update_exchange_rule( $this->event['data'], ['start_date' => date('Y-m-d H:i:s'), 'end_date' => '']);		
		require_once( USAM_FILE_PATH . '/includes/product/price_list.class.php' );
		$export = new USAM_Price_List( $this->event['data'] );
		$done = $export->write_data( $this->event['launch_number'] );
		
		if ( $this->is_process_completed( $done ) )							
			usam_update_exchange_rule( $this->event['data'], ['end_date' => date('Y-m-d H:i:s')]);				
		return ['done' => $done];		
	}	
	
	protected function controller_preparation_exchange_data( )
	{			
		$exchange = new USAM_Exchange( $this->event['data'] );
		$exchange->preparation_exchange_data();		
		return ['done' => 1, 'data' => $this->event['data']];		
	}
	
	protected function controller_start_exchange( )
	{
		$exchange = new USAM_Exchange( $this->event['data'] );	
		$done = $exchange->start_exchange( $this->event['launch_number'] );		
		return ['done' => $done];		
	}
	
	protected function controller_after_exchange( )
	{
		$exchange = new USAM_Exchange( $this->event['data'] );
		$done = $exchange->after_exchange( $this->event['launch_number'] );			
		return ['done' => $done];		
	}	
	
	//Проверка электронных адресов
	function controller_contacts_verify_email( )
	{		
		global $wpdb;
		require_once( USAM_FILE_PATH . '/includes/crm/communication_error.class.php' );
		$communications = $wpdb->get_results("SELECT meta.meta_value, meta.meta_id FROM `".USAM_TABLE_CONTACT_META."` AS meta LEFT OUTER JOIN `".USAM_TABLE_COMMUNICATION_ERRORS."` AS com_error ON (meta.meta_value=com_error.communication) WHERE meta.meta_key LIKE '%email%' AND meta.meta_value!='' AND com_error.id IS NULL AND meta.meta_id>".$this->event['data']." AND status!= 0 ORDER BY meta.meta_id ASC LIMIT 10");
		
		$i = 0;	
		foreach ( $communications as $communication )
		{					
			$result = usam_email_verification_mail( $communication->meta_value );						
			if ( $result !== true && $result !== false )
			{					
				usam_insert_communication_error( array( 'communication' => $communication->meta_value, 'communication_type' => 'email', 'reason' => $result ) );
			}
			$this->event['data'] = $communication->meta_id;
			$i++;
			sleep(30);
		}
		return array( 'done' => $i, 'data' => $this->event['data'] );		
	}	
	
	function controller_companies_verify_email( )
	{		
		global $wpdb;
		require_once( USAM_FILE_PATH . '/includes/crm/communication_error.class.php' );
		$communications = $wpdb->get_results("SELECT meta.meta_value, meta.meta_id FROM `".USAM_TABLE_COMPANY_META."` AS meta LEFT OUTER JOIN `".USAM_TABLE_COMMUNICATION_ERRORS."` AS com_error ON (meta.meta_value=com_error.communication) WHERE meta.meta_key LIKE '%email%' AND meta.meta_value!='' AND com_error.id IS NULL AND meta.meta_id>".$this->event['data']." AND com_error.status!= 0 ORDER BY meta.meta_id ASC LIMIT 10");		
		$i = 0;	
		foreach ( $communications as $communication )
		{					
			$result = usam_email_verification_mail( $communication->meta_value );						
			if ( $result !== true && $result !== false )
			{					
				usam_insert_communication_error( array( 'communication' => $communication->meta_value, 'communication_type' => 'email', 'reason' => $result ) );
			}
			$this->event['data'] = $communication->meta_id;
			$i++;
			sleep(30);
		}
		return ['done' => $i, 'data' => $this->event['data']];		
	}
	
	function controller_verify_emails( )
	{		
		require_once( USAM_FILE_PATH . '/includes/crm/communication_error.class.php' );	
		$i = 0;
		foreach ( $this->event['data']['emails'] as $key => $email )
		{					
			$result = usam_email_verification_mail( $email );
			if ( $result !== true && $result !== false )
				usam_insert_communication_error(['communication' => $email, 'communication_type' => 'email', 'reason' => $result]);
			unset($this->event['data']['emails'][$key]);
			$i++;
			sleep(30);
		}				
		return ['done' => $i, 'data' => $this->event['data']];		
	}
	
	// Пересчет цен
	function controller_recalculate_price_products( )
	{	
		$args = $this->event['data'];		
		$args['post_status'] = 'any';	
		$args['paged'] = $this->event['launch_number'];	
		$args['posts_per_page'] = $this->number;	
		$args['cache_results'] = true;	
		$args['update_post_term_cache'] = true;
		$args['prices_cache'] = true;
		$args['stocks_cache'] = false;	
		
		$products = usam_get_products( $args );	
		$product_ids = array();
		foreach ( $products as $product )
		{
			$product_ids[] = $product->ID;
		}		
		usam_cache_current_product_discount( $product_ids ); 		
		foreach ( $product_ids as $product_id )
		{
			usam_edit_product_prices( $product_id );	
			usam_clean_product_cache( $product_id );
		}		
		return ['done' => count($product_ids)];		
	}
	
	function controller_delete_post()
	{	
		global $wpdb;
		remove_all_actions('pre_get_posts');
		$post_status = array_values(get_post_stati());
		$args = $this->event['data'];		
		$args['paged'] = 1;	//иначе пропускает посты
		$args['posts_per_page'] = $this->number;	
		$args['cache_results'] = false;	
		$args['update_post_term_cache'] = false;
		$args['update_post_meta_cache'] = false;		
		$args['prices_cache'] = false;
		$args['stocks_cache'] = false;
		if ( !empty($args['post__in']) )
		{
			$args['post__in'] = array_map('intval', $args['post__in']);
			$args['post_type'] = 'any';		
			$args['post_status'] = $post_status;
		}	
		$posts = usam_get_posts( $args );
		if ( !empty($posts) )
		{
			remove_action('before_delete_post',  '_usam_delete_product');	
			$ids = [];
			$all_ids = [];
			wp_defer_term_counting( true );
			wp_defer_comment_counting( true );
			
			foreach ($posts as $post)
			{				
				if ( $post->post_type == 'usam-product' )
					$ids[] = (int)$post->ID;	
				$all_ids[] = (int)$post->ID;					
			}		
			if ( usam_is_multisite() )
			{				
				if ( is_main_site() )
				{  // Если главный сайт
					$sites = get_sites(['site__not_in' => [0,1]]);	
					if ( $sites )
					{
						foreach( $sites as $site )
						{						
							switch_to_blog( $site->blog_id );							
							$posts_multisite = $wpdb->get_results("SELECT * FROM ".usam_get_table_db('linking_posts_multisite')." WHERE ID IN (".implode(',', $all_ids).")" );
							if ( $posts_multisite )
							{
								wp_defer_term_counting( true );
								foreach( $posts_multisite as $post )
									wp_delete_post( $post->multisite_post_id, true );	
								wp_defer_term_counting( false );									
							}							
						}
						switch_to_blog( 1 );
						wp_defer_term_counting( true );
					}
				}
				else
					$wpdb->query("DELETE FROM ".usam_get_table_db('linking_posts_multisite')." WHERE multisite_post_id IN (".implode(',', $all_ids).")" );
			}					
			if ( $ids )
			{							
				$post_parents = usam_get_posts(['post_type' => 'any', 'post_status' => $post_status, 'post_parent__in' => $ids]);
				if ( !empty($post_parents) )	
				{ 
					foreach ($post_parents as $post) 
					{
						if ( $post->post_type == 'usam-product' || $post->post_type == 'revision' )
							$ids[] = $post->ID;
						elseif ( $post->post_type == 'attachment' )
						{
						//	wp_delete_attachment( $post->ID, true );
							$ids[] = $post->ID;
							$meta         = wp_get_attachment_metadata( $post->ID );
							$backup_sizes = get_post_meta( $post->ID, '_wp_attachment_backup_sizes', true );
							$file         = get_attached_file( $post->ID );
							wp_delete_attachment_files( $post->ID, $meta, $backup_sizes, $file );
						}	
						else
							wp_delete_post( $post->ID, true );	
						clean_post_cache( $post );
					}
				}
				if ( $ids )
				{											
					$wpdb->query( "DELETE FROM ".usam_get_table_db('product_attribute')." WHERE product_id IN (".implode(',', $ids).")" );
					$wpdb->query( "DELETE FROM ".usam_get_table_db('posts_search')." WHERE post_search_id IN (".implode(',', $ids).")" );
					$wpdb->query( "DELETE FROM ".usam_get_table_db('product_filters')." WHERE product_id IN (".implode(',', $ids).")" );
					$comment_ids = $wpdb->get_col( "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID IN (".implode(',', $ids).")" );
					foreach ( $comment_ids as $comment_id ) {
						wp_delete_comment( $comment_id, true );
					}			
				//	$wpdb->query( "DELETE FROM ".$wpdb->comments." WHERE comment_post_ID IN (".implode(',', $ids).")" );
					if ( !usam_is_multisite() || is_main_site() )
					{
						$wpdb->query( "DELETE FROM ".USAM_TABLE_POST_META." WHERE post_id IN (".implode(',', $ids).")" );							
						$wpdb->query( "DELETE FROM ".USAM_TABLE_ASSOCIATED_PRODUCTS." WHERE product_id IN (".implode(',', $ids).") OR associated_id IN (".implode(',', $ids).")" );	
						$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS." WHERE product_id IN (".implode(',', $ids).")" );	
						$wpdb->query( "DELETE FROM ".USAM_TABLE_STOCK_BALANCES." WHERE product_id IN (".implode(',', $ids).")" );
						$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_META." WHERE product_id IN (".implode(',', $ids).")" );
						$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_PRICE." WHERE product_id IN (".implode(',', $ids).")" );	
					}
					else
						$wpdb->query( "DELETE FROM ".usam_get_table_db('linking_posts_multisite')." WHERE multisite_post_id IN (".implode(',', $ids).")" );	
					$wpdb->query( "DELETE FROM ".$wpdb->postmeta." WHERE post_id IN (".implode(',', $ids).")" );							
					$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->term_relationships WHERE object_id = %d", $ids ) );	
					$wpdb->query( "DELETE FROM ".$wpdb->posts." WHERE ID IN (".implode(',', $ids).")" );			
				}				
			}
			foreach ($posts as $post)
			{				
				if ( $post->post_type == 'usam-product' )
					clean_post_cache( $post );
				elseif ( $post->post_type == 'attachment' )
				{
				//	wp_delete_attachment( $post->ID, true );
					$meta         = wp_get_attachment_metadata( $post->ID );
					$backup_sizes = get_post_meta( $post->ID, '_wp_attachment_backup_sizes', true );
					$file         = get_attached_file( $post->ID );
					wp_delete_attachment_files( $post->ID, $meta, $backup_sizes, $file );
					clean_post_cache( $post );
				}
				else
					wp_delete_post( $post->ID, true );					
			}	
			wp_defer_term_counting( false );
			wp_defer_comment_counting( false );			
		}
		return ['done' => count($posts)];
	}
	
	function controller_check_files_database( )
	{	
		global $wpdb;
		$done = 0;
		if ( $this->event['data'] )
		{
			$item = current($this->event['data']);	
			$upload_dir = wp_upload_dir();	
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$files = list_files( $item['dir'] );	
			if ( $files )
			{
				foreach ($files as $key => $file)
				{			
					unset($files[$key]);	
					if ( $item['number'] > $key - 1 )
						continue;
					
					$done++;	
					$this->event['data'][0]['number'] = $key;
					$path_parts = pathinfo($file);						
					$parts = explode('-', $path_parts['filename']);
					if ( $parts )
					{
						$end_part = end($parts);										
						$parts = explode('x', $end_part);
						if ( $parts && count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1]) )
							continue;
					}			
					$filepath = str_replace($upload_dir['basedir'].'/', '', $file);							
					if ( !$wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = '$filepath' LIMIT 1" ) )
						unlink($upload_dir['basedir'].'/'.$filepath);
					
					if ( $done > $this->number )
						break;									
				}						
			} 	
			if ( empty($files) )
			{
				unset($this->event['data'][0]);
				$this->event['data'] = array_values($this->event['data']);
			}
			if ( empty($this->event['data']) )
				$done = 0;	
			sleep(3);
		}
		return ['done' => $done, 'data' => $this->event['data']];		
	}

	function controller_conversion_to_webp( )
	{	
		$args = $this->event['data'];
		$args['paged'] = $this->event['launch_number'];
		$args['posts_per_page'] = 200;		
		$args['cache_results'] = false;	
		$args['update_post_term_cache'] = false;
		$args['update_post_meta_cache'] = true;	
		$args['post_type'] = 'attachment';	
		$args['post_status'] = 'any';
		$args['post_parent__not_in'] = 0;	
		$posts = usam_get_posts( $args );
		if ( !empty($posts) )
		{
			foreach ($posts as $post)
			{
				if ( !$post->post_parent )
					continue;
	
				$old_filepath = get_attached_file( $post->ID ); 
				if ( file_exists($old_filepath) )
				{				
					$path_parts = pathinfo($old_filepath);
					$directory = $path_parts['dirname']; 
					$old_filename = $path_parts['basename']; 				 
					if ( $path_parts['extension'] == 'jpg' || $path_parts['extension'] == 'jpeg' || $path_parts['extension'] == 'png' )
					{			
						$new_filepath = substr_replace($old_filepath , 'webp', strrpos($old_filepath , '.') +1);
						if ( !usam_create_image_webp($old_filepath, $new_filepath, 100) )
							continue;
															
						update_attached_file( $post->ID, $new_filepath );						
						$data = wp_get_attachment_metadata( $post->ID );
						$new_filename = pathinfo($directory . '/' . $data['file'], PATHINFO_FILENAME).'.webp';
						$data['file'] = str_replace($old_filename, $new_filename, $data['file']);
						if ( isset($data['original_image']) )
							$data['original_image'] = $new_filename;	
						foreach( $data['sizes'] as $size => $meta_size ) 
						{
							$meta_old_filepath = $directory . '/' . $meta_size['file'];
							$meta_new_filename = pathinfo($meta_old_filepath, PATHINFO_FILENAME).'.webp';	
							usam_create_image_webp($meta_old_filepath, $directory . '/' . $meta_new_filename, 100);
							if ( file_exists($directory.'/'.$meta_new_filename) )
							{
								$data['sizes'][$size]['file'] = $meta_new_filename;
								$data['sizes'][$size]['mime-type'] = 'image/webp';								
							}
						}  
						$data['filesize'] = filesize( $new_filepath );
						wp_update_attachment_metadata( $post->ID, $data );
						$guid = str_replace( $old_filename, $new_filename, $post->guid );
						wp_update_post(['ID' => $post->ID, 'post_mime_type' => 'image/webp', 'guid' => $guid]);
						
					}
					/*elseif ( $path_parts['extension'] == 'webp' )
					{
						$data = wp_get_attachment_metadata( $post->ID );
						$data['file'] = str_replace($old_filename, pathinfo($directory . '/' . $data['file'], PATHINFO_FILENAME).'.webp', $data['file']);
						foreach( $data['sizes'] as $size => $meta_size ) 
						{
							$meta_old_filepath = $directory . '/' . $meta_size['file'];							
							$data['sizes'][$size]['file'] = pathinfo($meta_old_filepath, PATHINFO_FILENAME).'.webp';					
							$data['sizes'][$size]['mime-type'] = 'image/webp';
						} 					
						wp_update_attachment_metadata( $post->ID, $data ); 
					}*/
				}		
				clean_post_cache( $post );					
			}			
		}
		return ['done' => count($posts)];		
	}
	
	function controller_regenerate_thumbnails( )
	{		
		$args = $this->event['data'];	
		if ( !empty($this->event['data']['regenerate']) )
		{
			$args_regenerate = $args['regenerate'];		
			unset($args['regenerate']);
		}		
		$args['paged'] = $this->event['launch_number'];
		$args['posts_per_page'] = 200;		
		$args['cache_results'] = false;	
		$args['update_post_term_cache'] = false;
		$args['update_post_meta_cache'] = true;	
		$args['post_type'] = 'attachment';	
		$args['post_status'] = 'any';
		$args['post_parent__not_in'] = 0;			
		$posts = usam_get_posts( $args );		
		if ( !empty($posts) )
		{
			require_once(USAM_FILE_PATH.'/includes/media/regenerate-thumbnails.class.php');
			foreach ($posts as $post)
			{				
				$regenerator = USAM_Regenerate_Thumbnails::get_instance( $post->ID );	
				if ( is_wp_error( $regenerator ) )
					continue;
				
				$result = $regenerator->regenerate( $args_regenerate );	
				clean_post_cache( $post ); 
				sleep(1);
			}			
		}
		return ['done' => count($posts)];
	}
	
	function controller_fix_thumbnail_sizes( )
	{	
		$args = $this->event['data'];		
		$args['paged'] = $this->event['launch_number'];
		$args['posts_per_page'] = 200;		
		$args['cache_results'] = true;	
		$args['update_post_term_cache'] = true;
		$args['update_post_meta_cache'] = true;	
		$args['post_type'] = 'attachment';	
		$args['post_status'] = 'any';
		$posts = usam_get_posts( $args );	
		if ( !empty($posts) )
		{
			foreach ($posts as $post)
			{
				$old_filepath = get_attached_file( $post->ID ); 
				if ( file_exists($old_filepath) )
				{				
					$attached_file = get_post_meta($post->ID, '_wp_attached_file', true);
					if ( !$attached_file )
						continue;
										
					$path_parts = pathinfo($old_filepath);
					$data = wp_get_attachment_metadata( $post->ID );
					
					if ( !empty($data['file']) && $data['file'] == $attached_file )
						continue;
											
					$data['file'] = $attached_file;				
					foreach( $data['sizes'] as $size => $meta_size ) 
					{						
						$data['sizes'][$size]['file'] = $path_parts['filename'].'-'.$meta_size['width'].'х'.$meta_size['height'].'.'.$path_parts['extension'];	
						if ( $path_parts['extension'] == 'webp' )
							$data['sizes'][$size]['mime-type'] = 'image/webp';
					} 					
					wp_update_attachment_metadata( $post->ID, $data ); 					
				}				
			}			
		}
		return ['done' => count($posts)];		
	}
	
	function controller_change_attribute_type(  )
	{
		$attribute_id = $this->event['data']['attribute_id'];
		$term = get_term_by( 'id', $attribute_id, 'usam-product_attributes' );
		$old_type = $this->event['data']['old_type'];
		$type = $this->event['data']['type'];	
							
		$args = ['fields' => 'ids', 'post_status' => 'any', 'paged' => $this->event['launch_number'], 'posts_per_page' => $this->number, 'prices_cache' => false, 'stocks_cache' => false, 'update_post_term_cache' => false, 'product_attribute_cache' => true, 'attributes_query' => [['key' => $term->slug, 'compare' => 'EXISTS']]];		
		$products_ids = usam_get_products( $args );
		foreach( $products_ids as $product_id )
		{			
			switch ( $type ) 
			{
				case 'C' ://Флажок один	
					$product_attribute = usam_get_product_attribute( $product_id, $term->slug );	
					if ( is_string($product_attribute) )
						usam_update_product_attribute( $product_id, $term->slug, usam_string_to_float($product_attribute) );
				break;		
				case 'S' :			
				case 'N' : 					
					switch ( $old_type ) 
					{						
						default:
						case 'T' :	
						case 'O' :								
							$attribute_values = usam_get_attribute_values( $term->term_id );	
							$value = usam_get_product_attribute( $product_id, $term->slug );							
							$ok = false;
							foreach( $attribute_values as $option )
							{
								if ( $option->id == $value )	
								{
									usam_update_product_attribute( $product_id, $term->slug, $option->id );
									$ok = true;
									break;
								}
							}		
							if ( !$ok )
							{
								if( $type == 'N' )
									$value =  usam_string_to_float($value);	
								$option_id = usam_insert_product_attribute_variant(['value' => $value, 'attribute_id' => $term->term_id]);
								usam_update_product_attribute( $product_id, $term->slug, $option_id );		
							}
						break;
					}
				break;	
			}
			usam_clean_product_cache( $product_id );
		}
		$number = count($products_ids);	
		$done = $number + $this->event['done'];	
		if ( $done >= $this->event['count'] || $number == 0)
		{				
			$filter = usam_get_term_metadata($attribute_id, 'filter');		
			if ( $filter )
				usam_calculate_product_filters( $attribute_id );			
		}
		return ['done' => $number];	
	}	
	
	function controller_change_attribute_search(  )
	{	
		$args = ['fields' => 'ids', 'post_status' => 'any', 'paged' => $this->event['launch_number'], 'posts_per_page' => $this->number, 'prices_cache' => false, 'stocks_cache' => false, 'update_post_term_cache' => false, 'product_attribute_cache' => true, 'attributes_query' => [['key' => $this->event['data']['slug'], 'compare' => 'EXISTS']]];		
		$products_ids = usam_get_products( $args );	
		foreach( $products_ids as $product_id )
		{		
			$product_attributes = [];
			foreach( usam_get_product_attribute( $product_id ) as $attribute )
			{
				$term = get_term_by( 'slug', $attribute->meta_key, 'usam-product_attributes' );
				$product_attributes[$term->term_id][] = $attribute->meta_value;
			}
			$_product = new USAM_Product( $product_id );
			$_product->calculate_product_searches( $product_attributes );
			usam_clean_product_cache( $product_id );
		}
		$number = count($products_ids);	
		$done = $number + $this->event['done'];	
		return ['done' => $number];		
	}	
		
	function controller_update_products_attribute( )
	{
		$args = [					
			'fields' => 'ids',			
			'post_status' => ['publish', 'private', 'draft', 'pending'],
			'update_post_term_cache' => true,
			'prices_cache' => false, 
			'stocks_cache' => false, 
			'cache_results' => true,
			'product_attribute_cache' => true,			
			'posts_per_page' => $this->number,	
			'paged' => $this->event['launch_number']			
		];
		if ( !empty($this->event['data']['args']) )
			$args = array_merge($args, $this->event['data']['args']);	
		if ( !empty($this->event['data']['update']['post_status']) )
		{
			$args['prices_cache'] = true;
			$args['discount_cache'] = true;
		}
		$products_ids = usam_get_products( $args );				
		foreach( $products_ids as $product_id )
		{					
			$product = new USAM_Product( $product_id );	
			$product->calculate_product_attributes( $this->event['data']['update'], true );
			usam_clean_product_cache( $product_id );
		}		
		return ['done' => count($products_ids)];	
	}	
	
	function controller_update_products_terms( )
	{
		$args = [			
			'post_status' => ['publish', 'private', 'draft', 'pending'],
			'update_post_term_cache' => true,
			'prices_cache' => true, 
			'discount_cache' => true, 			
			'stocks_cache' => false, 
			'cache_results' => true,		
			'posts_per_page' => $this->number,	
			'paged' => $this->event['launch_number']			
		];			
		if ( !empty($this->event['data']['args']) )
			$args = array_merge($args, $this->event['data']['args']);			
		$products = usam_get_products( $args );		
		$done = 0;
		wp_defer_term_counting( true );
		foreach( $products as $key => $product )
		{
			if ( $this->event['data']['operation'] == 'del' )			
			{
				foreach( $this->event['data']['terms'] as $taxonomy => $term_id )					
					wp_remove_object_terms( $product->ID, absint($term_id), 'usam-'.$taxonomy );	
			}
			else
			{				
				foreach( $this->event['data']['terms'] as $taxonomy => $term_id )
					wp_set_object_terms( $product->ID, absint($term_id), 'usam-'.$taxonomy, (bool)$this->event['data']['operation'] );
			}
			usam_clean_product_cache( $product->ID );
			$done++;
			unset($products[$key]);
		}	
		wp_defer_term_counting( false );				
		return ['done' => $done];	
	}
	
	function controller_update_system_products_attribute( )
	{	
		$args = [	
			'post_status' => ['publish', 'private', 'draft', 'pending'],
			'product_meta_cache' => true,					
			'update_post_term_cache' => true,
			'prices_cache' => false, 
			'stocks_cache' => false, 
			'cache_results' => true,
			'posts_per_page' => $this->number,
			'paged' => $this->event['launch_number'],	
		];	
		if ( !empty($this->event['data']['args']['post_status']) && !empty($this->event['data']['update']['post_status']) && (is_string($this->event['data']['args']['post_status']) && $this->event['data']['args']['post_status'] !== $this->event['data']['update']['post_status'] || in_array($this->event['data']['update']['post_status'], $this->event['data']['args']['post_status'])) )
			$args['paged'] = 1;
		
		if ( !empty($this->event['data']['args']) )
			$args = array_merge($args, $this->event['data']['args']);	
		$done = usam_update_system_products_attribute( $args, $this->event['data']['update'] );		
		return ['done' => $done];	
	}
		
	function controller_update_contacts_properties( )
	{					
		$args = array_merge( $this->event['data']['args'], ['cache_results' => true, 'meta_cache' => true, 'number' => $this->number, 'paged' => $this->event['launch_number']] );	
		$contacts = usam_get_contacts( $args );				
		usam_update_object_count_status( false );
		foreach ( $contacts as $contact )
		{
			$update = $this->event['data']['update'];		
			if ( !empty($update['appeal']) )
			{
				$names = [];
				foreach(['lastname', 'firstname', 'patronymic'] as $key ) 
					$names[$key] = usam_get_contact_metadata( $contact->id, $key );
				$update['appeal'] = usam_get_formatting_contact_name( $names, $update['appeal'] );
			}	
			usam_update_contact( $contact->id, $update );				
		}	
		usam_update_object_count_status( true );
		return ['done' => count($contacts)];	
	}	
	
	function controller_update_orders_properties( )
	{			
		$args = array_merge($this->event['data']['args'], ['status__not_in' => '', 'cache_results' => true, 'meta_cache' => true, 'number' => $this->number, 'paged' => $this->event['launch_number']]);
		$orders = usam_get_orders( $args ); 
		usam_update_object_count_status( false );
		foreach ( $orders as $order )
		{			
			if ( usam_check_document_access( $order, 'order', 'edit', $this->event['data']['contact_id'] ) )
			{ 
				usam_update_order( $order->id, $this->event['data']['update'] );		
				if ( isset($this->event['data']['update']['exchange']) )
				{
					if ( $this->event['data']['update']['exchange'] )
					{		
						usam_update_order_metadata($order->id, 'exchange', 1);
						usam_update_order_metadata($order->id, 'date_exchange', date("Y-m-d H:i:s"));
					}
					else
						usam_update_order_metadata($order->id, 'exchange', 0);
				}
			}
		}
		usam_update_object_count_status( true );
		return ['done' => count($orders)];	
	}	
	
	function controller_update_coupons_properties( )
	{			
		require_once( USAM_FILE_PATH . '/includes/basket/coupons_query.class.php' );
		$coupons = usam_get_coupons(['cache_results' => true, 'number' => $this->number, 'paged' => $this->event['launch_number']]);				
		foreach ( $coupons as $coupon )
			usam_update_coupon( $coupon->id, $this->event['data'] );	
		return ['done' => count($coupons)];	
	}	

	function controller_update_storages_properties( )
	{			
		$update = $this->event['data'];	
		$storages = usam_get_storages(['cache_results' => true, 'number' => $this->number, 'paged' => $this->event['launch_number'], 'owner' => 'all', 'active' => 'all']);		
		foreach ( $storages as $storage )
			usam_update_storage( $storage->id, $update );
		return ['done' => count($storages)];	
	}	
	
	function controller_update_companies_properties( )
	{				
		$update = $this->event['data'];		
		$companies = usam_get_companies(['cache_results' => true, 'meta_cache' => true, 'number' => $this->number, 'paged' => $this->event['launch_number']]);				
		foreach ( $companies as $company )
		{			
			usam_update_company( $company->id, $update );				
		}
		return ['done' => count($companies)];	
	}	
	
	function controller_calculate_product_filters( )
	{ 
		$parameters = $this->event['data'];		
		$default = ['fields' => 'ids', 'post_status' => 'publish', 'update_post_term_cache' => true, 'cache_results' => true, 'prices_cache' => false, 'stocks_cache' => false, 'posts_per_page' => $this->number, 'paged' => $this->event['launch_number']];	
		$args = array_merge( $parameters, $default );			
		$products_ids = usam_get_products( $args );			
		usam_cache_products_filters( $products_ids );			
		$terms = get_terms(['taxonomy' => 'usam-product_attributes', 'hide_empty' => 0, 'orderby' => 'id', 'usam_meta_query' => [['key' => 'filter','value' => 1, 'compare' => '=']]]);	
		foreach ( $products_ids as $product_id )
		{	
			$product_attributes = [];
			foreach ( $terms as $term )
			{
				$field_type = usam_get_term_metadata($term->term_id, 'field_type');	
				if ( $field_type == 'COLOR_SEVERAL' || $field_type == 'M')
				{
					$metas = usam_get_product_attribute( $product_id, $term->slug, false );
					if ( !empty($metas) )
						foreach( $metas as $meta )
						{
							$product_attributes[$term->term_id][] = $meta->meta_value;
						}
				}
				else
				{
					if ( $term->slug == 'brand' || $term->slug == 'contractor' )	
						$value = usam_get_product_property($product_id, $term->slug);	
					else
						$value = usam_get_product_attribute( $product_id, $term->slug );	
					if ( $value !== false && $value !== '' )
						$product_attributes[$term->term_id][] = $value;
				}
			}			
			$product = new USAM_Product( $product_id );				
			$product->calculate_product_filters( $product_attributes );
			usam_clean_product_cache( $product_id );
		}	
		return ['done' => count($products_ids)];	
	}
			
	// Пересчитать остатки у заданных товаров
	function controller_recalculate_stock_products( )
	{				
		$args['paged'] = $this->event['launch_number'];		
		$args['posts_per_page'] = $this->number;	
		$args['cache_results'] = true;		
		$args['update_post_term_cache'] = true;
		$args['prices_cache'] = false;		
		$args['fields'] = 'ids';			
		$products = usam_get_products( $args );				
		foreach ( $products as $product_id )
		{				
			usam_recalculate_stock_product( $product_id );
			usam_clean_product_cache( $product_id );
		}					
		return ['done' => count($products)];	
	}
	
	function controller_download_email_pop3_server( )
	{		
		usam_send_mails( );	
		$mailboxes = usam_get_mailboxes(['cache_results' => true, 'cache_mailbox_users' => true, 'cache_meta' => true]);	
		foreach ( $mailboxes as $mailbox ) 
		{
			usam_download_email_pop3_server( $mailbox->id );			
		}		
		return ['done' => 0, 'data' => 0];	
	}
	
	// Обновление товаров в контакте
	function controller_vk_update_all_products( ) 
	{					
		$count = 20;					
		$profile = usam_get_social_network_profile( $this->event['data'] );
		$args = ['post_status' => 'publish', 'paged' => $this->event['launch_number'], 'posts_per_page' => $count, 'stocks_cache' => false, 'update_post_term_cache' => true, 'productmeta_query' => [['key' => 'vk_market_id_'.$profile['code'], 'type' => 'numeric', 'value' => '0', 'compare' => '!=']] ];	
		$products = usam_get_products( $args );	
		if ( !empty($products) )
		{	
			require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
			$vkontakte = new USAM_VKontakte_API( $this->event['data'] );	
			foreach ( $products as $product ) 	
				$vkontakte->edit_product( $product );	
		}				
		return ['done' => count($products)];		
	}
	
	function controller_calculation_accumulative_discount_customer( ) 
	{	
		$args = array( 'fields' => 'ID', 'number' => $this->number, 'paged' => $this->event['launch_number'], 'orderby' => 'ID' );
		$user_ids = get_users( $args );
			
		foreach ( $user_ids as $user_id )
		{ 
			$discount = usam_get_accumulative_discount_customer( 'price', $user_id );		
			update_user_meta( $user_id, 'usam_accumulative_discount', $discount );	
		}
		return ['done' => count($user_ids)];		
	}
	
	function controller_calculate_increase_sales_product()
	{
		require_once( USAM_FILE_PATH . '/includes/product/increase_sales_product.class.php' );
				
		$args = ['fields' => 'ids',	'post_status' => 'publish', 'product_meta_cache' => true, 'update_post_term_cache' => false, 'stocks_cache' => false, 'prices_cache' => false, 'cache_results' => true, 'posts_per_page' => $this->number, 'paged' => $this->event['launch_number']];			
		if ( !empty($this->event['data']['args']) )
			$args = array_merge($args, $this->event['data']['args']);	
		$products_ids = usam_get_products( $args );	
		usam_get_associated_products( $products_ids );
		if ( !empty($this->event['data']['rules']) )
			$rules = $this->event['data']['rules'];
		else
			$rules = usam_get_crosssell_conditions();
		foreach ( $products_ids as $product_id )
		{						
			$delete = true;
			foreach ( $rules as $rule )
			{				
				$cross_sells = new USAM_Increase_Sales_Product( $product_id );
				if ( $cross_sells->cross_sell( $rule ) )
					$delete = false;
			}
			if ( $delete && empty($this->event['data']['rules']) )
				usam_delete_associated_products( $product_id, 'crosssell' );
			
			usam_update_product_meta( $product_id, 'increase_sales_time', time() );
			usam_clean_product_cache( $product_id );
		}
		return ['done' => count($products_ids)];	
	}	
	
	function controller_update_company_data( )
	{
		$args = ['fields' => 'id', 'meta_cache' => true, 'posts_per_page' => $this->number, 'paged' => $this->event['launch_number'] ];		
		$companies = usam_get_companies( $args );	
		foreach ( $companies as $company_id )
		{
			$inn = usam_get_company_metadata($company_id, 'inn');
			if ( $inn )
			{
				$data = usam_find_company_in_directory(['search' => $inn]);					
				foreach ( $data as $meta_key => $meta_value )
				{
					if ( $meta_value )
						usam_update_company_metadata($company_id, $meta_key, $meta_value);					
				}
			}
		}
		return ['done' => count($companies)];	
	}
//Подбор товаров в интернете
	function controller_internet_product_search( )
	{		
		require_once( USAM_FILE_PATH . '/includes/product/products_on_internet.class.php' );
		$args = $this->event['data'];
		
		$args['paged'] = $this->event['launch_number'];		
		$args['posts_per_page'] = 1;	
		$args['cache_results'] = false;	
		$args['post_status'] = 'publish';		
		$args['update_post_term_cache'] = false;
		$args['stocks_cache'] = false;
		$args['prices_cache'] = false;
		$args['fields'] = 'ids';			
		$products = usam_get_products( $args );				
		foreach ( $products as $product_id )
		{				
			usam_internet_product_search( $product_id );
			usam_clean_product_cache( $product_id );
		}					
		return ['done' => count($products), 'data' => $args];	
	}
	
	function controller_price_change( )
	{		
		$args = $this->event['data']['query'];		
		$args['paged'] = $this->event['launch_number'];	
		$args['posts_per_page'] = $this->number;	
		$args['cache_results'] = true;	
		$args['update_post_term_cache'] = true;
		$args['prices_cache'] = true;
		$args['stocks_cache'] = false;
		
		$type_price = $this->event['data']['type_price'];
		
		$products = usam_get_products( $args );	
		$product_ids = array();
		foreach ( $products as $product )
		{
			$product_ids[] = $product->ID;
		}		
		usam_cache_current_product_discount( $product_ids ); 		
		foreach ( $product_ids as $product_id )
		{
			$prices = [];
			$price = usam_get_product_price($product_id, $type_price);	
			$markup = $price*$this->event['data']['markup']/100;
			if ( $this->event['data']['operation'] == '+' )
				$price = $price+$markup;
			else
				$price = $price-$markup;
			$prices['price_'.$type_price] = $price;			
			$old_price = usam_get_product_old_price($product_id, $type_price);
			if ( $old_price )
			{
				$markup = $old_price*$this->event['data']['markup']/100;
				if ( $this->event['data']['operation'] == '+' )
					$old_price = $old_price+$markup;
				else
					$old_price = $old_price-$markup;
			}
			$prices['old_price_'.$type_price] = $old_price;			
			usam_edit_product_prices( $product_id, $prices );	
			usam_clean_product_cache( $product_id );			
		}		
		return array( 'done' => count($product_ids) );	
	}
	
	function controller_match_locations( )
	{			
		$merchant_instance = usam_get_shipping_class( $this->event['data']['id'] );	
		if ( $merchant_instance->match_locations( $this->event['launch_number'] ) )
			$number = 1;	
		else
			$number = 0;
		return ['done' => $number];	
	}
	
	function controller_delivery_warehouses( )
	{				
		$merchant_instance = usam_get_shipping_class( $this->event['data']['id'] );	
		if ( $merchant_instance->set_delivery_warehouses( $this->event['launch_number'] ) )
			$number = 1;		
		else
			$number = 0;
		return array( 'done' => $number );	
	}	
	
	function controller_create_cards( )
	{		
		$args['paged'] = $this->event['launch_number'];		
		$args['number'] = $this->number;	
		$args['fields'] = 'ID';			
		$args['cards'] = false;	
		$users = get_users( $args );
		foreach ( $users as $user_id )
		{		
			$code = usam_generate_bonus_card();
			usam_insert_bonus_card(['status' => 'active', 'user_id' => $user_id, 'code' => $code]);		
		}						
		return array( 'done' => count($users) );			
	}	
	
	function controller_create_customer_accounts( )
	{		
		require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php'  );
		$args['paged'] = $this->event['launch_number'];		
		$args['number'] = $this->number;	
		$args['fields'] = 'ID';		
		$args['accounts'] = false;			
		$users = get_users( $args );
		foreach ( $users as $user_id )
		{		
			usam_insert_customer_account(['status' => 'active', 'user_id' => $user_id]);
		}					
		return array( 'done' => count($users) );			
	}	
		
	function controller_add_bonuses( )
	{			
		$args['paged'] = $this->event['launch_number'];		
		$args['number'] = $this->number;	
		$args['fields'] = 'code';			
		require_once( USAM_FILE_PATH . '/includes/customer/bonus_cards_query.class.php' );
		$bonus_cards = usam_get_bonus_cards( $args );
		$properties = $this->event['data'];	
		foreach ( $bonus_cards as $bonus_card )
		{		
			$properties['code'] = $bonus_card;
			usam_insert_bonus( $properties );
		}
		return ['done' => count($bonus_cards)];			
	}	

	function controller_parsing_site_competitor( )
	{			
		require_once( USAM_FILE_PATH . '/includes/parser/spider_site_partner.class.php' );		
		$webspy = new USAM_Spider_Site_Partner();		
		$webspy->get( $this->event['data']['id'] );
		if ( $webspy->start() )
			$number = 0;
		else
			$number = 1;		
		return ['done' => $number];
	}	
	
	function controller_update_product_parsing_competitor( )
	{		
		require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );
		$site = usam_get_parsing_site( $this->event['data']['id'] );	
		if ( empty($site['domain']) )
			return ['done' => 0, 'data' => $this->event['data']];
		$args = ['date_query' => [['before' => date('Y-m-d H:i:s', strtotime('-1 days')), 'inclusive' => true, 'column' => 'date_update']], 'cache_results' => true, 'paged' => $this->event['launch_number'], 'number' => 50, 'conditions' => [['key' => 'url', 'value' => $site['domain'], 'compare' => 'LIKE']]];	
		$products = usam_get_products_competitors( $args );			
		if ( !empty($products) )
		{
			$products_update = usam_get_parsing_site_metadata( $site['id'], 'products_update');
			require_once( USAM_FILE_PATH . '/includes/parser/competitors-prices-update-parser.class.php' );
			foreach ( $products as $product )
			{				
				$webspy = new USAM_Competitors_Prices_Update_Parser( $site, (array)$product );
				$webspy->check_product();
				
				++$products_update;
				usam_update_parsing_site_metadata( $site['id'], 'products_update', $products_update );
				$bypass_speed = usam_get_parsing_site_metadata( $site['id'], 'bypass_speed' );
				$bypass_speed = $bypass_speed?$bypass_speed*1000000:1000000;
				$bypass_speed = $bypass_speed < 10000 ? 10000:$bypass_speed;	
				usleep($bypass_speed);		
			}
		}
		return ['done' => count($products), 'data' => $this->event['data']];		
	}		
	
	function controller_parsing_site_supplier( )
	{	
		require_once( USAM_FILE_PATH . '/includes/parser/spider_site_supplier.class.php' );
		$webspy = new USAM_Spider_Site_Supplier();		
		$webspy->get( $this->event['data']['id'] );
		if ( $webspy->start() )
			$number = 0;
		else
			$number = 1;
		return ['done' => $number];
	}
	
	function controller_update_product_parsing_supplier( )
	{		
		$site = usam_get_parsing_site( $this->event['data']['id'] ); 
		if ( empty($site['domain']) )
			return ['done' => 0, 'data' => $this->event['data']];	
				
		$args = ['post_status' => ['publish', 'private', 'draft', 'pending'], 'productmeta_query' => [['key' => 'webspy_link', 'value' => $site['domain'], 'compare' => 'LIKE'], ['relation' => 'OR', ['key' => 'date_externalproduct', 'value' => date('Y-m-d H:i:s', strtotime('-1 days')), 'compare' => '<' ], ['key' => 'date_externalproduct', 'compare' => "NOT EXISTS"]]]];					
		$args['paged'] = $this->event['launch_number'];				
		$args['number'] = 50;	
		$args['fields'] = 'ids';	
		$args['stocks_cache'] = false;
		$products_ids = usam_get_products( $args ); 
		if ( !empty($products_ids) )
		{
			$products_update = usam_get_parsing_site_metadata( $site['id'], 'products_update');
			require_once( USAM_FILE_PATH . '/includes/parser/product-update-parser.class.php' );
			usam_cache_current_product_discount( $products_ids );
			foreach ( $products_ids as $product_id )
			{	  
				$webspy = new USAM_Product_Update_Parser( $product_id, $site );
				$webspy->check_product();
				
				++$products_update;
				usam_update_parsing_site_metadata( $site['id'], 'products_update', $products_update );
				$bypass_speed = usam_get_parsing_site_metadata( $site['id'], 'bypass_speed' );
				$bypass_speed = $bypass_speed?$bypass_speed*1000000:1000000;
				$bypass_speed = $bypass_speed < 10000 ? 10000:$bypass_speed;	
				usleep($bypass_speed);					
			}
		}
		return ['done' => count($products_ids), 'data' => $this->event['data']];		
	}	
	
	function controller_loading_locations( )
	{			
		require_once( USAM_FILE_PATH . '/includes/exchange/location_importer.class.php' );
		$importer = new USAM_Location_Importer( $this->event['data'] );
		$method = $this->event['data']['source'].'_import';
		$number = 0;
		if ( method_exists($importer, $method) )
		{			
			$this->event['data'] = $importer->$method( );		
			if ( $this->event['data'] )
				$number = 1;
		}
		return ['done' => $number, 'data' => $this->event['data']];			
	}	
	
	function controller_delete_orders( )
	{					
		$args = $this->event['data'];
		$args['number'] = $this->number;
		$number = usam_delete_orders($args, true);		
		return ['done' => $number];			
	}
	
	function controller_delete_empty_contacts( )
	{					
		$conditions = [['key' => 'appeal', 'value' => '', 'compare' => '='], ['key' => 'number_orders', 'value' => '', 'compare' => '=']];
		$args = ['fields' => 'id', 'meta_cache' => true, 'number' => $this->number, 'paged' => $this->event['launch_number'], 'user_id' => 0, 'company_id' => 0, 'conditions' => $conditions];	
		$contacts = usam_get_contacts( $args );	
		foreach( $contacts as $contact_id )
		{
			$metas = usam_get_contact_metadata( $contact_id );
			foreach( $metas as $k => $meta )
			{
				if( $meta->meta_key == 'location' || $meta->meta_value === '' )
					unset($metas[$k]);
			}	
			if( empty($metas) )
			{
				$contact = new USAM_Contact( $contact_id );	
				$contact->set(['status' => 'temporary']);	
				$contact->save();
			}
		}
		return ['done' => count($contacts)];			
	}

	
	function controller_stock_management_data( )
	{				
		global $wpdb;
		$args['paged'] = $this->event['launch_number'];			
		$args['cache_results'] = false;	
		$args['post_status'] = 'publish';
		$args['number'] = $this->number;		
		$args['update_post_term_cache'] = false;
		$args['prices_cache'] = false;
		$args['fields'] = 'ids';			
		$products = usam_get_products( $args );	
		if ( !empty($products) )
		{
			require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
			$products_order = usam_get_products_order_query(['order_status' => 'closed', 'year' => date('Y'), 'monthnum' => date('m'), 'day' => date('d'), 'include' => $products]);	
			$products_quantity = array();
			foreach ( $products_order as $product )		
			{
				if ( isset($quantity[$product->product_id]) )
					$products_quantity[$product->product_id] += $product->quantity; 
				else
					$products_quantity[$product->product_id] = $product->quantity; 
			}		
			$storages = usam_get_storages( );	
			$date = date('Y-m-d');
			foreach ( $products as $product_id )
			{				
				$quantity_sold = isset($products_quantity[$product_id])?$products_quantity[$product_id]:0;
				foreach ( $storages as $storage )
				{
					$stock = usam_get_product_stock( $product_id, $storage->meta_key );		
					$wpdb->insert( USAM_TABLE_STOCK_MANAGEMENT_DATA, array('storage_id' => $storage->id, 'product_id' => $product_id, 'stock' => $stock, 'quantity_sold' => $quantity_sold, 'date_insert' => $date) );			
				}
				$stock = usam_get_product_stock( $product_id, "stock" );
				$wpdb->insert( USAM_TABLE_DATA_ORDER_PRODUCTS, array('product_id' => $product_id, 'stock' => $stock, 'quantity_sold' => $quantity_sold, 'date_insert' => $date) );
				usam_clean_product_cache( $product_id );
			}
		}
		return ['done' => count($products)];	
	}
	
	function controller_import_product_mysql( )
	{
		global $db_export;
		$settings_mysql = get_option( 'usam_exchange_mysql_settings', array() );			
		if ( !empty($settings_mysql) )			
		{			
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			
			$db_export = new wpdb( $settings_mysql['user'], $settings_mysql['pass'], $settings_mysql['db'], $settings_mysql['host'] );	
			
			$post_id = implode( "', '",  $this->event['data'] );
			$sql = "SELECT * FROM {$wpdb->posts} WHERE ID IN ('". $post_id ."') LIMIT $this->number";
			$table_data = $db_export->get_results( $sql );
			
			$i = 0;
			foreach ( $table_data as $post ) 
			{					
				$i++;
				$new_post_type = $post->post_type;
				$post_content = str_replace( "'", "''", $post->post_content );
				$post_content_filtered = str_replace( "'", "''", $post->post_content_filtered );
				$post_excerpt = str_replace( "'", "''", $post->post_excerpt );
				$post_title = str_replace( "'", "''", $post->post_title );
				$post_name = str_replace( "'", "''", $post->post_name );
				$comment_status = str_replace( "'", "''", $post->comment_status );
				$ping_status = str_replace( "'", "''", $post->ping_status );

				$terms = array();
				$terms_data = $db_export->get_results( "SELECT term_id, name FROM $wpdb->terms" );				
				foreach ( $terms_data as $term )
					$terms[$term->term_id] = $term->name;
				
				$meta = array();
				$post_meta_infos = $db_export->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ) );				
				if ( count( $post_meta_infos ) ) 
				{							
					foreach ( $post_meta_infos as $meta_info )
					{						
						$meta_key = $meta_info->meta_key;
						$meta_value = addslashes( $meta_info->meta_value );						
						switch( $meta_key )
						{
							case '_edit_lock':		
							case '_edit_last':		
								$save_action = false;
							break;	
							case '_thumbnail_id':									
								$thumbnail_id = $meta_value;								
								$save_action = false;
							break;																					
							case '_usam_product_metadata':
								$meta_value = maybe_unserialize( $meta_info->meta_value );								
								$save_action = true;
							break;
							default:
								if ( stripos($meta_key, '_usam_') !== false) 
									$save_action = true;
								else
									$save_action = false;
							break;
						}						
						if ( $save_action )
						{
							$meta_key = str_replace("_usam_", "", $meta_key);
							$meta[$meta_key] = $meta_value;
						}
					}					
				}					
				$product = array(
					'post_status'           => 'draft',
					'post_type'             => $new_post_type,
					'ping_status'           => $ping_status,
					'post_parent'           => $post->post_parent,
					'menu_order'            => $post->menu_order,
					'to_ping'               => $post->to_ping,
					'pinged'                => $post->pinged,
					'post_excerpt'          => $post_excerpt,
					'post_title'            => $post_title,
					'post_content'          => $post_content,
					'post_content_filtered' => $post_content_filtered,
					'post_mime_type'        => $post->post_mime_type,
					'meta'                  => $meta,
				);					
				$_product = new USAM_Product( $product );		
				$new_post_id = $_product->insert_product();
				
				$url = $db_export->get_var( "SELECT guid FROM {$wpdb->posts} WHERE ID = '$meta_value'" );
				$attach_id = media_sideload_image( $url, 0, '', 'id' );
				$attach_id = $this->set_image( $thumbnail_id, $new_post_id );
				set_post_thumbnail( $new_post_id, $attach_id );		
				
				$id_image = $db_export->get_col( $wpdb->prepare("SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_parent` = '%d' AND `post_type` = 'attachment' AND `ID` != '$thumbnail_id'",$post->ID));
				foreach ( $id_image as $id )
				{	
					$attach_id = $this->set_image( $id, $new_post_id );	
				}
				if ( !empty($_GET["usam-category"]))
				{					
					$category = sanitize_title($_GET["usam-category"]);
					wp_set_object_terms( $new_post_id, $category, "usam-category", true );
				}
				//usam_duplicate_children( $post->ID, $new_post_id );// Н
			}
		}	
		return ['done' => count($products)];			
	}
}
?>