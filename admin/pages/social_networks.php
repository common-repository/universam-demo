<?php
/*
 * Отображение страницы "Социальные сети"
 */
 class USAM_Tab extends USAM_Page_Tab
{		
	protected function localize_script_tab()
	{	
		return array(			
			'add_products_vk_nonce' => usam_create_ajax_nonce( 'add_products_vk' ),	
			'add_image_vk_nonce'    => usam_create_ajax_nonce( 'add_image_vk' ),			
			'add_posts_vk_nonce'    => usam_create_ajax_nonce( 'add_posts_vk' ),				
			'add_products_ok_nonce' => usam_create_ajax_nonce( 'add_products_ok' ),	
			'add_posts_ok_nonce'    => usam_create_ajax_nonce( 'add_posts_ok' ),	
			'add_posts_fb_nonce'    => usam_create_ajax_nonce( 'add_posts_fb' ),			
			'add_products_fb_nonce'    => usam_create_ajax_nonce( 'add_products_fb' ),	
			'add_products_facebuk_nonce' => usam_create_ajax_nonce( 'add_products_facebuk' ),	
			'add_posts_facebuk_nonce'    => usam_create_ajax_nonce( 'add_posts_facebuk' ),			
		);
	}	
}