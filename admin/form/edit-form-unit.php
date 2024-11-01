<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_unit extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить единицу','usam');
		else
			$title = __('Добавить единицу', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_data($this->id, 'usam_units_measure', false);
		else	
			$this->data = ['title' => '', 'short' => '', 'code' => '', 'accusative' => '', 'in' => '', 'plural' => '', 'external_code' => '', 'international_code' => ''];
	}	
	
	public function display_settings( )
	{		
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_short'><?php esc_html_e( 'Краткое обозначение', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_short" maxlength='6' type="text" name="short" value="<?php echo $this->data['short']; ?>" required />
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_code" maxlength='6' type="text" name="code" value="<?php echo $this->data['code']; ?>" required />
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_international_code'><?php esc_html_e( 'Международный код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_international_code" type="text" name="international_code" value="<?php echo $this->data['international_code']; ?>"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_numerical'><?php esc_html_e('Цифровой код ОКЕИ', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_numerical" type="text" name="numerical" value="<?php echo $this->data['numerical']; ?>"/>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_plural'><?php esc_html_e( 'Название во множественном', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_plural" type="text" name="plural" value="<?php echo $this->data['plural']; ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_accusative'><?php esc_html_e( 'Название в родительном', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_accusative" type="text" name="accusative" value="<?php echo $this->data['accusative']; ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_in'><?php esc_html_e( 'Название в чем товар', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_in" type="text" name="in" value="<?php echo $this->data['in']; ?>"/>
				</div>
			</div>							
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_external_code'><?php esc_html_e( 'Внешний код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="option_external_code" type="text" name="external_code" value="<?php echo $this->data['external_code']; ?>" />
				</div>
			</div>			
		</div>
      <?php
	}      
			
	function display_left()
	{			
		$this->titlediv( $this->data['title'] );	
		usam_add_box( 'usam_settings', __('Параметры','usam'), array( $this, 'display_settings' ) );	
    }	
}
?>