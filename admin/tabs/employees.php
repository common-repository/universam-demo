<?php
class USAM_Tab_employees extends USAM_Page_Tab
{		
	protected $views = ['table', 'grid', 'report', 'settings'];		
	
	public function get_title_tab()
	{		
		if ( $this->view == 'grid' )
			return __('Сотрудники в отделах', 'usam');
		elseif ( $this->table == 'departments' )
			return __('Отделы', 'usam');
		elseif ( $this->table == 'time' )
			return __('Учет рабочего времени', 'usam');		
		elseif ($this->table == 'work' )
			return __('Выполненная менеджерами работа', 'usam');
		elseif($this->table == 'bonus_employee' )
			return __('Система мотивации сотрудников', 'usam');		
		else
			return __('Сотрудники', 'usam');
	}	
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'departments' )	
			return array( array('form' => 'edit', 'form_name' => 'department', 'title' => __('Добавить отдел', 'usam') ) );		
		elseif ( $this->table == 'employee_properties' )		
			return [['form' => 'edit', 'form_name' => 'employee_property', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'employee_property_groups' )		
			return [['form' => 'edit', 'form_name' => 'employee_property_group', 'title' => __('Добавить', 'usam')]];			
		elseif ( $this->table == 'employees' || $this->view == 'grid' )	
		{
			$forms = [];
			if ( current_user_can('add_employee') )
				$forms[] = ['form' => 'edit', 'form_name' => 'employee', 'title' => __('Добавить сотрудника', 'usam')];
			if ( current_user_can('add_department') )
				$forms[] = ['form' => 'edit', 'form_name' => 'department', 'title' => __('Добавить отдел', 'usam')];
			return $forms;					
		}
		return array();
	}
	
	public function get_tab_sections() 
	{ //'capability' => 'view_telephony'
		$tables = ['employees' => ['title' => __('Сотрудники','usam'), 'type' => 'table'], 'work' => ['title' => __('Работа','usam'), 'type' => 'table'], 'departments' => ['title' => __('Отделы','usam'), 'type' => 'table'], 'telephony' => ['title' => __('Записанные разговоры','usam'), 'type' => 'table'], 'bonus_employee' => ['title' => __('Мотивация','usam'), 'type' => 'table'],
		//'time' => array( 'title' => __('Учет рабочего времени','usam'), 'type' => 'table' ) 
		];	
		return $tables;
	}
	
	public function get_settings_tabs() 
	{ 
		return ['settings' => ['title' => __('Настройки','usam'), 'type' => 'section'], 'employee_properties' => ['title' => __('Реквизиты','usam'), 'type' => 'table'], 'employee_property_groups' => ['title' => __('Группы реквизитов','usam'), 'type' => 'table'], 'employee_status' => ['title' => __('Статусы','usam'), 'type' => 'table']];
	}
		
	public function display_section_settings( ) 
	{			
		usam_add_box( 'usam_settings', __('Настройка мотивации', 'usam'), array( $this, 'settings_meta_box' ) );
	}
	
	public function settings_meta_box()
	{	
		require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
		$type_prices = array( );
		foreach ( usam_get_prices( ) as $price )
		{	
			$type_prices[$price['code']] = $price['title'];
		}		
		$options = array(    	
			array( 'key' => 'active', 'type' => 'checkbox', 'title' => __('Включить мотивацию', 'usam'), 'option' => 'motivation_employees'),
			array( 'key' => 'type_price', 'type' => 'select', 'title' => __('Код цены', 'usam'), 'option' => 'motivation_employees', 'options' => $type_prices, 'description' => __('Цена для расчета бонусов менеджеру за выполненный заказ', 'usam')),
		); 		  
		$this->display_table_row_option( $options ); 
	}	
}
?>