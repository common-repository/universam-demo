<?php
function usam_get_search_engine_regions( $qv = array() )
{ 
	global $wpdb;
	if ( isset($qv['fields']) )
	{
		$fields = $qv['fields'] == 'all'?'*':$qv['fields'];
	}
	else		
	{
		$qv['fields'] = 'all';
		$fields = '*';
	}		
	if ( !isset($qv['active']) || $qv['active'] == '1')
		$_where[] = "active = '1'";
	elseif ( $qv['active'] == '0' )
		$_where[] = "active = '0'";		
	
	$_where[] = '1=1';		
	if ( isset($qv['location_id']) )
		$_where[] = "location_id IN( '".implode( "','", $qv['location_id'] )."' )";
	
	if ( isset($qv['code']) )
		$_where[] = "code = '".$qv['code']."'";
	
	if ( isset($qv['search_engine']) )
		$_where[] = "search_engine = '".$qv['search_engine']."'";
	
	if ( isset($qv['name']) )
		$_where[] = "name = '".$qv['name']."'";	
	
	if ( isset($qv['include']) )
		$_where[] = "id IN( '".implode( "','", $qv['include'] )."' )";
	
	$where = implode( " AND ", $_where);		
	if ( isset($qv['orderby']) )	
		$orderby = $qv['orderby'];	
	else
		$orderby = 'sort';
	$orderby = "ORDER BY $orderby";
	
	if ( isset($qv['order']) )	
		$order = $qv['order'];	
	else
		$order = 'ASC';	
	
	if ( empty( $qv['paged'] ) ) 
		$qv['paged'] = 1;	
	
	$limit = '';
	if ( isset($qv['number'] ) && $qv['number'] > 0 ) 
	{
		if ( isset($qv['offset']) )
			$limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
		else 
			$limit = $wpdb->prepare( "LIMIT %d, %d", $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
	}	
	$sql = "SELECT $fields FROM ".USAM_TABLE_SEARCH_ENGINE_REGIONS." WHERE $where $orderby $order $limit";
	if ( is_array($qv['fields']) || 'all' == $qv['fields'] )
	{
		$results = $wpdb->get_results( $sql );
	} 		
	else 
	{
		$results = $wpdb->get_col( $sql );
	}
	return $results;
}

/* Эта функция будет проверять, является ли посетитель роботом поисковой системы */
function usam_is_bot( )
{ 
	$bots = usam_get_site_bots();
	if ( isset($_SERVER['HTTP_USER_AGENT']) )
	{
		$agent = mb_strtolower($_SERVER['HTTP_USER_AGENT']);
		foreach($bots as $bot)
		{
			if( stripos($agent, $bot) !== false )
				return true;
		}
	}
	return false;
}

function usam_check_bot( $bot )
{ 
	$bots = usam_get_site_bots();
	if ( isset($_SERVER['HTTP_USER_AGENT']) )
	{
		$agent = mb_strtolower($_SERVER['HTTP_USER_AGENT']);		
		if( stripos($agent, $bot) !== false )
			return true;
	}
	return false;
}

function usam_get_bot( )
{
	$bots = usam_get_site_bots();
	if ( isset($_SERVER['HTTP_USER_AGENT']) )
	{
		$agent = mb_strtolower($_SERVER['HTTP_USER_AGENT']);
		foreach($bots as $bot)
		{
			if( stripos($agent, $bot) !== false )
				return $bot;
		}
	}
	return false;
}

function usam_yandex_is_token( )
{	
	$yandex = get_option( 'usam_yandex' );
	return !empty($yandex['access_token'])?true:false;
}

function usam_get_url_yandex_service( $type, $url = '' )
{	
	if ( usam_yandex_is_token( ) )
	{
		$yandex = get_option('usam_yandex');
		return "https://passport.yandex.ru/passport?mode=oauthaccess_token=".$yandex['access_token']."&type=$type&retpath=$url";
	}
	else
		return '';
}

function usam_get_domain_information( $domain )
{				
	$data = wp_remote_post("http://htmlweb.ru/analiz/api.php?whois&url=$domain&json", ['body' => ['api_key' => '0f57a9e88a0bf31a36fa51e131ac8f91'], 'sslverify' => true, 'timeout' => 5, 'method' => 'GET'] );
	return json_decode($data['body'], true);
}

function usam_get_seo_shortcodes( ) 
{	
	$shortcode['term'] = array(
		'name'	          => __('Название','usam'),		
	);	
	$shortcode['product'] = array(
		'post_title'	  => __('Название','usam'),
		'sku'	          => __('Артикул','usam'),
		'price'		      => __('Цена','usam'),		
		'price_currency'  => __('Цена в валюте','usam'),
		'post_excerpt'	  => __('Краткое описание','usam'),
	);
	$shortcode['product_filter'] = array(
		'name'	          => __('Название','usam'),		
		'filter_name'	      => __('Название фильтра','usam'),
		'filter_attribute_name'	=> __('Значения фильтра','usam'),
	);
	$shortcode['post'] = array(
		'post_title'	  => __('Название','usam'),
		'post_excerpt'	  => __('Краткое описание','usam'),		
	);	
	$shortcode['default'] = array(		
		'site_name'	      => __('Название сайта','usam'),
		'phone'	          => __('Телефон','usam'),
		'email'	          => __('Электронная почта','usam'),
		'year'	          => __('Год','usam'),		
	);	
	return $shortcode;
}

function usam_get_seo_shortcode( $key = 'default' ) 
{	
	$shortcodes = usam_get_seo_shortcodes();
	if ( isset($shortcodes[$key]) )
		return array_merge($shortcodes[$key], $shortcodes['default']);
	else
		return $shortcodes['default'];	
}