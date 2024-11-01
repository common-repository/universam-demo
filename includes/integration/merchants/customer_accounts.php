<?php
/**
	Name: Оплата с внутреннего счета клиента
*/
class USAM_Merchant_customer_accounts extends USAM_Merchant
{			
	public function submit() 
	{			
		$user_id = get_current_user_id();
		$totalprice = $this->purchase_log->get('totalprice');	
		$order_id = $this->purchase_log->get('id');	
		if ( $user_id && $totalprice )
		{
			require_once( USAM_FILE_PATH . '/includes/customer/customer_accounts_query.class.php'  );
			require_once( USAM_FILE_PATH . '/includes/customer/account_transaction.class.php' );
			$account = usam_get_customer_accounts(['number' => 1, 'status' => 'active', 'user_id' => $user_id, 'conditions' => ['key' => 'sum', 'compare' => '>', 'value' => $totalprice]]);
			if ( !empty($account) )
			{
				$result = usam_insert_account_transaction(['account_id' => $account->id, 'order_id' => $order_id, 'sum' => $totalprice, 'type_transaction' => 1, 'description' => __('Оплата заказа','usam')]);
				if ( $result )
				{
					usam_update_payment_document($this->payment_number, ['status' => 3, 'payment_type' => 'account'], 'number');
					$this->go_to_transaction_results( );	
				}
			}
		}
		$this->go_to_transaction_results( 0 );					
	}
}
?>