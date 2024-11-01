<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_license extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		return __('Активировать лицензию', 'usam');	
	}
	
	protected function get_data_tab(  )
	{			
		if ( $this->id !== null )
			$this->data = usam_get_license( $this->id );
		else	
			$this->data = ['license' => '', 'title' => '', 'license_holder' => '']; 
	}	

	function display_settings()
	{		
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_license'><?php esc_html_e( 'Лицензия', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_license' required name="license" autocomplete="off" value="<?php echo $this->data['license']; ?>"/>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='license_holder'><?php esc_html_e( 'Название компании или ФИО владельца', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="license_holder" name="license_holder" value="<?php echo $this->data['license_holder']; ?>"/>
				</div>
			</div>			
		</div>		
		<?php			
	}

	function display_left()
	{			
		usam_add_box( 'usam_settings', __('Добавление лицензии','usam'), array( $this, 'display_settings' ) );	
    }	
}
?>