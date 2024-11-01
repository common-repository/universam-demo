<?php
/**
	Name: Оплата при получении
*/
class USAM_Merchant_payment_on_receipt extends USAM_Merchant
{			
	public function submit() 
	{			
		$result = $this->set_purchase_processed_by_purchid( 'received' );			
		$this->go_to_transaction_results( );			
	}
}
?>