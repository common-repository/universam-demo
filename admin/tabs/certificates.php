<?php
class USAM_Tab_certificates extends USAM_Tab
{	
	public function get_title_tab()
	{
		return __('Подарочные сертификаты', 'usam');
	}
		
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'certificate', 'title' => __('Добавить', 'usam')], ['form' => 'edit', 'form_name' => 'generate_certificate', 'title' => __('Генерировать', 'usam') ]];
	}
}