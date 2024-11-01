<?php	
require_once( USAM_FILE_PATH .'/admin/includes/importer.class.php' );	
class USAM_Form_order_importer extends USAM_Importer
{		
	protected $rule_type = 'order_import';	
	protected function get_columns()
	{
		return usam_get_columns_order_import();
	}
	
	public function get_url()
	{
		return admin_url('admin.php?page=orders&tab=orders&view=table&table=order_import');
	}	
}
?>