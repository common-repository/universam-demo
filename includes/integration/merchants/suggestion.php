<?php
/**
	Name: Коммерческое предложение
*/
class USAM_Merchant_suggestion extends USAM_Merchant
{		
	protected function display_the_page_transaction() 
	{	
		$order_id = $this->purchase_log->get('id'); 	
		return "
		<div class='printed_forms'>
			<div class='printed_forms__action'><a href='".$this->get_document_pdf_link('order_suggestion', $order_id)."' target='_blank' class='button'>".__('Скачать коммерческое предложение', 'usam')."</a></div>
		</div>";
	}
	
	/**
	* передает полученные данные в платежный шлюз
	*/
	public function submit() 
	{ 			
		$this->set_purchase_processed_by_purchid('received');
		$this->go_to_transaction_results();	
	}
}
?>