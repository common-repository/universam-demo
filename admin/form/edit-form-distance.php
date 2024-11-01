<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_distance extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 
		if ( $this->id != null )			
			$title = sprintf( __('Редактировать расстояние между %s и %s','usam'), $this->data['from_location'], $this->data['to_location'] );
		else
			$title = __('Добавить новое рассотяние', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
		{
			$data = explode('-',$this->id);
			$this->data['from_location_id'] = $data[0];
			$this->data['to_location_id'] = $data[1];
			$this->data['distance'] = usam_get_locations_distance( $this->data['from_location_id'], $this->data['to_location_id'] );
			$this->data['to_location'] = usam_get_full_locations_name( $this->data['to_location_id'] );			
			$this->data['from_location'] = usam_get_full_locations_name( $this->data['from_location_id'] );				
		}
		else	
		{			
			$this->data = array( 'from_location_id' => '', 'to_location_id' => 0, 'distance' => '', 'from_location' => '', 'to_location' => '' );			
		}				
	}
		
	function display_left()
	{				
		usam_add_box( 'usam_location', __('Введите расстояние до пунктов','usam'), array( $this, 'distance_setting' ) );			
    }
	
	function distance_setting()
	{								
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='search_location_1'><?php esc_html_e( 'От', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php 
					$t = new USAM_Autocomplete_Forms();		
					$t->get_form_position_location( $this->data['from_location_id'], array( 'name' => 'from_location_id' ) );
					?>				
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='search_location_1'><?php esc_html_e( 'До', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php 
					$t = new USAM_Autocomplete_Forms();		
					$t->get_form_position_location( $this->data['to_location_id'], array( 'name' => 'to_location_id' ) );
					?>				
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Расстояние', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_sort' name="distance" size = "100" maxlength = "100" value="<?php echo $this->data['distance']; ?>" autocomplete="off">
				</div>
			</div>
		</div>
		<?php	
    }
}
?>