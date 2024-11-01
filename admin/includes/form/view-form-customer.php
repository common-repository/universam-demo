<?php	
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_customer extends USAM_View_Form
{	
	protected $ribbon = true;	
	protected function display_contact_online( ) 
	{
		if ( !empty($this->data['online']) ) 
		{ 
			$timestamp = strtotime( $this->data['online'] );						
			if ( $timestamp >= USAM_CONTACT_ONLINE )
			{
				?><span class="item_status_valid item_status status_online"><?php _e("Онлайн","usam"); ?></span><?php
			}
			else
			{	
				$sex = usam_get_contact_metadata($this->id, 'sex');
				$message = $sex == 'f' ? __("Была %s","usam") : __("Был %s","usam");						
				?><span class="item_status_notcomplete item_status status_online" title="<?php printf( $message, usam_local_date($this->data['online']) ); ?>"><?php echo human_time_diff( $timestamp, time() ); ?></span><?php
			}
		} 
	}
	
	protected function display_contact_foto( ) 
	{
		?><div class ="customer_preview__foto image_container"><img src='<?php echo usam_get_contact_foto( $this->id, 'id', [130, 130]) ?>'></div><?php
	}
}
?>