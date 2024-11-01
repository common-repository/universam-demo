<?php
/*
 * Отображение страницы SEO
 */  
class USAM_Tab extends USAM_Page_Tab
{	
	protected function localize_script_tab()
	{
		return array(			
			'seo_title_product_save_nonce' => usam_create_ajax_nonce( 'seo_title_product_save' ),
			'add_keyword_nonce'     => usam_create_ajax_nonce( 'add_keyword' ),	
			'delete_keyword_nonce'  => usam_create_ajax_nonce( 'delete_keyword' ),
		);
	}
}