<?php
class USAM_Tab_contacts extends USAM_Tab
{		
	public function __construct()	
	{
		if ( empty($_REQUEST['table']) || $_REQUEST['table'] == 'contacts' )
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
		if ( $this->table == 'contacts_export' )
			return [['form' => 'edit', 'form_name' => 'contact_export', 'title' => __('Добавить', 'usam') ]];	
		elseif ( $this->table == 'contact_import' )		
			return [
				['form' => 'edit', 'form_name' => 'contact_import', 'title' => __('Добавить', 'usam')],
				['form' => 'progress', 'form_name' => 'contact_importer', 'title' => __('Импортировать контакты', 'usam')]
			];
		elseif ( $this->table == 'contact_properties' )		
			return [['form' => 'edit', 'form_name' => 'contact_property', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'contact_property_groups' )		
			return [['form' => 'edit', 'form_name' => 'contact_property_group', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'contact_status' )		
			return [['form' => 'edit', 'form_name' => 'contact_status', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'bonuses' )		
			return [['form' => 'edit', 'form_name' => 'bonus', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'bonus_cards' )		
			return [
				['form' => 'edit', 'form_name' => 'bonus_card', 'title' => __('Добавить карту', 'usam')],
				['action' => 'create_cards', 'title' => __('Создать карты', 'usam')],
			];	
		elseif ( $this->table == 'customer_accounts' )		
			return array(
				array('form' => 'edit', 'form_name' => 'customer_account', 'title' => __('Добавить счет', 'usam') ),
				array('action' => 'create_customer_accounts', 'title' => __('Создать счета', 'usam') ) ,
			);			
		elseif ( $this->table == 'contacts_communication_errors' )		
			return [['action' => 'verify_email', 'title' => __('Проверить email', 'usam')]];
		elseif ( $this->table == 'contacts' )
		{
			$forms = [];
			if ( current_user_can('add_contact') )
				$forms[] = array('form' => 'edit', 'form_name' => 'contact', 'title' => __('Добавить', 'usam') );
			if ( current_user_can('import_contact') )
				$forms[] = array('table' => 'contact_import', 'title' => __('Импорт', 'usam') );
			if ( current_user_can('export_contact') )
				$forms[] = array('table' => 'contacts_export', 'title' => __('Экспорт', 'usam') );
			if ( current_user_can('edit_contact') )
				$forms[] = array('table' => 'contacts_duplicate', 'title' => __('Проверка и очистка', 'usam') );			
			return $forms;			
		}
		return array();
	}	
	
	public function load_tab() 
	{ 
		if($this->table == 'contacts' )
			$this->views = ['table', 'map', 'report', 'settings'];
	}
		
	public function get_tab_sections() 
	{ 
		if ( $this->view == 'settings' )
		{ 
			$tables = $this->get_settings_tabs();
			array_unshift($tables, ['title' => __('Назад','usam')]);		
		}	
		elseif ( $this->table == 'contacts_duplicate' || $this->table == 'contacts_communication_errors' )
		{			
			$tables = ['contacts_duplicate' => ['title' => __('Поиск дубликатов','usam'), 'type' => 'table'], 'contacts_communication_errors' => ['title' => __('Проверка email','usam'), 'type' => 'table']];	
		}
		else
			$tables = ['contacts' => ['title' => __('Контакты','usam'), 'type' => 'table'], 'bonus_cards' => ['title' => __('Бонусные карты','usam'), 'type' => 'table', 'capability' => 'view_bonus_cards'], 'customer_accounts' => ['title' => __('Клиентские счета','usam'), 'type' => 'table', 'capability' => 'view_customer_accounts'], 'carts' => ['title' => __('Корзины','usam'), 'type' => 'table', 'capability' => 'view_carts']];	
		return $tables;
	}
	
	public function get_settings_tabs() 
	{ 
		return ['contact_properties' => ['title' => __('Реквизиты','usam'), 'type' => 'table'], 'contact_property_groups' => ['title' => __('Группы реквизитов','usam'), 'type' => 'table'], 'contact_status' => ['title' => __('Статусы','usam'), 'type' => 'table']];
	}
		
	public function get_title_tab()
	{		
		if ( $this->view == 'map' )
			return __('Контакты на карте', 'usam');
		elseif ( $this->view == 'report' )
			return __('Отчеты по контактам', 'usam');		
		elseif ($this->table == 'contacts_duplicate' )
			return __('Поиск дубликатов', 'usam');
		elseif($this->table == 'contacts_export' )
			return __('Шаблоны экспорта контактов', 'usam');
		elseif($this->table == 'contact_import' )
			return __('Шаблоны импорта контактов', 'usam');
		elseif($this->table == 'contact_properties' )
			return __('Реквизиты контактов', 'usam');
		elseif($this->table == 'contact_property_groups' )
			return __('Группы реквизитов контактов', 'usam');
		elseif($this->table == 'bonuses' )			
			return __('Бонусы клиентов', 'usam');
		elseif($this->table == 'bonus_cards' )
			return __('Бонусные карты клиентов', 'usam');
		elseif($this->table == 'customer_accounts' )
			return __('Клиентские счета', 'usam');		
		elseif($this->table == 'carts' )
			return __('Корзины клиентов', 'usam');		
		elseif($this->table == 'contacts_communication_errors' )
			return __('Проблемные электронные адреса', 'usam');
		else
			return __('Контакты', 'usam');
	}	
}
?>