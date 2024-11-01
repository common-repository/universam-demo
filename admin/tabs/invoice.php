<?php
class USAM_Tab_invoice extends USAM_Tab
{		
	public function __construct()
	{	
		$this->views[] = 'table';
		if ( current_user_can( 'report_document' ) )
			$this->views[] = 'report';
	}	
	
	public function get_title_tab()
	{		
		return usam_get_document_name('invoice', 'plural_name');
	}
		
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'invoice',  'title' => __('Выставить счет', 'usam'), 'capability' => 'add_invoice'], ['form' => 'edit', 'form_name' => 'invoice_offer', 'title' => __('Выставить счет-оферта', 'usam'), 'capability' => 'add_invoice_offer']];	
	}	
}
