<?php
class USAM_Tab_Reports extends USAM_Tab
{			
	public function get_title_tab()
	{			
		$path_report = USAM_FILE_PATH .'/admin/reports/finished-reports/'; 
		if ( isset($_REQUEST['table']) &&  file_exists( $path_report.$_REQUEST['table'].'.php' ) )
		{
			$file_data = get_file_data( $path_report.$_REQUEST['table'].'.php', array('ver'=>'Version', 'author'=>'Author', 'date'=>'Date', 'description'=>'Description', 'name'=>'Name' ) );
			return $file_data['name'];
		}
		else
			return __('Готовые отчеты', 'usam');
	}
		
	public function get_message()
	{		
		$message = '';		
		if( isset($_REQUEST['error']) && $_REQUEST['error'] == 'yandex' )
		{
			$message = __('Подключите Яндекс Метрика','usam');	
		}		
		return $message;
	} 	
}