<?php
class USAM_Tab_accumulative extends USAM_Tab
{
	public function get_title_tab()
	{			
		return __('Накопительные скидки', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'accumulative', 'title' => __('Добавить', 'usam')]];			
	}
}