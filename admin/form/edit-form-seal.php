<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_seal extends USAM_Edit_Form
{
	protected $vue = true;	
	protected function get_title_tab()
	{ 	
		return __('Печати для документов', 'usam');
	}
	
	protected function get_data_tab(  )
	{			
		$this->data = ['date_insert' => date("Y-m-d H:i:s")];
	}		
		
	function display_form()
	{	
		$this->display_attachments();
    }
}
?>