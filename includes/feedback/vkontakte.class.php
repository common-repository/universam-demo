<?php
class USAM_VKontakte
{		
	public function __construct( )
	{	
		if ( !USAM_DISABLE_INTEGRATIONS )
		{			
			add_action( 'usam_hourly_cron_task', [&$this, 'friends_add'] ); 
			add_action( 'usam_vk_publish', [&$this, 'publish_birthday'], 11 ); 
			add_action( 'usam_vk_publish', [&$this, 'publish_product_day'], 10 );	
			add_action( 'usam_vk_publish', [&$this, 'update_products'], 9 ); 	
			add_action( 'usam_update_product_images', [&$this, 'update_product_images'], 9, 2 );			
			
			add_action( 'usam_hourly_cron_task', [&$this, 'publish_contest'] ); 				
			add_action( 'usam_update_customer_review_status', [&$this, 'event_update_customer_review_status'], 10, 4 );
		}
	}	
	
	public static function pixel( )
	{		
		$pixel = get_option( 'usam_vk_pixel', false );
		if ( !empty($pixel) )
		{
			$currency = usam_get_currency_price_by_code();
			?>
			<script>
				!function(){var t=document.createElement("script");t.type="text/javascript",t.async=!0,t.src="https://vk.com/js/api/openapi.js?160",t.onload=function(){VK.Retargeting.Init('<?php echo $pixel; ?>'),VK.Retargeting.Hit()},document.head.appendChild(t)}();		
				jQuery(document).ready(function($)
				{ 
					jQuery('body').delegate('.js-product-add', 'product-add' , function( e, product_id, response )	
					{ 
						var callback = function(r)
						{																
							if ( r )
							{ 
								VK.Retargeting.ProductEvent(product_id, "add_to_cart", {"products": [{"id": product_id, 'price': r.price, 'price_old': r.old_price, 'currency_code':'<?php echo $currency; ?>'}]});						      
							}						
						};				
						usam_send({action: 'get_product_data',	product_id : product_id}, callback);
					});		
				});	
			</script>	
			<noscript><img src="https://vk.com/rtrg?p=VK-RTRG-374233-3sY99" style="position:fixed; left:-999px;" alt=""/></noscript>
			<?php		
		}		
	}	
	
	public function event_update_customer_review_status( $reviews_id, $current_status, $previous_status, $t )
	{
		if ( $current_status == 1 )
		{
			$post_id = $t->get('page_id');				
			$post = get_post( $post_id );	
			if ( !empty($post) && $post->post_type == 'usam-product')
			{
				require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );				
				$profiles = usam_get_social_network_profiles( array( 'type_social' => array( 'vk_group', 'vk_user' ), 'meta_key' => 'publish_reviews', 'meta_value' => 1 ) );
				foreach ( $profiles as $profile )
				{							
					$vkontakte = new USAM_VKontakte_API( $profile->id );
					$vkontakte->publish_product_review( $reviews_id );	
				}	
			}
		}
	}	
	
	function friends_add()
	{
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );				
		$profiles = usam_get_social_network_profiles(['type_social' => 'vk_user', 'meta_key' => 'add_friends', 'meta_value' => 1, 'cache_results' => true, 'cache_meta' => true]);
		$search = array( 'q' => '', 'sort' => '1', 'online' => 1, 'country' => 1 , 'count' => 1, 'age_from' => 25, 'age_to' => 45, 'sex' => 2, 'has_photo' => 1 );			
		foreach ( $profiles as $profile )
		{							
			$params['offset'] = (int)usam_get_social_network_profile_metadata( $profile->id, 'add_friends_offset' );
			$params['offset'] += 10;
			$vkontakte = new USAM_VKontakte_API( $profile->id );
			$vkontakte->users_search( $params );
			usam_update_social_network_profile_metadata( $profile->id, 'add_friends_offset', $params['offset'] );		
		}			
	}
			
	// Обновляет товары в контакт
	function update_products()
	{			
		$profiles = usam_get_social_network_profiles(['type_social' => 'vk_group']);
		foreach ( $profiles as $profile ) 
		{  	 
			if ( isset($profile->code) )
			{
				$args = ['post_status' => 'publish', 'productmeta_query' => [['key' => 'vk_market_id_'.$profile->code, 'type' => 'numeric', 'value' => '0', 'compare' => '!=']]];	
				$count = usam_get_total_products( $args );
				if ( $count )
					usam_create_system_process( sprintf(__("Обновление товаров вКонтакте сообщества %s", "usam" ), $profile->name), $profile->id, 'vk_update_all_products', $count, 'vk_update_all_products', 4 );
			}
		}
	}	

	function update_product_images( $product_id, $data )
	{					
		$profiles = usam_get_social_network_profiles(['type_social' => 'vk_group']);
		if( $profiles )
		{
			require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
			foreach ( $profiles as $profile ) 
			{  	
				$vkontakte = new USAM_VKontakte_API( $profile->id );	
				$vkontakte->edit_photos_product( $product_id );
			}
		}
	}		
	
	/*
	Поздравляет пользователь группы
	*/
	function publish_birthday()
	{								
		$profiles = usam_get_social_network_profiles(['type_social' => 'vk_group', 'birthday' => 1]);	
		$count_users = 0;
		if( $profiles )
		{
			require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );		
			
			$current_day = (int)date('d');
			$current_month = (int)date('m');
			
			foreach ( $profiles as $profile ) 
			{  						
				$vkontakte = new USAM_VKontakte_API( $profile->id );	
				$users = $vkontakte->get_group_members(['fields' => 'sex, city, country, has_mobile, online, bdate, photo_50, photo_100']);				
				if ( empty($users) )
					continue;
				
				$selected_users = array(); 
				foreach ( $users as $user ) 
				{
					if ( isset($user['bdate']) )
					{						
						$day = explode(".",$user['bdate']);					
						if ( isset($day[1]) && (int)$day[0] == $current_day && $current_month == (int)$day[1] )
						{
							$selected_users[] = $user;
						}
					}					
				}			
				if ( empty($selected_users) )
					continue;
				
				$vkontakte->publish_birthday( $selected_users );			
				$count_users += count($selected_users);	
			} 	
		}
		return $count_users;		
	}	
	
	function publish_product_day()
	{		
		$profiles = usam_get_social_network_profiles(['type_social' => 'vk_group', 'meta_key' => 'publish_product_day', 'meta_value' => 1]);
		if( $profiles )
		{
			require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
			require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
			foreach ( $profiles as $profile )
			{	
				$vkontakte = new USAM_VKontakte_API( $profile->id );	
				$products_day = usam_get_products_day(['status' => 1, 'code_price' => $profile->type_price]);
				foreach( $products_day as $product )		
				{
					$vkontakte->product_day( $product->product_id );							
				}
			}
		}
	}	
			
	// Обработка начала и окончания конкурсов
	function publish_contest(  ) 
	{
		$current_time = current_time('timestamp');	
		$option = get_site_option('usam_vk_contest');
		$contests = maybe_unserialize( $option );	
		if ( !empty($contests) )
		{		
			require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );				
			$t_date = time();
			$flag = false;
			foreach ( $contests as $key => $contest )
			{							
				if ( $contest['active'] == 1 )
				{
					$vkontakte = new USAM_VKontakte_API( $contest['profile'] );	
					if ( $contest['process'] == 0  && strtotime($contest['start_date']) <= $current_time )
					{							
						$contest[$key]['process'] = 1;								
						$vkontakte->publish_contest( $contest );					
					}
					if ( $contest['process'] == 1 && strtotime($contest['end_date']) >= $current_time )
					{					
						$args = array( 'in_group' => $contest['in_group'], 'winner_count' => $contest['winner_count'] );
						$contest[$key]['process'] = 2;
						$vkontakte->find_winner_the_contest( $args );
					}				
				}
			}
			if ( $flag )
				update_site_option('usam_vk_contest', serialize($contests) );
		}		
	}
}
new USAM_VKontakte();


/*
Запрос в API вКонтакт
*/
function usam_vkontakte_send_request( $function, $params = [] )
{		
	$params['v'] = '5.131';
	if ( empty($params['access_token']) )
	{
		$api = get_option('usam_vk_api');		
		$params['access_token'] = !empty($api['service_token'])?$api['service_token']:'';
	}
	$data = wp_remote_post('https://api.vk.com/method/'.$function, ['body' => $params, 'sslverify' => true, 'timeout' => 5]);
	if ( is_wp_error($data) )
	{
		usam_log_file( __('Ошибки вКонтакте: ','usam').$data->get_error_message() );
		return false;
	} 
	$result = json_decode($data['body'],true);	
	if ( isset($result['error'] ) ) 
	{		
		$error = sprintf( __('Ошибки вКонтакте. Приложение %s вызвало ошибку №%s. Текст ошибки: %s'), $result['error']['request_params'][0]['value'], $result['error']['error_code'], $result['error']['error_msg']);		
		usam_log_file( $error );	
		return false;
	}		
	return $result['response'];
}

function usam_get_statuses_vk()
{
	return [0 => 'Новый', 1 => 'Согласуется', 2 => 'Собирается', 3 => 'Доставляется', 4 => 'Выполнен', 5 => 'Отменен', 6 => 'Возвращен'];
}
?>