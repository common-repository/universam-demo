<?php
function usam_is_multisite( ) 
{
	if ( is_multisite() )
	{
		if ( defined('USAM_MULTISITE') && USAM_MULTISITE )
			return true;	
	}
	return false;	
}

function usam_get_post_id_main_site( $post_id ) 
{
	$post_id = (int)$post_id;
	if ( !usam_is_multisite() || is_main_site() )
		return $post_id;
	else
	{
		$blog_id = get_current_blog_id();
		$cache_key = 'usam_post_id_main_site_'.$blog_id;
		$post_id_main_site = wp_cache_get($post_id, $cache_key);	
		if( $post_id_main_site === false )
		{					
			global $wpdb;
			$post_id_main_site = $wpdb->get_var("SELECT ID FROM ".usam_get_table_db('linking_posts_multisite')." WHERE multisite_post_id=$post_id");	
			wp_cache_set( $post_id, $post_id_main_site, $cache_key );
		}		
		return $post_id_main_site;
	}	
}

function usam_get_post_id_multisite( $post_id_main_site ) 
{
	$post_id_main_site = (int)$post_id_main_site;
	if ( !usam_is_multisite() || is_main_site() )
		return $post_id_main_site;
	else
	{
		$blog_id = get_current_blog_id();
		$cache_key = 'usam_post_id_multisite_'.$blog_id;
		$post_id = wp_cache_get($post_id_main_site, $cache_key);	
		if( $post_id === false )
		{					
			global $wpdb;
			$post_id = $wpdb->get_var("SELECT multisite_post_id FROM ".usam_get_table_db('linking_posts_multisite')." WHERE ID=$post_id_main_site");	
			wp_cache_set( $post_id_main_site, $post_id, $cache_key );
		}		
		return $post_id;
	}	
}

function usam_get_term_id_main_site( $term_id ) 
{
	$term_id = (int)$term_id;
	if ( !usam_is_multisite() || is_main_site() )
		return $term_id;
	else
	{
		$term_id_main_site = wp_cache_get($term_id, 'usam_term_id_main_site');	
		if( $term_id_main_site === false )
		{					
			global $wpdb;
			$term_id_main_site = $wpdb->get_var("SELECT term_id FROM ".usam_get_table_db('linking_terms_multisite')." WHERE multisite_term_id=$term_id");	
			wp_cache_set( $term_id, $term_id_main_site, 'usam_term_id_main_site' );
		}		
		return $term_id_main_site;
	}	
}
?>