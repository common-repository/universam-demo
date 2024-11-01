<?php
require_once( USAM_FILE_PATH . '/admin/includes/filter.class.php' );	
class USAM_Tab_constructor extends USAM_Tab
{
	public function get_title_tab()
	{					
		if ( !empty($_REQUEST['filter_id']) )
		{ 			
			$id = absint($_REQUEST['filter_id']);				
			$filter = new USAM_Filter( $id );
			$data = $filter->get_data();				
			return $data['name'];
		}
		return __('Созданные отчеты', 'usam');	
	}	
}