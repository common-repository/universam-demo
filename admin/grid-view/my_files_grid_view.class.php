<?php
require_once( USAM_FILE_PATH . '/admin/grid-view/files_grid_view.class.php' );
class USAM_my_files_Grid_View extends USAM_Files_Grid_View
{			
	protected function class_grid() 
	{ 
		return 'files_grid';
	}
}
?>