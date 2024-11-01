<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_language extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить язык &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить язык', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{		
		if ( $this->id != null )
		{				
			$this->data = usam_get_data( $this->id, 'usam_languages' );			
		}
		else
			$this->data = array( 'name' => '', 'code' => '', 'sort' => 10 );		
	}	 

	function settings_meta_box() 
	{		
		?>				
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="option_code"><?php _e( 'Код','usam'); ?></label>:</div>
				<div class ="edit_form__item_option">							
					<input type='text' id='option_code' value='<?php echo $this->data['code']; ?>' name='code'/>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="option_sort"><?php _e( 'Сортировка','usam'); ?></label>:</div>
				<div class ="edit_form__item_option">							
					<input type='text' id='option_sort' value='<?php echo $this->data['sort']; ?>' name='sort'/>
				</div>
			</div>	
		</div>		
		<?php 
	}    
	
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );		
		usam_add_box( 'usam_description', __('Настройка', 'usam'), array( $this, 'settings_meta_box' ) );	
    }	
}
?>