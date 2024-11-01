<?php
// Класс обратных запросов ajax 
abstract class USAM_Callback 
{	
	protected $action = '';
	protected $error_code = false;
	protected $error = '';
	protected $query = '';
	protected $verify_nonce = true;
	protected $request_data = [];
	protected $sendback;	
	
	protected function set_log_file()
	{		
		usam_log_file( $this->error );
		$this->error = '';
	}
		
	/**
	 * Обработчик для всех запросов.	
	 */
	public function handler()
	{		
		$this->action = $_REQUEST[$this->query];	

		$this->sendback = wp_get_referer();
		$this->sendback = remove_query_arg([$this->query, '_wpnonce', 'nonce'], $this->sendback );
		if ( $this->verify_nonce )
		{		
			$result = $this->verify_nonce( $this->action.'_nonce' );
			if ( ! is_wp_error( $result ) )
				$result = $this->fire_callback( );	
			else
				$this->sendback = add_query_arg( 'verify_nonce', 1, $this->sendback );
		}
		else
			$result = $this->fire_callback( );

		$this->set_log_file();
		if ( $this->error_code )
		{	// ошибки возникшие во время исполнения запросов			
			$this->sendback = add_query_arg( 'error', $this->error_code, $this->sendback );		
		} 			
		wp_redirect( $this->sendback );
		exit();		
	}	
	
		
	/**
	 * AJAX обработчик для всех запросов Ajax. Эта функция автоматизирует проверку одноразового номера и выдает ответ JSON.
	 */
	public function handler_ajax()
	{				
		usam_set_nocache_constants();
		nocache_headers();
			
		$url = wp_get_referer();
		$_SERVER['REQUEST_URI'] = remove_query_arg(['action', 'nonce', 'usam_ajax_action'], $url );		
		unset($_REQUEST['usam_ajax']);
		if ( !empty($_REQUEST['screen_id']) )
		{
			$screen_id = sanitize_title($_REQUEST['screen_id']);		
			set_current_screen($screen_id);
		}	
		if ( empty($_REQUEST[$this->query]) )
			return false;
		$this->action = str_replace( '-', '_', $_REQUEST[$this->query] );
		unset($_REQUEST[$this->query]);
		if ( $this->verify_nonce )
		{			
			$result = $this->verify_nonce( 'usam_ajax_' . $this->action );			
			if ( ! is_wp_error( $result ) )
				$result = $this->fire_callback( );
		}	
		else
			$result = $this->fire_callback( );		

		if ( is_wp_error($result) ) 
			$result = ['errors' => $result->get_error_messages() ];
		echo json_encode( $result );
		exit;		
	}	
	
	/**
	 * Проверка запроса на безопасность
	 */
	protected function verify_nonce( $usam_action ) 
	{			
		$nonce = ''; // nonce могут быть переданы с именем usam_nonce или _wpnonce
		if ( isset($_REQUEST['nonce'] ) )
			$nonce = $_REQUEST['nonce'];
		elseif ( isset($_REQUEST['_wpnonce'] ) )
			$nonce = $_REQUEST['_wpnonce'];
		else
			return new WP_Error( 'usam_invalid_nonce', sprintf(__('Ключ для проверки безопасности для вызова %s не найден.', 'usam'), $this->action) );		
		// проверить nonce		
		if ( !wp_verify_nonce( $nonce, $usam_action ) )
			return new WP_Error( 'usam_invalid_nonce', __('Проверка не пройдена. Возможна ваша сессия истекла. Обновите страницу и попробуйте еще раз.', 'usam') );	
		
		return true;
	}

	/**
	 * Проверка AJAX обратного вызова и вызвать его, если он существует.
	 */
	protected function fire_callback( )
	{			
		$callback = "controller_{$this->action}";
		if ( method_exists( $this, $callback )) 
			$result = $this->$callback();
		elseif ( is_callable("usam_{$this->action}") )
			$result = call_user_func("usam_{$this->action}");
		else
			$result = new WP_Error( 'usam_invalid_ajax_callback', sprintf(__('Неверный AJAX обратного вызова %s.', 'usam'), $this->action) );
		return $result;
	}	
	
	/**
	 * функция масштаба изображения, динамически изменяет размер изображения если не существует изображение такого размера.
	 */
	public function controller_scale_image() 
	{	
		$output_url = USAM_CORE_THEME_URL."images/no-image-uploaded.png";
		if ( !isset($_REQUEST['attachment_id']) || !is_numeric( $_REQUEST['attachment_id'] ) )
			return $output_url;
			
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attachment_id = absint($_REQUEST['attachment_id']);
		$width = absint($_REQUEST['width']);
		$height = absint($_REQUEST['height']);
		$intermediate_size = '';
		if ( ($width >= 10 && $height >= 10) && ($width <= 10240 && $height <= 10240) )
		{
			$intermediate_size = "usam-{$width}x{$height}";
			$generate_thumbnail = true;	
		} 
		else 
		{
			if ( isset($_REQUEST['intermediate_size'] ) )
				$intermediate_size = sanitize_text_field($_REQUEST['intermediate_size']);
			$generate_thumbnail = false;
		}		
		// Если ID вложения больше 0, а ширина и высота больше или равна 10, и меньше, чем или равно 1024				
		if ( $attachment_id && $intermediate_size != '' ) 
		{
			$uploads = wp_upload_dir();
			$metadata = wp_get_attachment_metadata( $attachment_id );			
			$file_path = get_attached_file( $attachment_id );
			
			if ( !isset($metadata['sizes']) )
				$metadata['sizes'] = array();					
						
			if( count($metadata['sizes']) && !empty($metadata['sizes'][$intermediate_size]) ) 
			{		
				if( file_exists($file_path) ) 
				{
					$original_modification_time = filemtime( $file_path );
					$image = image_get_intermediate_size($attachment_id, [$width, $height]);		
					$cache_modification_time = filemtime($uploads['basedir']."/".$image['path'] );			
					if ( $original_modification_time < $cache_modification_time ) 
						$generate_thumbnail = false;					
				}
			}
			if ( $generate_thumbnail )
			{	
				$crop = apply_filters( 'usam_scale_image_cropped', get_option('thumbnail_crop', true) );	
				$intermediate_size_data = image_make_intermediate_size( $file_path, $width, $height, $crop );
				if ( $intermediate_size_data )
				{
					$metadata['sizes'][$intermediate_size] = $intermediate_size_data;
					wp_update_attachment_metadata( $attachment_id, $metadata );
				}
				else
				{
					usam_log_file( sprintf( __( "Не удалось создать размер изображения %s. Возможно оригинальное изображение слишком маленькое чтобы создать новый размер.", 'usam'), $file_path) );
					$intermediate_size = 'full';
				}
			}
			$output_url = wp_get_attachment_image_url( $attachment_id, $intermediate_size );			
			if( is_ssl() )
				$output_url = str_replace( "http://", "https://", $output_url );
		} 			
		wp_redirect( $output_url );
		exit;
	}
	
	function controller_number_of_unread_menager_chat_messages()
	{
		if ( usam_check_current_user_role( 'administrator' ) || usam_check_current_user_role('shop_manager') )
			return usam_get_number_new_message_dialogues();	
	}
	
	function controller_update_consultant_status()
	{
		$contact_id = usam_get_contact_id( );	
		$contact = usam_get_contact( $contact_id );
		if ( !empty($contact) && $contact['contact_source'] == 'employee' )
			usam_update_contact_metadata( $contact_id, 'online_consultant', $_POST['status'] === 'true'?1:0 );
	}
}