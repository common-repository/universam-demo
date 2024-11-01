<?php
class USAM_Tracker 
{
	public static function init() 
	{	
		if ( get_option( 'usam_allow_tracking', 0 ) == 1 )
		{
			if ( defined( 'DOING_CRON' ) )
				add_action( 'usam_tracker_send_event', array( __CLASS__, 'send_tracking_data' ) );
					
			$install_date = get_option( 'usam_install_date' );
			if ( empty($install_date) )
			{
				add_option( 'usam_install_date', date( "Y-m-d H:i:s" ), '', 'yes' );				
			}
			else
			{
				$time = strtotime( "+7 day", strtotime($install_date) );
				if ( time() < $time )
				{
					add_action( 'admin_footer', array( __CLASS__, 'send_collected_links' ), 101 );
					add_action( 'wp_footer', array( __CLASS__, 'send_collected_links' ), 101 );						
				}
			}
		}				
		if ( usam_is_license_type('FREE') )
			add_action( 'admin_footer', array(__CLASS__, 'confirm_deactivation_box') );	
	}
	
	public static function send_collected_links( )
	{		
		$link = stripslashes($_SERVER['REQUEST_URI']);
		$api = new USAM_Service_API();
		$result = $api->collected_links( $link );		
	}
	
	public static function confirm_deactivation_box()
	{		
		global $current_screen;
		if ( $current_screen->id == 'plugins' )
		{
			$html = '<div class="modal-body deactivation_plugin">';
			$html .= '<div class="modal-scroll">';
			$html .= '<h3>'.__('Техническая помощь support@wp-universam.ru', 'usam').'</h3>';		
			$html .= '<p class="description">'. __('Если у вас что-то не работает, сообщите нам.', 'usam').'</p>';		
			$options = self::confirm_deactivation_options();
			foreach ( $options as $key => $field ) 
			{
				$html .= '<p><input type="radio" id="' . $key . '" name="deactivation-reason" value="' . $key . '"><label for="' . $key . '">' . $field[ 'label' ] . '</label></p>';
				$html .= '<p>';
				if ( $field[ 'placeholder' ] )
					$html .= '<textarea name="deactivation-reason-desc" cols="40" placeholder="' . $field[ 'placeholder' ] . '"></textarea>';
				$html .= '</p>';
			}
			$html .= '<div class="warning">'. __('Выберите причину...', 'usam').'</div>';
			$html .= '</div>';
			$html .= '<div class="modal__buttons">';			
			$html .= '&nbsp;<a href="" id="usam_submit_deactivate" class="button-primary">' . __('Отправить и деактивировать', 'usam') . '</a>';
			$html .= '&nbsp;<a href="" id="usam_deactivate_close" class="button-secondary">' . __('Отмена', 'usam') . '</a>';
			$html .= '</div>';
			$html .= '</div>';
			
			$html .= '<script>
			jQuery(document).ready(function() 
			{ 
				var deactivate_link = "";
				jQuery("tr[data-plugin=\''.USAM_PLUGINSLUG.'\'] span.deactivate a").click(function(e) 
				{  
					e.preventDefault();
					deactivate_link = jQuery(this);
					
					jQuery(".deactivation_plugin textarea").hide();
					jQuery("#display_deactivation_plugin").modal();
				});				
				jQuery("#usam_submit_deactivate").click(function(e) 
				{							
					e.preventDefault();							
					var selected_reason = jQuery(".deactivation_plugin input[name=deactivation-reason]:checked");				
					if ( ! selected_reason.length > 0  )
					{						
						jQuery(".deactivation_plugin .warning").html("'. __('Выберите причину...', 'usam').'").show();			
						return false;
					}	
					var reason = selected_reason.val();						
					if ( reason != "reactivate" )
					{
						var reason_desc = selected_reason.parent("p").next("p").find("textarea").val();
						if ( reason_desc == "" )
						{	
							jQuery(".deactivation_plugin .warning").html("'. __('Напишите причину деактивации. Мы дополним платформу вашими рекомендациями в следующих обновлениях...', 'usam').'").show();						
							return false;
						}	
						if ( reason_desc.length < 20 )
						{	
							jQuery(".deactivation_plugin .warning").html("'. __('Дайте нам больше информации. Мы не можем понять почему вы деактивируете плагин...', 'usam').'").show();		
							return false;
						}						
					}					
					jQuery(".deactivation_plugin").html("'.__('Деактивация...', 'usam').'");
					jQuery(".modal__buttons").remove();					
					
					var post_values = {
						action   : "confirm_deactivation",				
						nonce    : "'.usam_create_ajax_nonce( 'confirm_deactivation' ).'",
						reason   : reason,
						message  : reason_desc,
					},
					response_handler = function(response) 
					{								
						if (! response.success) 
						{
							alert(response.error.messages.join("\n"));
							return;
						}					
						jQuery("#display_deactivation_plugin").modal("hide");						
						window.location = deactivate_link.attr("href");						
					};			
					usam_send( post_values, response_handler);	
					setTimeout(function () 
					{						
						window.location = deactivate_link.attr("href");						
					}, 1000);					
				});
				jQuery("#usam_deactivate_close").click(function(e) 
				{
					e.preventDefault();
					jQuery("#display_deactivation_plugin").modal("hide");
				});	
				jQuery(".deactivation_plugin input[name=deactivation-reason]").click(function() 
				{
					jQuery(".deactivation_plugin textarea").hide();
					jQuery(this).parent("p").next("p").find("textarea").show();
				});				
			});
			</script>';

			echo usam_get_modal_window( __('Пожалуйста, расскажите почему вы деактивируете плагин?','usam'), 'display_deactivation_plugin', $html );	
		}
	}
	
	public static function confirm_deactivation_options() 
	{
		$options = array(			
			'missing-feature'		 => array( 'label' => __( "Плагин отличный, но нет функции которая мне нужна", 'usam'), 'placeholder' => __( "Какая функция Вам нужна?", 'usam') ),
			'theme'		             => array( 'label' => __( "Не подходит под мою тему", 'usam'), 'placeholder' => __( "Вам помочь с интеграцией в вашу тему?", 'usam') ),
			'dont-understand'		 => array( 'label' => __( "Я не смог понять как он работает", 'usam'), 'placeholder' => __( "Что мы могли бы сделать лучше?", 'usam') ),
			'better-plugin'			 => array( 'label' => __( "Я нашел плагин получше", 'usam'), 'placeholder' => __( "Какой плагин лучше?", 'usam') ),			
			'not-working'			 => array( 'label' => __( "Плагин не работает", 'usam'), 'placeholder' => __( "Пожалуйста, поделитесь тем, что не работает, чтобы мы исправили это в будущих версиях...", 'usam') ),
			'looking-something-else' => array( 'label' => __( "Это не то, что я искал", 'usam'), 'placeholder' => __( "Что вы искали?", 'usam') ),
			'didnt-work'			 => array( 'label' => __( "Плагин не работал должным образом", 'usam'), 'placeholder' => __( "Пожалуйста, поделитесь тем, что не работает должным образом?", 'usam') ),
			'reactivate'		     => array( 'label' => __( "Мне просто нужно его переактивировать", 'usam'), 'placeholder' => '' ),
			'other'					 => array( 'label' => __( "Другое", 'usam'), 'placeholder' => __( "Какова причина?", 'usam') ),
		);
		return $options;
	}
	
	public static function send_tracking_data( $override = false )
	{	
		wp_clear_scheduled_hook( 'usam_tracker_send_event' );	
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		} 			
		if ( ! apply_filters( 'usam_tracker_send_override', $override ) )
		{			
			$last_send = self::get_last_send_time(); 
			if ( $last_send && $last_send > apply_filters( 'usam_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}
		} 
		else 
		{			
			$last_send = self::get_last_send_time(); 
			if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}
		update_option( 'usam_tracker_last_send', time() );

		$params   = self::get_tracking_data();
		
		$api = new USAM_Service_API();
		$result = $api->tracker( $params );
	}	

	private static function get_last_send_time() 
	{
		return apply_filters( 'usam_tracker_last_send_time', get_option( 'usam_tracker_last_send', false ) );
	}
	
	private static function get_tracking_data() 
	{
		$data                       = array();		
		$data['url']                = home_url();
		$data['email']              = get_option( 'admin_email' );
		$data['theme']              = self::get_theme_info();	
		$data['wp']                 = self::get_wordpress_info();		
		$data['server']             = self::get_server_info();			
		$data['platform_version']   = get_bloginfo( 'version' );	
		$data['usam_version']       = USAM_VERSION;			
		
		$all_plugins                = self::get_all_plugins();
		$data['active_plugins']     = $all_plugins['active_plugins'];
		$data['inactive_plugins']   = $all_plugins['inactive_plugins'];			

		$memory = usam_filesize_to_bytes( WP_MEMORY_LIMIT );
		if ( function_exists( 'memory_get_usage' ) ) 
		{
			$system_memory = usam_filesize_to_bytes( @ini_get( 'memory_limit' ) );
			$memory        = max( $memory, $system_memory );
		}	
		$data['memory_limit'] = $memory;		
		
		$data['users']              = self::get_user_counts();
		return apply_filters( 'usam_tracker_data', $data );
	}
	
	private static function get_all_plugins() 
	{		
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}
		$plugins        	 = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins 	 = array();

		foreach ( $plugins as $k => $v ) 
		{			
			$formatted = array();
			$formatted['name'] = strip_tags( $v['Name'] );
			if ( isset($v['Version'] ) ) {
				$formatted['version'] = strip_tags( $v['Version'] );
			}
			if ( isset($v['Author'] ) ) {
				$formatted['author'] = strip_tags( $v['Author'] );
			}
			if ( isset($v['Network'] ) ) {
				$formatted['network'] = strip_tags( $v['Network'] );
			}
			if ( isset($v['PluginURI'] ) ) {
				$formatted['plugin_uri'] = strip_tags( $v['PluginURI'] );
			}
			if ( in_array( $k, $active_plugins_keys ) ) 
			{				
				unset( $plugins[ $k ] );
				$active_plugins[ $k ] = $formatted;
			} else {
				$plugins[ $k ] = $formatted;
			}
		}
		return array( 'active_plugins' => $active_plugins, 'inactive_plugins' => $plugins );
	}
	
	private static function get_theme_info()
	{
		$theme_data        = wp_get_theme();
	
		$theme_child_theme = is_child_theme() ? 'Yes' : 'No';
		$theme_support  = ( ! in_array( $theme_data->template, ['Domino'] ) ) ? 'No' : 'Yes';

		return array( 'name' => $theme_data->Name, 'version' => $theme_data->Version, 'child_theme' => $theme_child_theme, 'support' => $theme_support );
	}
	
	private static function get_wordpress_info() 
	{
		$wp_data = array();
		
		$wp_data['debug_mode']   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data['locale']       = get_locale();
		$wp_data['multisite']    = is_multisite() ? 'Yes' : 'No';

		return $wp_data;
	}
	
	private static function get_server_info() 
	{
		$server_data = array();

		if ( isset($_SERVER['SERVER_SOFTWARE'] ) && !empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_data['software'] = $_SERVER['SERVER_SOFTWARE'];
		}

		if ( function_exists( 'phpversion' ) ) {
			$server_data['php_version'] = phpversion();
		}

		if ( function_exists( 'ini_get' ) ) 
		{
			$server_data['php_post_max_size'] = size_format( usam_filesize_to_bytes( ini_get( 'post_max_size' ) ) );
			$server_data['php_time_limt'] = ini_get( 'max_execution_time' );
			$server_data['php_max_input_vars'] = ini_get( 'max_input_vars' );
			$server_data['php_suhosin'] = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
		}

		global $wpdb;
		$server_data['mysql_version'] = $wpdb->db_version();

		$server_data['php_max_upload_size'] = size_format( wp_max_upload_size() );
		$server_data['php_default_timezone'] = date_default_timezone_get();
		$server_data['php_soap'] = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
		$server_data['php_fsockopen'] = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data['php_curl'] = function_exists( 'curl_init' ) ? 'Yes' : 'No';

		return $server_data;
	}	
	
	private static function get_user_counts()
	{
		$user_count          = array();
		$user_count_data     = count_users();
		$user_count['total'] = $user_count_data['total_users'];

		// Get user count based on user role
		foreach ( $user_count_data['avail_roles'] as $role => $count ) {
			$user_count[ $role ] = $count;
		}
		return $user_count;
	}	
}
USAM_Tracker::init();