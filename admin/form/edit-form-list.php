<?php		
require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
require_once( USAM_FILE_PATH .'/includes/feedback/mailing_list.php' );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_list extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить список','usam');
		else
			$title = __('Добавить список', 'usam');	
		return $title;
	}
	
	protected function get_data_tab()
	{ 
		if ( $this->id != null )
			$this->data = usam_get_mailing_list( $this->id );
		else
			$this->data = ['name' => '', 'description' => '', 'view' => 0];			
	}
	
	public function display_settings( )
	{
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_view'><?php esc_html_e( 'Виден для посетителей', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="checkbox" id='option_view' name="view_list" <?php checked( $this->data['view'], 1 ); ?> value="1"/>
				</div>
			</div>
			<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Группа', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<select v-model="data.pricelist">
							<option value="">---</option>
							<?php
							$lists = usam_get_mailing_lists(['parent_id' => 0]);
							foreach ( $lists as $list )
							{
								?><option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option><?php
							}		
							?>	
						</select>
					</div>	
				</div>
		</div>
      <?php
	}      
	
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );		
		$this->add_box_description( $this->data['description'] );			
		usam_add_box( 'usam_settings_list', __('Параметры','usam'), array( $this, 'display_settings' ) );		
    }		
}
?>