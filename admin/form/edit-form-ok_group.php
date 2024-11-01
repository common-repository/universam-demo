<?php	
require_once( USAM_FILE_PATH .'/admin/includes/form/edit-form-social_network.php' );
class USAM_Form_ok_group extends USAM_Form_Social_Network
{		
	public function display_settings( )
	{  				
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'ID вашей группы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input value = "<?php echo $this->data['code'] ?>" type='text' name='code' id = "option_code"/>
				</div>
			</div>			
		</div>
      <?php
	}       
}
?>