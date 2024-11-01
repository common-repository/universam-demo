<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_currency extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить валюту','usam');
		else
			$title = __('Добавить валюту', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
		{			
			$this->data = usam_get_currency( $this->id );
		}
		else	
		{
			$this->data = array( 'name' => '', 'symbol' => '', 'symbol_html' => '', 'code' => '', 'numerical' => '', 'display_currency' => 0 );
		}						
	}	
	
	public function display_settings( )
	{		
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='currency_isocode'><?php esc_html_e( 'Буквенный код ISO', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="currency_isocode" maxlength='3' type="text" name="code" value="<?php echo $this->data['code']; ?>" required>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='currency_code'><?php esc_html_e( 'Числовой код ISO', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="currency_code" maxlength='3' type="text" name="numerical" value="<?php echo $this->data['numerical']; ?>" required>
				</div>
			</div>
		</div>
      <?php
	}      
	
	public function display_currency( )
	{		
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='currency_symbol'><?php esc_html_e( 'Символ', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="currency_symbol" type="text" name="symbol" autocomplete="off" value="<?php echo $this->data['symbol']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='currency_symbol_html'><?php esc_html_e( 'HTML код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input id="currency_symbol_html" type="text" name="symbol_html" value="<?php echo $this->data['symbol_html']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Отображение', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<label><input type="radio" <?php checked( $this->data['display_currency'], 1 ); ?> name="display_currency" value="1"> <?php _e( 'Да', 'usam'); ?></label>&nbsp;&nbsp;
					<label><input type="radio" <?php checked( $this->data['display_currency'], 0 ); ?>name="display_currency" value="0"> <?php _e( 'Нет', 'usam'); ?></label>
				</div>
			</div>
		</div>
      <?php
	}      
		
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );	
		usam_add_box( 'usam_settings', __('Параметры','usam'), array( $this, 'display_settings' ) );	
		usam_add_box( 'usam_display_currency', __('Отображение валюты','usam'), array( $this, 'display_currency' ) );
    }	
}
?>