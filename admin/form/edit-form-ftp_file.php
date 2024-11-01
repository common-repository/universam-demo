<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_ftp_file extends USAM_Edit_Form
{			
	protected function get_title_tab()
	{ 	
		$title = sprintf( __('Изменить файл &laquo;%s&raquo; от &laquo;%s&raquo;','usam'), $_GET['id'], date("d.m.Y H:i", $this->data['date_insert']) );		
		return $title;
	}	
	
	function get_data_tab()
	{ 
		$this->data['id'] = $_GET['id'];
		$folder = !empty($_GET['f']) ? USAM_EXCHANGE_DIR.sanitize_title($_GET['f']).'/':USAM_EXCHANGE_DIR;
		$this->data['file'] = file_get_contents($folder.$this->data['id']);
		$this->data['date_insert'] = filectime($folder.$this->data['id']);
	}
	
	function display_left()
	{	
		usam_add_box( 'usam_description', __('Содержимое файла','usam'), array( $this, 'file_content' ) );	
    }	
		
	public function file_content(  ) 
	{	
		?>
		<textarea name='file' rows="20" style="width: 100%;"><?php echo htmlspecialchars($this->data['file']); ?></textarea>
		<?php
	}
}
?>