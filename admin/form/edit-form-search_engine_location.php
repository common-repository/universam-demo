<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_search_engine_location extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить местоположение','usam');
		else
			$title = __('Добавить местоположение', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{		
		if ( $this->id != null )
		{				
			$this->data = usam_get_data( $this->id, 'usam_search_engine_location' );			
		}
		else
			$this->data = array( 'search_engine' => '', 'location' => '' );		
	}	     
	
	function display_left()
	{				
		usam_add_box( 'usam_description', __('Настройка', 'usam'), array( $this, 'settings_meta_box' ) );	
    }		
	
	function settings_meta_box() 
	{
		$bots = usam_get_site_bots( );
		if ( $this->id )
		{
			$option = get_site_option('usam_search_engine_location');
			$locations = maybe_unserialize( $option );	
			foreach( $locations as $location )	
			{
				unset($bots[$location['search_engine']]);
			}
		}			
		?>				
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="search_engine"><?php esc_html_e( 'Поисковые системы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="search_engine" id="search_engine">					
						<?php						
						foreach( $bots as $id => $name )
						{						
							?><option value="<?php echo $name; ?>" <?php selected($name, $this->data['search_engine']) ?>><?php echo $name; ?></option><?php
						}		
						?>				
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="search_location_1"><?php esc_html_e( 'Местоположение', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php
					$autocomplete = new USAM_Autocomplete_Forms( );
					$autocomplete->get_form_position_location( $this->data['location'] );
					?>	
				</div>
			</div>
		</div>		
		<?php 
	}
}
?>