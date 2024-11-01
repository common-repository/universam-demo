<?php
// Получить id основных страниц по шорткоду
function usam_get_system_page_id( $page_name ) 
{	
	$system_page = get_option( 'usam_system_page', false );
	if ( empty($system_page) )
	{
		$system_page = usam_update_permalink_slugs();
	}
	$post_id = isset($system_page[$page_name]['id'] ) ? $system_page[$page_name]['id'] : null;
	return apply_filters( 'usam_get_system_page_id', $post_id, $page_name );
}

function usam_get_system_page_name( $page_name ) 
{	
	$system_page = get_option( 'usam_system_page', false );
	if ( empty($system_page) )
	{
		$system_page = usam_update_permalink_slugs();
	}
	$name = isset($system_page[$page_name]['name'] ) ? $system_page[$page_name]['name'] : null;
	return apply_filters( 'usam_get_system_page_name', $name, $page_name );
}

// Получить ссылку на основные страницы по их названию
function usam_get_url_system_page( $page_name, $path = '' ) 
{ 
	$virtual_page = usam_get_virtual_page( $page_name );
	if ( !empty($virtual_page) ) 
		$url_system_page = home_url( $virtual_page['slug'] );	
	else
	{
		$system_page = get_option( 'usam_system_page', false );
		$url_system_page = isset($system_page[$page_name]['url'] ) ? $system_page[$page_name]['url'] : '';	
		if ( is_ssl() ) 
			$url_system_page = str_replace( 'http://', 'https://', $url_system_page );
		else
			$url_system_page = str_replace( 'https://', 'http://', $url_system_page );
	}	
	if ( empty($url_system_page) )
		$url_system_page = rtrim(home_url( $page_name ), '/\\').'/';	
	if ( $path )
		$url_system_page = rtrim($url_system_page, '/\\').'/'.$path.'/';	
	
	return apply_filters( 'usam_get_url_system_page', $url_system_page, $page_name );
}	

function usam_url_system_page( $page_name ) 
{
	echo usam_get_url_system_page( $page_name );
}

// Получить ссылку на вкладку страницы кабинета пользователя
function usam_get_user_account_url( $tab = '' ) 
{
	if ( $tab == 'logout_url' )
		$url_system_page = wp_logout_url();
	else
		$url_system_page = usam_get_url_system_page( 'your-account', $tab );
	return $url_system_page;
}

function usam_is_system_page( $page_name ) 
{
	$pages = usam_system_pages( );
	if ( isset($pages[$page_name]) )
	{
		global $post;
		if ( strpos($post->post_content, '['.$pages[$page_name]['shortcode'].']') )			
			return true;
	}
	return false;
}	

/**
 * Обновить постоянную ссылку на используемые страницы по шорткоду. 
 * Для обновления сохраните любую страницу.
 */
function usam_update_permalink_slugs() 
{
	global $wpdb;	
	$system_pages = usam_system_pages();
	$update_page = array();				
	foreach ( $system_pages as $page )	
	{
		$results = $wpdb->get_row("SELECT `ID`, `post_name` FROM `{$wpdb->posts}` WHERE `post_type` = 'page' AND `post_content` LIKE '%[".$page['shortcode']."]%' LIMIT 1");
		if ( empty($results) )
			continue;
			
		$update_page[$page['name']] = ['id' => $results->ID, 'name' => $results->post_name, 'url' => get_page_link( $results->ID )];
	}
	update_option( 'usam_system_page', $update_page, true );
	return $update_page;
}


function usam_your_account_current_tab( $default_tab = '', $default_subtab = '' ) 
{
	global $wp_query;
	if (isset($wp_query->query_vars['tabs']))
		$tab['tab'] = $wp_query->query_vars['tabs'];
	elseif ( $default_tab )
		$tab['tab'] = $default_tab;	
	else
	{		
		$tabs = usam_get_menu_your_account();
		$current = current($tabs);
		$tab['tab'] = $current['slug'];
	}	
	if (isset($wp_query->query_vars['subtab']))
		$tab['subtab'] = $wp_query->query_vars['subtab'];
	else
		$tab['subtab'] = $default_subtab;		
	return $tab;
}

function usam_get_virtual_page( $pagename ) 
{	
	$virtual_page = usam_virtual_page();
	if ( isset($virtual_page[$pagename]) )
		return $virtual_page[$pagename];
	else
		return false;
}	

function usam_get_system_page( $pagename ) 
{	
	$virtual_page = usam_system_pages( );
	if ( isset($virtual_page[$pagename]) )
		return $virtual_page[$pagename];
	else
		return false;
}
?>