<?php		
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-property.php' );
class USAM_Form_file_property extends USAM_Form_Property
{		
	function display_left()
	{						
		$this->titlediv( $this->data['name'] );	
		$this->add_box_description( $this->data['description'] );
		usam_add_box( 'usam_document_setting', __('Параметры','usam'), [$this, 'display_settings'] );
		usam_add_box( 'usam_data_type', __('Тип данных','usam'), [$this, 'display_data_type'] );	
    }
}
?>