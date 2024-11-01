<?php
class USAM_Tab_webforms extends USAM_Page_Tab
{	
	protected $views = ['table', 'settings'];
	
	public function get_title_tab()
	{			
		if( $this->table == 'webform_properties' )
			return __('Поля веб-форм', 'usam');
		elseif( $this->table == 'webform_property_groups' )
			return __('Группы полей веб-форм', 'usam');
		return __('Веб-формы', 'usam');
	}
	
	public function get_tab_sections() 
	{ 
		$tables = array();
		if ( $this->view == 'settings' )
		{ 
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );		
		}					
		return $tables;
	}
	
	protected function get_tab_forms()
	{	
		if ( $this->table == 'webform_properties' )		
			return [['form' => 'edit', 'form_name' => 'webform_property', 'title' => __('Добавить', 'usam') ]];
		elseif ( $this->table == 'webform_property_groups' )		
			return [['form' => 'edit', 'form_name' => 'webform_property_group', 'title' => __('Добавить', 'usam') ]];		
		elseif ( $this->table == 'webforms' )		
			return [['form' => 'edit', 'form_name' => 'webform', 'title' => __('Добавить веб-форму', 'usam') ]];			
		return array();
	}	
		
	public function get_settings_tabs() 
	{ 
		return array( 'webform_properties' => array('title' => __('Поля веб-форм','usam'), 'type' => 'table' ), 'webform_property_groups' => array( 'title' => __('Группы полей веб-форм','usam'), 'type' => 'table') );
	}
}
?>