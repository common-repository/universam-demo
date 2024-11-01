<?php
/**
 * Name: Квитанция Сбербанка
 */
class USAM_Merchant_receipt_sberbank extends USAM_Merchant 
{
	protected $api_version = '2.0';
	protected $type_operation = 'c';
	
	protected function display_the_page_transaction() 
	{		
		if ( $this->purchase_log )
		{
			$order_id = $this->purchase_log->get('id');
			return "<div class='printed_forms'>
				<div class='printed_forms__action'><a href='".$this->get_document_pdf_link('receipt_sberbank', $order_id)."' target='_blank' class='button'>".__('Скачать квитанцию', 'usam')."</a></div>
			</div>";
		}
	}
}