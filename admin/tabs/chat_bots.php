<?php
require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_template.class.php' );
require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_command.class.php' );
class USAM_Tab_chat_bots extends USAM_Tab
{			
	public function __construct() 
	{
		$this->views = ['table', 'settings'];		
	}
	
	public function get_title_tab()
	{			
		return __('Чат-боты', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'chat_bot_commands' )
			return [['form' => 'edit', 'form_name' => 'chat_bot_command', 'title' => __('Добавить команду', 'usam')]];		
		else
			return [['form' => 'edit', 'form_name' => 'chat_bot_template', 'title' => __('Добавить шаблон', 'usam')]];					
	}
		
	public function display_section_application( ) 
	{			
		usam_add_box( 'usam_settings', __('Настройка чата', 'usam'), [$this, 'settings_meta_box']);
	//	usam_add_box( 'usam_decor', __('Шаблоны сообщений', 'usam'), [$this, 'decor_meta_box']);	
	}	
		
	public function settings_meta_box()
	{
		$contacts = usam_get_contacts(['fields' => ['id', 'appeal'], 'source' => 'employee']);		
		$managers = [];
		foreach ( $contacts as $contact ) 
		{					
			$managers[$contact->id] = $contact->appeal;
		}		
		$options = [
			['key' => 'contact_id','type' => 'select', 'title' => __('От имени', 'usam'), 'option' => 'chat_bot', 'options' => $managers],					
		]; 		  
		$this->display_table_row_option( $options ); 
	}	
}