<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_Files_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			[['title' => __('Общий итог', 'usam'), 'key' => 'total_files', 'view' => 'transparent']],		
			[['title' => __('Загрузка файлов', 'usam'), 'key' => 'uploading_files', 'view' => 'graph'], ['title' => __('Количество скачиваний', 'usam'), 'key' => 'number_downloads', 'view' => 'loadable_table']],	
		);	
		return $reports;
	}

	public function number_downloads_report_box()
	{	
		return [__('Файл','usam'), __('Колличество','usam')];
	}	
}
?>