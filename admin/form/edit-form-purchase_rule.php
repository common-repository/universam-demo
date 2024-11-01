<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );	
class USAM_Form_purchase_rule extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить правило &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить правило', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_data($this->id, 'usam_purchase_rules');
		else
			$this->data = array( 'name' => '', 'active' => 0, 'description' => '', 'status' => 0, 'conditions' => array() );
	}		
  	
	function display_left()
	{					
		$this->titlediv( $this->data['name'] );
		$this->add_box_description( $this->data['description'], 'description', __('Показать покупателю сообщение','usam'));				
		usam_add_box( 'usam_condition', __('Условия выполнения правила','usam'), array( $this, 'display_rules_work_basket' ), $this->data['conditions'] );	
    }		
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
    }
}
?>