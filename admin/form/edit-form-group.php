<?php		
require_once( USAM_FILE_PATH . '/includes/crm/group.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Group extends USAM_Edit_Form
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить группу &#8220;%s&#8221;','usam'), $this->data['name'] );
		else
			$title = __('Добавить группу', 'usam');	
		return $title;
	}
		
	protected function get_data_tab(  )
	{				
		if ( $this->id != null )		
			$this->data = usam_get_group( $this->id );		
		else
			$this->data = array( 'name' => '', 'code' => '', 'type' => '', 'sort' => '' );		
	}	
	
	public function setting( )	
	{  		
		?>	
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='property_code'><?php _e( 'Код','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='property_code' name='code' value="<?php echo $this->data['code']; ?>"/>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='property_sort'><?php _e( 'Сортировка','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='property_sort' name='sort' value="<?php echo $this->data['sort']; ?>"/>
				</div>
			</div>
		</div>
      <?php
	} 	
	
	function display_left()
	{						
		$this->titlediv( $this->data['name'] );	
		usam_add_box( 'usam_document_setting', __('Параметры','usam'), array( $this, 'setting' ) );
    }		
}
?>