<?php
class USAM_Tab_companies extends USAM_Tab
{		
	public function __construct()	
	{		
		if ( empty($_REQUEST['table']) || $_REQUEST['table'] == 'companies' )
		{
			$this->views = [];
			if ( current_user_can( 'list_crm' ) )
				$this->views[] = 'table';			
			if ( current_user_can( 'map_crm' ) )
				$this->views[] = 'map';	
			if ( current_user_can( 'report_crm' ) )
				$this->views[] = 'report';	
			if ( current_user_can( 'setting_crm' ) )
				$this->views[] = 'settings';			
		}	
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'company_export' )		
			return [['form' => 'edit', 'form_name' => 'company_export', 'title' => __('Добавить', 'usam')]];	
		elseif ( $this->table == 'company_import' )		
			return [
				['form' => 'edit', 'form_name' => 'company_import', 'title' => __('Добавить', 'usam')],
				['form' => 'progress', 'form_name' => 'company_importer', 'title' => __('Импортировать компании', 'usam')]
			];
		elseif ( $this->table == 'company_status' )		
			return [['form' => 'edit', 'form_name' => 'company_status', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'company_properties' )		
			return [['form' => 'edit', 'form_name' => 'company_property', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'company_property_groups' )		
			return [['form' => 'edit', 'form_name' => 'company_property_group', 'title' => __('Добавить', 'usam')]];		
		elseif ( $this->table == 'companies_communication_errors' )		
			return [['action' => 'verify_email', 'title' => __('Проверить email', 'usam')]];
		elseif ( $this->table == 'companies' )		
			return [
				['form' => 'edit', 'form_name' => 'company', 'title' => __('Добавить', 'usam')],			
				['table' => 'company_import', 'title' => __('Импорт', 'usam')],
				['table' => 'company_export', 'title' => __('Экспорт', 'usam')],
				['table' => 'company_duplicate', 'title' => __('Найти дубликаты', 'usam')],
			];			
		return [];
	}	
	
	public function get_tab_sections() 
	{ 
		if ( $this->view == 'settings' )
		{
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );	
		}
		elseif ( $this->table == 'company_duplicate' || $this->table == 'companies_communication_errors' )
		{			
			$tables = ['company_duplicate' => ['title' => __('Поиск дубликатов','usam'), 'type' => 'table'], 'companies_communication_errors' => ['title' => __('Проверка email','usam'), 'type' => 'table']];	
		}
		else
			$tables = array();
		return $tables;
	}
	
	public function get_settings_tabs() 
	{ 
		return ['company_properties' => ['title' => __('Реквизиты компаний','usam'), 'type' => 'table'], 'company_property_groups' => ['title' => __('Группы реквизитов компаний','usam'), 'type' => 'table'], 'company_status' => ['title' => __('Статусы компаний','usam'), 'type' => 'table']];
	}
			
	public function get_title_tab()
	{			
		if ( $this->view == 'map' )
			return __('Компании на карте', 'usam');
		elseif ( $this->view == 'report' )
			return __('Отчеты по компаниям', 'usam');		
		elseif ($this->table == 'company_duplicate' )
			return __('Поиск дубликатов', 'usam');
		elseif ($this->table == 'company_export' )
			return __('Шаблоны экспорта компании', 'usam');
		elseif ($this->table == 'company_import' )
			return __('Шаблоны импорта компаний', 'usam');
		elseif ($this->table == 'company_properties' )
			return __('Реквизиты компаний', 'usam');
		elseif ($this->table == 'company_property_groups' )
			return __('Группы реквизитов компаний', 'usam');		
		elseif ($this->table == 'companies_communication_errors' )
			return __('Проблемные электронные адреса', 'usam');			
		else
			return __('Компании', 'usam');		
	}	
}
?>