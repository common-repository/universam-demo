<?php
class USAM_Tab_advertising_campaigns extends USAM_Tab
{	
	public $views = ['table', 'report'];
	
	public function __construct()
	{			
		/*
require_once( USAM_FILE_PATH . '/includes/seo/yandex/direct_yandex.class.php' );

$webmaster = new USAM_Yandex_Direct();		
$external = $webmaster->get_campaigns( );
*/
	}	
	
	function get_title_tab() 
	{			
		return __('Управление рекламными компаниями', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return array( array('form' => 'edit', 'form_name' => 'advertising_campaign', 'title' => __('Добавить компанию', 'usam') ) );					
	}		
}