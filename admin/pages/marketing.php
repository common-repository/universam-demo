<?php
/*
 * Отображение страницы "Маркетинг"
 */
class USAM_Tab extends USAM_Page_Tab
{		
	protected function localize_script_tab()
	{ 			
		return array(										
			'text_or'  => __('ИЛИ', 'usam'),
			'text_and' => __('И', 'usam'),
		);
	}
} 
