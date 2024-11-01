<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_position extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		return __('Добавить ключевые слова','usam');
	}
	
	protected function get_data_tab( ) 	
	{	
		$this->data = ['keywords' => ''];
	}	
	
	protected function toolbar_buttons( ) 
	{
		
	}
	
	function display_left()
	{					
		?>		
		<table class = "keyword">							
			<tr>				
				<td><textarea rows="15" cols="100" id="keywords" name="keywords"></textarea></td>				
			</tr>	
		</table>			
		<?php		
		submit_button( __('Добавить', 'usam'), 'primary', 'save', false, ['id' => 'add-keywords']);		
	}	
}
?>