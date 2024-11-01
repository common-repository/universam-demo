<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class webforms_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		$filters = array(  );
		$languages = maybe_unserialize(get_site_option('usam_languages'));
		if ( !empty($languages) )
			$filters['language'] = ['title' => __('Язык', 'usam'), 'type' => 'checklists'];
		return $filters;
	}		
}
?>