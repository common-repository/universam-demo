<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_sales_area extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить регион &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить регион', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{		
		if ( $this->id != null )
		{				
			$this->data = usam_get_data( $this->id, 'usam_sales_area' );			
		}
		else
			$this->data = array( 'name' => '', 'locations' => array() );		
	}	     
	
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );		
		usam_add_box( 'usam_locations', __('Местоположение','usam'), array( $this, 'selecting_locations' ), $this->data['locations'] );		
    }	
}
?>