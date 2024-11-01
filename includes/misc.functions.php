<?php
// сжатие css.
// Использовать ob_start("compress_css");
function usam_compress_css($buffer)
{
	/* Удаляем комментарии */
	$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	/* Удаляем табуляции, пробелы, переводы строки и так далее */
	//$buffer = str_replace(array("\r", "\t", '  ', '    ', '    '), '', $buffer);
	return $buffer;
}

function usam_ip_in_range( $ip, $range ) 
{
    if ( strpos( $range, '/' ) === false )
        $range .= '/32';
    // $range is in IP/CIDR format eg 127.0.0.1/24
    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_decimal = ip2long( $range );
    $ip_decimal = ip2long( $ip );
    $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal) );
}

/* Функция генерации уникальной строки */
function usam_rand_string( $length = 6, $chars = 'ABCDEFGHJKLMNOPQRSTUVWXYZ1234567890' )
{
    $input_length = strlen($chars);
    $random_string = '';
    for($i = 0; $i < $length; $i++)
	{
        $random_character = $chars[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
	return $random_string;
}

// Массив в ссылку
function usam_url_array_encode( $data )
{
    return strtr(base64_encode(addslashes(gzcompress(serialize($data),9))), '+/=', '-_,');
}

function usam_url_array_decode( $encoded ) 
{
    return unserialize(gzuncompress(stripslashes(base64_decode(strtr($encoded, '-_,', '+/=')))));
}

function usam_array_merge_recursive($default, $arrays)
{
    foreach( $default as $key => $value )  
	{
		if( is_array($value) && @is_array($arrays[$key]))
			$arrays[$key] = usam_array_merge_recursive($value, $arrays[$key]);
		else
			$arrays[$key] = isset($arrays[$key])?$arrays[$key]:$value;
	}
    return $arrays;
}

function usam_check_license() 
{
	if ( usam_is_license_type('FREE') )
		return true;
	$license = get_option('usam_license', array() );		
	if( empty($license['license']) )
	{
		$api = new USAM_Service_API();
		$api->set_free_license( );
		return true;
	}			
	if( !empty($license['status']) && $license['status'] == 1 && (empty($license['date']) || strtotime($license['date']) < time()) && (empty($license['domain']) || $license['domain'] == $_SERVER['SERVER_NAME']) )	
		return true;
	else
		return false;
}

function usam_add_data_temporary_table( $data, $name_table ) 
{
	global $wpdb;
		
	set_time_limit(1800);		
	
	if ( empty($data[0]) )
		return false;
	
	$columns = array();
	foreach( $data as $row ) 
	{
		foreach( $row as $key => $value ) 
		{
			$max = 0;
			foreach( $data as $values ) 
			{	
				if ( !empty($values[$key]) )
				{
					$strlen = mb_strlen($values[$key]);			
					if ( $strlen > $max )
						$max = $strlen;
				}
			}
			if ( !isset($columns[$key]) )
			{
				if ( $max <= 255 )
					$columns[$key] = "`$key` VARCHAR(255)";
				else
					$columns[$key] = "`$key` longtext";
			}
		}
	}
	$columns[] =  '`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT';
	$columns[] = "PRIMARY KEY (`id`)";
	$wpdb->query("DROP TABLE IF EXISTS $name_table");	
	$wpdb->query("CREATE TABLE $name_table (".implode(",",$columns).");");
	foreach( $data as $row ) 
	{
		$columns = array();
		$i = 0;
		foreach( $row as $key => $value )
		{
			if ( empty($value) )
				$i++;
			$columns[] = "`$key`";
			
			$row[$key] = esc_sql($value);
		}	
		if ( $i != count($row) )
			$wpdb->query( "INSERT `$name_table` (".implode(",",$columns).") VALUES ('".implode("','",$row)."')" );
	}
	return true;
}

function usam_get_tag( $attr, $value, $xml, $tag = null ) 
{
	if( is_null($tag) )
	$tag = '\w+';
	else
	$tag = preg_quote($tag);

	$attr = preg_quote($attr);
	$value = preg_quote($value);

	$tag_regex = "/<(".$tag.")[^>]*$attr\s*=\s*"."(['\"])$value\\2[^>]*>(.*?)<\/\\1>/";
	preg_match_all($tag_regex,$xml,$matches, PREG_PATTERN_ORDER);
	return $matches;
}

//функция склонения слов
function usam_get_plural($number, $before, $after = array() ) 
{
	$cases = array(2,0,1,1,1,2);
	if ( !empty($after) )
		return $before[($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)]].' '.$number.' '.$after[($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)]];
	else
		return $number.' '.$before[ ($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)] ];
}

function usam_plural($number, $before, $after = array() ) 
{
	echo usam_get_plural($number, $before, $after );
}

function usam_url_admin_action( $action, $arg = [], $url = '' )
{
	if ( empty($url) )
		$url = admin_url('admin.php');
	
	if ( !empty($arg) )
		$url = add_query_arg( $arg, $url );	
	
	return wp_nonce_url( add_query_arg( array('usam_admin_action' => $action), $url ), $action.'_nonce' );
}

function usam_url_action( $action, $arg = [],  $url = '' )
{
	if ( empty($url) )
		$url = get_bloginfo('url');
	
	if ( !empty($arg) )
		$url = add_query_arg( $arg, $url );	
	
	return wp_nonce_url( add_query_arg(['usam_action' => $action], $url ), $action.'_nonce' );
}

/**
 * Вспомогательная функция, которая генерирует временное значение для действия AJAX. Функция автоматически добавлять префикс.
 */
function usam_create_ajax_nonce( $ajax_action ) 
{
	return wp_create_nonce( "usam_ajax_{$ajax_action}" );
}


function usam_compare_data( $operator, $data, $value )
{
	$result = false;	
	switch ( $operator ) 
	{
		case 'contains' :							
			if( stripos(mb_strtolower($data), mb_strtolower($value)) !== false )
				$result = true;
		break;
		case 'not_contain' :			
			if( stripos(mb_strtolower($data), mb_strtolower($value)) === false )
				$result = true;
		break;
		case '!=' :
			if( is_integer($value) )
			{
				if( $data != $value )
					$result = true;
			}
			elseif( mb_strtolower($data) != mb_strtolower($value) )
				$result = true;
		break;
		case '=' :							
			if( is_integer($value) )
			{
				if ($data == $value)
					$result = true;
			}
			elseif( mb_strtolower($data) === mb_strtolower($value) )
				$result = true;
		break;
		case '>=' :
			if( (int)$data >= (int)$value)
				$result = true;
		break;
		case '<=' :
			if( (int)$data <= (int)$value)
				$result = true;
		break;
		case '>' :
			if( (int)$data > (int)$value)
				$result = true;
		break;
		case '<' :
			if( (int)$data < (int)$value)
				$result = true;
		break;
	}	
	return $result;
}


function usam_get_list_triggers( ) 
{
	$triggers = [];
	$documents = usam_get_details_documents();
	//foreach ( $documents as $key => $document ) 
	//	$triggers[$key] = $document['single_name'];
	$triggers['usam_new_letter_received'] = ['title' => __('Входящее электронное письмо','usam'), 'args' => 2];
	$triggers['usam_new_request_customer'] = ['title' => __('Новое обращение от покупателя','usam'), 'args' => 4];
	$triggers['usam_competitor_price_changed'] = ['title' => __('Цена конкурента изменилась','usam'), 'args' => 4];
	$triggers['usam_update_order_status'] = ['title' => __('Статус заказа изменился','usam'), 'args' => 4];
	$triggers['usam_subscription_expired'] = ['title' => __('Подписка завершилась','usam'), 'args' => 2];	
	$triggers['usam_bonus_update'] = ['title' => __('Бонусы обновленны','usam'), 'args' => 1];
	return $triggers;
}

function usam_get_list_actions_triggers( ) 
{
	$actions = [];
	$actions['creating_lead'] = ['title' => __('Создать лид','usam'), 'events' => ['usam_new_letter_received', 'usam_new_request_customer']];
	$actions['change_price'] = ['title' => __('Изменить цену','usam'), 'events' => ['usam_competitor_price_changed']];
	$actions['create_invoice'] = ['title' => __('Создать счет','usam'), 'events' => ['usam_subscription_expired']];
	$actions['create_act'] = ['title' => __('Создать акт','usam'), 'events' => ['usam_subscription_expired']];	
	$actions['renew_subscription'] = ['title' => __('Продлить подписку','usam'), 'events' => ['usam_subscription_expired']];
	$actions['send_letter'] = ['title' => __('Отправить письмо','usam'), 'events' => ['usam_subscription_expired', 'usam_new_request_customer', 'usam_update_order_status', 'usam_bonus_update']];
	$actions['send_sms'] = ['title' => __('Отправить SMS','usam'), 'events' => ['usam_subscription_expired', 'usam_new_request_customer', 'usam_update_order_status', 'usam_bonus_update']];	
	return $actions;
}

function usam_get_title_trigger_list( $event )
{
	$events = usam_get_list_triggers();	
	return isset($events[$event])?$events[$event]['title']:'';	
}

/*Получить переводчики*/
function usam_get_translators()
{
	return apply_filters( 'usam_translators', [] );
}
//usam_translate



function usam_get_user_columns( $type )
{
	$user_columns = get_user_option( 'usam_columns_document' );	
	if ( empty($user_columns) )
		$user_columns = [];
	if ( empty($user_columns[$type]) )
		$user_columns[$type] = [];			
	return $user_columns[$type];
}