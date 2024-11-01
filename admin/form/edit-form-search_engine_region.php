<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_search_engine_region extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить местоположение &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить местоположение', 'usam');	
		return $title;
	}	
	
	protected function get_data_tab(  )
	{		
		if ( $this->id != null )
		{				
			global $wpdb;
			$this->data = $wpdb->get_row( "SELECT * FROM ".USAM_TABLE_SEARCH_ENGINE_REGIONS." WHERE id = '".$this->id."'", ARRAY_A );			
		}
		else
			$this->data = array( 'location_id' => '', 'name' => '', 'code' => '', 'search_engine' => '', 'active' => 0, 'sort' => 100 );		
	}	     
	
	function display_left()
	{			
		?>
		<div class="location_site">
			<?php
			$autocomplete = new USAM_Autocomplete_Forms( );
			$autocomplete->get_form_position_location( $this->data['location_id'], array( 'code' => 'all' ) );			
			?>
		</div>
		<?php		
		usam_add_box( 'usam_locations', __('Параметры региона','usam'), array( $this, 'settings_locations' ) );		
    }		
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
    }
	
	function settings_locations( )
	{			
		?>		
		<div class="edit_form">	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Код региона в поисковой системе', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" for='option_code' value="<?php echo $this->data['code']; ?>" name="code"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_name'><?php esc_html_e( 'Название региона в поисковой системе', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_name' value="<?php echo htmlspecialchars($this->data['name']); ?>" name="name"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_search_engine'><?php esc_html_e( 'Поисковая система', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name = "search_engine" id='option_search_engine'>
						<option value='g' <?php selected('g',$this->data['search_engine']); ?>><?php esc_html_e( 'Google', 'usam');  ?></option>
						<option value='y' <?php selected('y',$this->data['search_engine']); ?>><?php esc_html_e( 'Яндекс', 'usam');  ?></option>
					</select>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_sort' value="<?php echo $this->data['sort']; ?>" name="sort" autocomplete="off" />
				</div>
			</div>
		</div>		
		<?php
	}		
}
?>