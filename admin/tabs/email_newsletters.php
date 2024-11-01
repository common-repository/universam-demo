<?php
class USAM_Tab_email_newsletters extends USAM_Page_Tab
{
	public function get_title_tab()
	{			
		if ( $this->view == 'report' )
			return __('Отчет по рассылкам', 'usam');	
		elseif ( $this->table == 'trigger_email_newsletters' )
			return __('Триггерные email-рассылки', 'usam');		
		elseif ( $this->table == 'email_newsletter_templates' )
			return __('Письма для индивидуальной отправки', 'usam');
		else
			return __('Email-рассылки', 'usam');
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'email_newsletter_templates' )
			return [['form' => 'edit', 'form_name' => 'email_newsletter', 'title' => __('Добавить письмо', 'usam') ]];
		else
			return [['form' => 'edit', 'form_name' => 'email_newsletter', 'title' => __('Добавить рассылку', 'usam') ]];	
	}
	
	public function get_tab_sections() 
	{ 
		$tables = ['email_newsletters' => ['title' => __('Рассылки','usam'), 'type' => 'table'], 'trigger_email_newsletters' => ['title' => __('Триггерные','usam'), 'type' => 'table'], 'email_newsletter_templates' => ['title' => __('Шаблоны','usam'), 'type' => 'table']];
		return $tables;
	}
}