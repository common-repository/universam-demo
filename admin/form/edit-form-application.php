<?php
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_application extends USAM_Edit_Form
{	
	protected $group_code = '';
	protected $class_application;
	protected function get_title_tab()
	{ 	
		return '<div class="tab_title_left"><span class="tab_title_text">'.usam_get_name_service( $this->data['service_code'] ).'</span><span class="tab_title_buttons">'.$this->class_application->get_form_buttons().'</span></div>';
	}
	
	protected function get_data_tab(  )
	{
		if ( $this->id )
			$this->data = usam_get_application( $this->id );
		else
		{
			$service_code = isset($_GET['service_code']) ? sanitize_title($_GET['service_code']) : '';
			$this->data = ['id' => 0, 'service_code' => $service_code, 'group_code' => '', 'access_token' => '', 'active' => 0];
		}
		$this->class_application = usam_get_class_application( $this->data );
		if ( $this->class_application === null )
			$this->data = [];
	}
	
	function display_left()
	{		
		usam_add_box( 'usam_individual_settings', __('Индивидуальные настройки','usam'), [$this->class_application, 'display_form']);		
		$this->class_application->display_form_left();
    }
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
		?><input type='hidden' value='<?php echo $this->data['service_code']; ?>' name='service_code' /><?php
    }	
}
?>