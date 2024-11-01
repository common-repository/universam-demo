<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-document_status.php' );
class USAM_Form_contact_status extends USAM_Document_Status_Edit_Form
{	
	function display_left()
	{					
		$this->titlediv( $this->data['name'] );	
		$this->add_box_description( $this->data['description'] );	
		usam_add_box( 'usam_settings_status', __('Параметры','usam'), [$this, 'display_settings']);
    }
}
?>