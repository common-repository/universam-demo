<?php
new USAM_Customer_Incentives();
class USAM_Customer_Incentives
{	
	public function __construct( ) 
	{		
		add_action( 'user_register', [$this, 'event_user_register'], 5, 1 );
		add_action( 'usam_update_customer_review_status', [$this, 'update_customer_review_status'], 10, 4 );
		add_action( 'usam_review_insert', [$this, 'review_insert']);			
		add_action( 'usam_order_close', [$this, 'order_close'], 10, 2 );
		add_action( 'usam_user_profile_activation', [$this, 'user_profile_activation'], 10, 1 );	
		add_action('usam_open_referral_url',[&$this, 'open_loyalty_link'], 1);
	}
	
	// Заказ завершен.
	function order_close( $order_id, $purchase_log ) 
	{			
		$user_id = $purchase_log->get('user_ID');		
		if ( $user_id )		
		{			
			$motivation_employees = get_option('usam_motivation_employees');		
			if ( empty($motivation_employees['active']) || !usam_check_is_employee() )
			{ //Если выключена программа лояльности для сотрудников или покупатель не сотрудник					
				$sum = $purchase_log->get('totalprice')-$purchase_log->get('shipping');					
				if ( $sum > 0 )
				{		
					$products = usam_get_products_order( $order_id );
					$bonus = 0;
					foreach( $products as $product )
					{				
						$bonus += $product->bonus*$product->quantity;
					}		
					if ( $bonus )
					{
						$rules = $this->get_rules('order_close', $user_id);		
						foreach( $rules as $rule )
							usam_insert_bonus(['sum' => $bonus, 'description' => $rule['description'], 'object_id' => $order_id, 'object_type' => 'order', 'type_transaction' => 2], $user_id );
					}
					$discount = usam_get_accumulative_discount_customer( 'bonus' );				
					if ( $discount != 0 )				
					{
						$bonus = round( $sum * $discount / 100, 2 );	
						usam_insert_bonus(['sum' => $bonus, 'description' => __('По программам накопительных скидок','usam'), 'object_id' => $order_id, 'object_type' => 'order'], $user_id );	
					}
					$bonus_card = usam_get_bonus_card( $user_id, 'user_id' );
					if ( !empty($bonus_card) && $bonus_card['status'] == 'active' && $bonus_card['percent'] > 0 )		
					{
						$bonus = round($sum * $bonus_card['percent'] / 100, 0);				
						usam_insert_bonus(['sum' => $bonus, 'description' => __('Процент по карте','usam'), 'object_id' => $order_id, 'object_type' => 'order'], $user_id );	
					}			
				}	
			}
		}
	}
	
	function open_loyalty_link( $id )
	{	
		$contact_id = usam_get_contact_id();	
		require_once( USAM_FILE_PATH . '/includes/customer/user_referral.class.php' );	
		$referral = usam_get_user_referral( $id );			
		if ( !empty($referral) && $referral['status'] == 'active' )
		{			
			require_once( USAM_FILE_PATH . '/includes/customer/user_referral_links.class.php' );	
			$id = usam_insert_user_referral_link(['user_id' => $referral['user_id']]); 
			if ( $id )
				$this->creating( 'open_url', $referral['user_id'] );				
		}				
	}
	
	public function user_profile_activation( $user_id )
	{ 	
		$this->creating( 'user_profile_activation', $user_id );
	}
		
	public function event_user_register( $user_id )
	{ 		
		$this->creating( 'register', $user_id  );		
	}
	
	public function update_customer_review_status( $id, $current_status, $status, $t )
	{				
		$this->review_insert( $t );
	}
	
	public function review_insert( $t )
	{
		$data = $t->get_data();
		if( $data['status'] == 2 )
		{
			if( !empty($data['contact_id']) )
			{
				$contact = usam_get_contact( $data['contact_id'] );
				if( !empty($contact['user_id']) )			
					$this->creating( 'review', $contact['user_id'], $data['id'], 'review' );
			}
		}
	}	
	
	private function get_rules( $rule_type, $user_id )
	{
		$location = usam_get_customer_location( );
		if ( !empty($location) )
			$current_location_ids = usam_get_address_locations( $location, 'id' );
		else
			$current_location_ids = [];
		
		$contact = usam_get_contact( $user_id, 'user_id' );
		if( !empty($contact['user_id']) )
		{
			$rules = usam_get_bonuses_rules(['rule_type' => $rule_type, 'active' => 1, 'user_id' => $user_id, 'location' => $current_location_ids]);
			foreach( $rules as $k => $rule )
			{
				if ( !usam_validate_rule( $rule ) )
				{
					unset($rules[$k]);
					continue;
				}
				if ( !empty($rule['total_purchased']) )
				{
					if ( $contact['total_purchased'] < $rule['total_purchased'] )
						unset($rules[$k]);
				}
			}
		}
		return $rules;
	}
	
	private function creating( $rule_type, $user_id, $object_id = 0, $object_type = '' )
	{				
		global $wpdb;
		if ( $user_id )
		{	
			$rules = $this->get_rules( $rule_type, $user_id );	
			foreach( $rules as $rule )		
			{
				if ( !isset($rule['what']) || $rule['what'] == 'bonus_card' )
				{	
					$id = 0;
					if ( $object_id && $object_type )						
						$id = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".USAM_TABLE_BONUS_TRANSACTIONS." WHERE object_id='%d' AND object_type='%s' AND user_id='%s' LIMIT 1", $object_id, $object_type, $user_id));	
					if( !$id )
						usam_insert_bonus(['sum' => $rule['value'], 'description' => $rule['description'], 'transaction_code' => $rule_type, 'object_id' => $object_id, 'object_type' => $object_type, 'user_id' => $user_id], $user_id);	
				}
				else
				{
					require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php' );
					require_once( USAM_FILE_PATH . '/includes/customer/account_transaction.class.php' );
					$card = usam_get_customer_account( $user_id, 'user_id' );	
					if ( empty($card) )
						$account_id = usam_insert_customer_account(['status' => 'active', 'user_id' => $user_id]);
					else
						$account_id = $card['id'];	 
					usam_insert_account_transaction(['sum' => $rule['value'], 'description' => $rule['description'], 'account_id' => $account_id, 'transaction_code' => $rule_type, 'user_id' => $user_id]);								
				}
			}	
		}
	}	
}
?>