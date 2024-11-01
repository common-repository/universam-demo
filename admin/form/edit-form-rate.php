<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/directory/currency_rate.class.php' );
class USAM_Form_Rate extends USAM_Edit_Form
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить валютный курс','usam');
		else
			$title = __('Добавить валютный курс', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_currency_rate( $this->id );	
		else
			$this->data = array('id' => '', 'basic_currency' => '', 'currency' => '', 'rate' => 1, 'autoupdate' => 0, 'markup' => '');
	}	
	
	function show_setting()
	{			
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_basic_currency'><?php esc_html_e( 'Базовая валюта', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_select_currencies( $this->data['basic_currency'], array('name' => 'basic') ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_currency'><?php esc_html_e( 'Валюта', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_select_currencies( $this->data['currency'], array('name' => 'currency') ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_rate'><?php esc_html_e( 'Курс', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_rate' name="rate" required value="<?php echo $this->data['rate']; ?>" />
				</div>
			</div>			
		</div>		
		<?php	
	}
	
	function show_autoupdate()
	{			
		?>
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_autoupdate'><?php esc_html_e( 'Включить', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' <?php checked( $this->data['autoupdate'], 1 ); ?> id='option_autoupdate' name='autoupdate' value="1"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_markup'><?php esc_html_e( 'Наценка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_markup' name="markup" value="<?php echo $this->data['markup']; ?>" />
				</div>
			</div>
		</div>		
		<?php	
	}

	function display_left()
	{			
		usam_add_box( 'usam_show_setting', __('Валютный курс','usam'), array( $this, 'show_setting' ) );		
		usam_add_box( 'usam_show_autoupdate', __('Автообновление курса','usam'), array( $this, 'show_autoupdate' ) );			
    }
}
?>