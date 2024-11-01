<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_seller extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить продавца %s','usam'), $this->data['name'] );
		else
			$title = __('Добавить продавца', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_seller( $this->id );
		else
			$this->data = ['id' => '', 'name' => '', 'status' => '', 'user_id' => ''];			
	}
	
	function display_left()
	{			
		$locations = usam_get_seller_metadata( $this->data['id'], 'locations' );
	//	usam_add_box( 'usam_settings', __('Основные параметры','usam'), array( $this, 'display_settings' ) );
		usam_add_box( 'usam_locations', __('Местоположение работы продавца','usam'), array( $this, 'selecting_locations' ), $locations );		
    }	
	
	function display_right()
	{	
		$title = __('Ответственный','usam');
		$title_button = $this->data['manager_id']?__('Сменить','usam'):__('Выбрать','usam');		
		$title .= "<a href='' data-modal='select_manager' data-screen='user' data-list='manager'  class='js-modal'>$title_button</a>";		
		usam_add_box(['id' => 'usam_manager', 'title' => $title, 'function' => [$this, 'display_manager_metabox'], 'close' => false]);		
    }
			
	function display_settings()
	{
		?>
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Статус','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name = "stFatus">
						<?php 
						$status = usam_get_seller_statuses();					
						foreach ( $status as $key => $name )
						{	 ?>	
							<option value='<?php echo $key; ?>' <?php selected( $key, $this->data['status'] ) ?>><?php echo $name; ?></option>	
						<?php 
						}
						?>	
					</select>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='company_id'><?php _e( 'Название компании','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php 
						$autocomplete = new USAM_Autocomplete_Forms( );
						$autocomplete->get_form_company( $this->data['company_id'], array( 'name' => 'company_id' ) );
					?>	
				</div>
			</div>			
		</div>	
		<?php
    }
}
?>