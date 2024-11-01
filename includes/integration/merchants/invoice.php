<?php
/**
 * Name: Оплата по счету 
 */
class USAM_Merchant_invoice extends USAM_Merchant 
{	
	protected function display_the_page_transaction() 
	{	
		if ( $this->purchase_log )
		{
			$order_id = $this->purchase_log->get('id'); 	
			return "<div class='printed_forms'>
				<div class='printed_forms__action'><a href='".$this->get_document_pdf_link('payment_invoice', $order_id)."' target='_blank' class='button'>".__('Скачать счет', 'usam')."</a></div>
			</div>";
		}
	}
	
	/**
	* передает полученные данные в платежный шлюз
	*/
	public function submit() 
	{ 		
		$this->set_purchase_processed_by_purchid('waiting_payment');
		$this->go_to_transaction_results();	
	}
}