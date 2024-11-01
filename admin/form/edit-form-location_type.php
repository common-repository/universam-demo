<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_location_type extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить тип местоположений','usam');
		else
			$title = __('Добавить тип местоположений', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
		{
			$this->data = usam_get_type_location( $this->id );						
		}
		else	
		{			
			$this->data = array( 'name' => '', 'code' => '', 'sort' => 100 );				
		}
	}
	
	function setting_display()
	{			
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_code' name="code" size = "100" maxlength = "100" value="<?php echo $this->data['code']; ?>" autocomplete="off">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_sort' name="sort" size = "100" maxlength = "100" value="<?php echo $this->data['sort']; ?>" autocomplete="off">
				</div>
			</div>
		</div>	
		<?php
    }
	
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );
		usam_add_box( 'usam_location_type', __('Настройка типа местоположения','usam'), array( $this, 'setting_display' ) );			
    }	
}
?>