<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Calendar extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить календарь &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить календарь', 'usam');	
		return $title;
	}	
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )					
			$this->data = usam_get_data($this->id, 'usam_calendars');
		else	
			$this->data = array( 'name' => '', 'sort' => 100 );
	}	
		
    public function display_settings( )
	{		
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Сортировка', 'usam');  ?>:</div>
				<div class ="edit_form__item_option"><input type="text" name="sort" autocomplete="off" value="<?php echo $this->data['sort']; ?>"/></div>
			</div>
		</div>
      <?php
	}      
	
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );	
		usam_add_box( 'usam_setting_calendar', __('Параметры','usam'), array( $this, 'display_settings' ) );		
    }
}
?>