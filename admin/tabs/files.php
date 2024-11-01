<?php
class USAM_Tab_files extends USAM_Tab
{
	protected $views = ['grid', 'table', 'report', 'settings'];
	
	public function get_title_tab()
	{
		if( $this->table == 'file_properties' )
			$title = __('Свойства файлов', 'usam');
		elseif( $this->table == 'file_property_groups' )
			$title = __('Группы свойств файлов', 'usam');
		else
			$title = __('Файлы', 'usam');
		return $title;
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
		if ( $this->table == 'file_properties' )		
			return [['form' => 'edit', 'form_name' => 'file_property', 'title' => __('Добавить', 'usam')]];
		elseif ( $this->table == 'file_property_groups' )		
			return [['form' => 'edit', 'form_name' => 'file_property_group', 'title' => __('Добавить', 'usam') ]];		
		else
			return [['button' => 'add_files', 'title' => __('Добавить файлы', 'usam')], ['button' => 'add_folder', 'title' => __('Добавить папку', 'usam')]];
		return array();
	}	
		
	public function get_settings_tabs() 
	{ 
		return array( 'file_properties' => array('title' => __('Свойства файлов','usam'), 'type' => 'table' ), 'file_property_groups' => array( 'title' => __('Группы свойств файлов','usam'), 'type' => 'table') );
	}
}