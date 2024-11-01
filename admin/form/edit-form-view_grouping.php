<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_view_grouping extends USAM_Edit_Form
{
	protected function get_data_tab(  )
	{		
		if ( $this->id != null )
		{				
			$this->data = usam_get_data( $this->id, 'usam_order_view_grouping' );			
		}
		else
			$this->data = array( 'name' => '', 'type_prices' => array() );		
	}	
	
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );	
		usam_add_box( 'usam_prices', __('Цены, на которые установить','usam'), array( $this, 'selecting_type_prices' ) );			
    }
}
?>