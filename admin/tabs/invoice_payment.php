<?php
class USAM_Tab_invoice_payment extends USAM_Page_Tab
{		
	public function get_title_tab()
	{		
		return usam_get_document_name('invoice_payment', 'plural_name');
	}
		
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'invoice_payment',  'title' => __('Добавить', 'usam'), 'capability' => 'add_invoice_payment']];
	}	
}
