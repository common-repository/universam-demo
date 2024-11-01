<?php
class USAM_Tab_sms_newsletters extends USAM_Page_Tab
{
	public function get_title_tab()
	{			
		if ( $this->view == 'report' )
			return __('Отчет по рассылкам', 'usam');	
		elseif ( $this->table == 'sms_newsletter_templates' )
			return __('SMS для индивидуальной отправки', 'usam');
		else
			return __('СМС-рассылки', 'usam');
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'sms_newsletter_templates' )
			return [['form' => 'edit', 'form_name' => 'sms_newsletter', 'title' => __('Добавить шаблон', 'usam') ]];
		else
			return [['form' => 'edit', 'form_name' => 'sms_newsletter', 'title' => __('Добавить рассылку', 'usam') ]];	
	}
	
	public function get_tab_sections() 
	{ 
		$tables = ['sms_newsletters' => ['title' => __('Рассылки','usam'), 'type' => 'table'], 'sms_newsletter_templates' => ['title' => __('Шаблоны','usam'), 'type' => 'table']];
		return $tables;
	}
}