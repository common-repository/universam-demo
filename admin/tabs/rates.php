<?php
class USAM_Tab_Rates extends USAM_Tab
{		
	public function get_title_tab()
	{			
		return __('Валютные курсы', 'usam');	
	}

	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'rate', 'title' => __('Добавить', 'usam') ]];			
	}	

	public function table_view() 
	{			
		?>			
		<div class = "base_currency">
			<strong><?php _e('Базовая валюта', 'usam') ?>: </strong><span><?php echo usam_get_currency_name( ); ?></span>
		</div>
		<?php
		$this->list_table->display_table();		
	}
}