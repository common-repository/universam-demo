<?php
class USAM_Tab_directories extends USAM_Tab
{
	protected $views = ['settings'];	
	public function get_tab_sections() 
	{ 
		if ( $this->view == 'settings' )
			$tables = $this->get_settings_tabs();
		else 
			$tables = array();
		return $tables;
	}
	
	public function get_settings_tabs() 
	{ 
		$table = ['phones' => ['title' => __('Телефоны','usam'), 'type' => 'table'], 'prices' => ['title' => __('Цены','usam'), 'type' => 'table'], 'location' => ['title' => __('Местоположения','usam'), 'type' => 'table'], 'location_type' => ['title' => __('Типы местоположений','usam'), 'type' => 'table'], 'languages' => ['title' => __('Языки сайта','usam'), 'type' => 'table'], 'currency' => ['title' => __('Валюты','usam'), 'type' => 'table'], 'countries' => ['title' => __('Страны','usam'), 'type' => 'table'], 'distance' => ['title' => __('Расстояния','usam'), 'type' => 'table'], 'sales_area' => ['title' => __('Мультирегиональность','usam'), 'type' => 'table'], 'units' => ['title' => __('Единицы измерения','usam'), 'type' => 'table']];
		if ( usam_is_license_type('FREE') )
			unset($table['sales_area']);
		return $table;
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'prices' )		
			return array( array('form' => 'edit', 'form_name' => 'price', 'title' => __('Добавить', 'usam') ) );
		elseif ( $this->table == 'location' )		
			return array( array('form' => 'edit', 'form_name' => 'location', 'title' => __('Добавить местоположение', 'usam') ), array('form' => 'import', 'form_name' => 'location', 'title' => __('Импорт местоположений', 'usam') ) );	
		elseif ( $this->table == 'location_type' )		
			return array( array('form' => 'edit', 'form_name' => 'location_type', 'title' => __('Добавить', 'usam') ) );
		elseif ( $this->table == 'units' )		
			return array( array('form' => 'edit', 'form_name' => 'unit', 'title' => __('Добавить единицу', 'usam') ) );	
		elseif ( $this->table == 'sales_area' )		
			return array( array('form' => 'edit', 'form_name' => 'sales_area', 'title' => __('Добавить регион', 'usam') ) );
		elseif ( $this->table == 'currency' )		
			return array( array('form' => 'edit', 'form_name' => 'currency', 'title' => __('Добавить', 'usam') ) );
		elseif ( $this->table == 'languages' )		
			return array( array('form' => 'edit', 'form_name' => 'language', 'title' => __('Добавить', 'usam') ) );
		elseif ( $this->table == 'countries' )		
			return array( array('form' => 'edit', 'form_name' => 'country', 'title' => __('Добавить', 'usam') ) );			
		elseif ( $this->table == 'phones' )		
			return array( array('form' => 'edit', 'form_name' => 'phone', 'title' => __('Добавить', 'usam') ) );			
		elseif ( $this->table == 'distance' )		
			return array( array('form' => 'edit', 'form_name' => 'distance', 'title' => __('Добавить', 'usam') ) );
		return array();
	}	
	
	public function get_title_tab() 
	{ 		
		if ( $this->table == 'prices' )		
			return __('Цены', 'usam');
		elseif ( $this->table == 'location' )		
			return __('Местоположения', 'usam');	
		elseif ( $this->table == 'location_type' )		
			return __('Типы местоположений', 'usam');
		elseif ( $this->table == 'sales_area' )		
			return __('Мультирегиональность', 'usam');	
		elseif ( $this->table == 'units' )		
			return __('Единицы измерения', 'usam');
		elseif ( $this->table == 'currency' )		
			return __('Валюты', 'usam');	
		elseif ( $this->table == 'languages' )		
			return __('Языки сайта', 'usam');
		elseif ( $this->table == 'countries' )		
			return __('Страны', 'usam');
		elseif ( $this->table == 'phones' )		
			return __('Телефоны', 'usam');
		elseif ( $this->table == 'distance' )		
			return __('Расстояние между местоположениями', 'usam');	
		else
			return __('Справочники', 'usam');		
	}
}