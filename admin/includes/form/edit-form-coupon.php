<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Coupon_Code extends USAM_Edit_Form
{	
	protected function get_data_tab(  )
	{			
		if ( $this->id != null )
		{
			$this->data = usam_get_coupon( $this->id );			
		}
		else	
			$this->data = ['coupon_code' => usam_generate_coupon_code(), 'description' => '', 'action' => '', 'max_is_used' => 0, 'value' => '', 'active' => 0, 'start_date' => date('Y-m-d H:i:s'), 'end_date' => date('Y-m-d H:i:s', time()+3600*24*360),  'is_percentage' => 0, 'user_id' => 0, 'amount_bonuses_author' => 0, 'coupon_type' => 'coupon'];			
	}		
		
	function display_users_metabox()
	{		
		$this->display_user_block( $this->data['user_id'], 'user', __('Нет пользователя','usam') );
	}
}
?>