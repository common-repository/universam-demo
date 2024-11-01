<?php
class USAM_Tab_Coupons extends USAM_Tab
{		
	public function get_title_tab()
	{
		if ( $this->table == 'rules_coupons' )
			return __('Правила создания автоматических купонов', 'usam');
		elseif ( $this->table == 'referral' )
			return __('Реферальные ссылки', 'usam');
		else
			return __('Купоны', 'usam');
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'rules_coupons' )
			return [['form' => 'edit', 'form_name' => 'rule_coupon', 'title' => __('Добавить правило', 'usam')]];
		else
			return [['form' => 'edit', 'form_name' => 'coupon', 'title' => __('Добавить', 'usam')], ['form' => 'edit', 'form_name' => 'generate_coupon', 'title' => __('Генерировать', 'usam')]];		
	}
	
	public function get_tab_sections() 
	{ 
		return ['coupons' => ['title' => __('Купон','usam'), 'type' => 'table'], 'referral' => ['title' => __('Реферальная ссылка','usam'), 'type' => 'table'], 'rules_coupons' => ['title' => __('Правила купонов','usam'), 'type' => 'table']];
	}
}