<?php
/*
 * Отображение страницы Управление ценой
 */ 
class USAM_Tab extends USAM_Page_Tab
{		
	protected function localize_script_tab()
	{
		return array(	
			'change_product_price_nonce'  => usam_create_ajax_nonce( 'change_product_price' ),
			'change_group_price_nonce'  => usam_create_ajax_nonce( 'change_group_price' ),
		);
	}		
} 
