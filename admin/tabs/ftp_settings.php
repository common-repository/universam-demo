<?php
class USAM_Tab_ftp_settings extends USAM_Tab
{	
	protected $views = ['simple'];
	public function __construct() 
	{
		add_action( 'pre_update_option_usam_ftp_settings', array($this, 'update_option_usam_ftp_settings'), 10, 3 );
	}
	
	public function get_title_tab()
	{					
		return __('Настройка FTP сервера', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['action' => 'ftp_test', 'title' => __('Проверить соединение', 'usam')]];			
	}
	
	public function update_option_usam_ftp_settings( $value, $old_value, $option ) 
	{
		if ( !empty($value['pass']) && $value['pass'] == '******' )	
			$value['pass'] = $old_value['pass'];
		else							
			$value['pass'] = $value['pass'];
		
		return $value;
	}	
	
	public function display() 
	{		
		usam_add_box( 'usam_ftp_settings', __('Настройка подключения к FTP', 'usam'), array( $this, 'ftp_settings' ) );		
	//	usam_add_box( 'usam_ftp_file_settings', __('Настройка файлов обмена', 'usam'), array( $this, 'ftp_file_settings' ) );
	}		

	public function ftp_settings() 
	{			
		$options = array( 
			array( 'key' => 'host', 'type' => 'input', 'title' => __('Сервер', 'usam'), 'option' => 'ftp_settings', 'description' => ''),	
			array( 'key' => 'port', 'type' => 'input', 'title' => __('Порт', 'usam'), 'option' => 'ftp_settings', 'description' => ''),	
			array( 'key' => 'timeout', 'type' => 'input', 'title' => __('Таймаут соединения', 'usam'), 'option' => 'ftp_settings', 'description' => ''),	
			array( 'key' => 'user', 'type' => 'input', 'title' => __('Имя пользователя', 'usam'), 'option' => 'ftp_settings', 'description' => ''),	
			array( 'key' => 'pass', 'type' => 'password', 'title' => __('Пароль', 'usam'), 'option' => 'ftp_settings', 'description' => ''),	
			array( 'key' => 'mode', 'type' => 'radio', 'title' => __('Режим', 'usam'), 'option' => 'ftp_settings',  'description' => '',  'radio' => array( '0' => __('Активный', 'usam'), '1' => __('Пассивный', 'usam'),), ),
			array( 'key' => 'get_mode', 'type' => 'radio', 'title' => __('Режим передачи', 'usam'), 'option' => 'ftp_settings',  'description' => '',  'radio' => array( 'FTP_BINARY' => 'FTP_BINARY', 'FTP_ASCII' => 'FTP_ASCII' ), ),
		);
		$this->display_table_row_option( $options );
	}
	
	public function ftp_file_settings() 
	{			
		$options = array( 
			array( 'key' => 'export_encoding', 'type' => 'select', 'title' => __('Кодировка файла экспорта', 'usam'), 'option' => 'ftp_settings', 'description' => '', 'options' => array( 'utf-8' => 'utf-8', 'windows-1251' => 'windows-1251' ) ),	
			array( 'key' => 'import_encoding', 'type' => 'select', 'title' => __('Кодировка файла импорта', 'usam'), 'option' => 'ftp_settings', 'description' => '',  'options' => array( 'utf-8' => 'utf-8', 'windows-1251' => 'windows-1251' ) ),
			array( 'key' => 'separator', 'type' => 'input', 'title' => __('Разделитель', 'usam'), 'option' => 'ftp_settings', 'description' => ''),				
		);
		$this->display_table_row_option( $options );
	}
}
?>