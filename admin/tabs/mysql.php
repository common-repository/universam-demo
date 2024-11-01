<?php
class USAM_Tab_mysql extends USAM_Page_Tab
{	
	protected $views = ['table', 'settings'];	
	public function get_title_tab()
	{			
		if ( $this->view == 'settings' )
			return __('Настройка интеграции', 'usam');
		else
			return __('Загрузка товаров из другой платформы &laquo;Универсам&raquo;', 'usam');
	}
	
	public function get_settings_tabs() 
	{ 
		return ['mysql_settings' => ['title' => __('Настройка MySQL','usam'), 'type' => 'section']];
	}
	
	protected function display_section_mysql_settings() 
	{		
		usam_add_box( 'usam_application', __('Настройка MySQL', 'usam'), array( $this, 'mysql_settings_meta_box' ) );	
	}
	
	public function mysql_settings_meta_box()
	{		
		$options = array( 
			['key' => 'user', 'type' => 'input', 'title' => __('Имя пользователя', 'usam'), 'option' => 'exchange_mysql_settings'],
			['key' => 'pass', 'type' => 'input', 'title' => __('Пароль', 'usam'), 'option' => 'exchange_mysql_settings'],
			['key' => 'db', 'type' => 'input', 'title' => __('Имя базы', 'usam'), 'option' => 'exchange_mysql_settings'],
			['key' => 'prefix', 'type' => 'input', 'title' => __('Префикс', 'usam'), 'option' => 'exchange_mysql_settings'],
			['key' => 'host', 'type' => 'input', 'title' => __('Хост', 'usam'), 'option' => 'exchange_mysql_settings'],			
		);
		$this->display_table_row_option( $options );
	}	
}