<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_delivery_documents_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = array(
		//	[['title' => __('Доставленные', 'usam'), 'key' => 'delivery_documents_total', 'view' => 'transparent']],			
			array( array('title' => __('Количество доставок','usam'), 'key' => 'courier', 'view' => 'graph'),  array('title' => __('Скорость доставки','usam'), 'key' => 'shipped_document_status_processing_speed', 'view' => 'graph') ),					
			array( array('title' => __('Доставки', 'usam'), 'key' => 'shipped_documents_received', 'view' => 'graph') ),	
		);
		return $reports;
	}	
}
?>