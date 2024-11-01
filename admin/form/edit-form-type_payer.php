<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_type_payer extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить тип &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить тип', 'usam');	
		return $title;
	}
		
	protected function get_data_tab()
	{		
		if ( $this->id != null )					
			$this->data = usam_get_payer( $this->id );	
		else
			$this->data = array( 'name' => '', 'active' => 1, 'type' => 'contact', 'sort' => 10 );
	}
	
	function display_setting()
	{ 
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" autocomplete="off" id="option_sort" value="<?php echo $this->data['sort']; ?>" name="sort"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_type'><?php esc_html_e( 'Тип платильщика', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="type" id='option_type'>
						<option value="contact" <?php selected($this->data['type'], 'contact') ?> ><?php _e('Физическое лицо','usam'); ?></option>
						<option value="company" <?php selected($this->data['type'], 'company') ?> ><?php _e('Юридическое лицо','usam'); ?></option>
					</select>	
				</div>
			</div>
		</div>	
		<?php
	}
	
	function display_left()
	{				
		$this->titlediv( $this->data['name'] );		
		usam_add_box( 'usam_setting', __('Общие настройки', 'usam'), array( $this, 'display_setting' ) );			
    }
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
    }
}
?>