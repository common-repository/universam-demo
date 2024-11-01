<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/seo/site.class.php' );
class USAM_Form_site extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить сайт &#8220;%s&#8221;','usam'), $this->data['domain'] );
		else
			$title = __('Добавить сайт', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{			
		if ( $this->id !== null )
		{
			$this->data = usam_get_site( $this->id );
		}
		else	
			$this->data = array( 'id' => 0,  'domain' => '', 'description' => '', 'type' => 'c' );
	}	
	
	function display_settings()
	{		
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_domain'><?php esc_html_e( 'Домен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_domain' name="domain" value="<?php echo $this->data['domain']; ?>" />
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_type'><?php _e( 'Клиент','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">				
					<select id = "option_type" name = "type">
						<option value='c' <?php selected($this->data['type'], 'c' ) ?>><?php esc_html_e( 'Конкуренты', 'usam'); ?></option>
					</select>		
				</div>
			</div>	
		</div>		
		<?php			
	}

	function display_left()
	{			
		$this->add_box_description( $this->data['description'], 'description', __('Описание','usam') );			
		usam_add_box( 'usam_settings', __('Настройки','usam'), array( $this, 'display_settings' ) );	
    }
}
?>