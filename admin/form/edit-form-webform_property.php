<?php		
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-property.php' );
class USAM_Form_webform_property extends USAM_Form_Property
{		
	protected $property_type = 'webform';
	public function display_property_connection( )
	{	
		?>	
		<div class="edit_form" >
			<?php $this->display_connection(); ?>			
		</div>
		<?php
	}   
	
	function display_left()
	{						
		$this->titlediv( $this->data['name'] );
		$this->add_box_description( $this->data['description'] );
		usam_add_box( 'usam_order_property_settings', __('Параметры','usam'), array( $this, 'display_settings' ) );
		usam_add_box( 'usam_data_type', __('Тип данных','usam'), array( $this, 'display_data_type' ) );	  						
		usam_add_box( 'usam_property_connection', __('Связь между свойствами','usam'), array( $this, 'display_property_connection' ) );	
    }		
}
?>