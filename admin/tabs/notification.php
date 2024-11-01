<?php
class USAM_Tab_notification extends USAM_Tab
{	
	public function get_title_tab()
	{			
		return __('Уведомления для персонала', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'notification', 'title' => __('Добавить уведомление', 'usam') ]];			
	}
}
?>