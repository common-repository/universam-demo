<?php
new USAM_Creating_Coupons();
class USAM_Creating_Coupons
{	
	public function __construct( ) 
	{		
		add_action( 'user_register', array( $this, 'event_user_register'), 9, 1 );
		add_action( 'usam_customer_review_published', array( $this, 'customer_review_published'), 10, 3 );
		add_action( 'usam_order_close', array( $this, 'order_close' ), 10, 2 );	
		add_action( 'usam_user_profile_activation', array( $this, 'user_profile_activation' ), 10, 1 );	
	}
	
	function order_close( $order_id, $purchase_log ) 
	{			
		$user_id = $purchase_log->get('user_ID');	
		$this->creating( 'order_close', $user_id  );
	}
	
	public function user_profile_activation( $user_id )
	{ 	
		$this->creating( 'user_profile_activation', $user_id );
	}
		
	public function event_user_register( $user_id )
	{ 		
		$this->creating( 'register', $user_id );
		$this->creating( 'referral', $user_id );
	}
	
	public function customer_review_published( $contact_id, $id, $t )
	{
		$contact = usam_get_contact( $contact_id, 'user_id' );
		if( !empty($contact['user_id']) )
			$this->creating( 'review', $contact['user_id'] );
	}
	
	private function creating( $rule_type, $user_id )
	{	
		if ( $user_id )
		{
			$location = usam_get_customer_location( );
			if ( !empty($location) )
				$current_location_ids = usam_get_address_locations( $location, 'id' );
			else
				$current_location_ids = array();
			
			$rules = usam_get_coupons_rules(['rule_type' => $rule_type, 'active' => 1, 'user_id' => $user_id, 'location' => $current_location_ids]);			
			$user_info = get_userdata( $user_id );				
			$phone = get_user_meta( $user_id, 'user_management_phone', true );	
			foreach( $rules as $rule )		
			{
				if ( usam_validate_rule( $rule ) )
				{								
					$coupon = usam_get_coupon( $rule['coupon_id'] );
					if (empty($coupon))
						continue;				
				
					$coupon['value'] = (double)$rule['discount'];
					$coupon['is_percentage'] = $rule['discount_type'];					
					$coupon['coupon_code']   = usam_generate_coupon_code( $rule['format'], $rule['type_format'] );
					$coupon['description']   = sprintf( __('Из правила %s', 'usam'), '"'.$rule['title'].'"' );
					$coupon['active']        = 1;		
					$coupon['coupon_type']   = $rule_type == 'referral' ? 'referral' : 'coupon';										
					$coupon['user_id'] = $user_id;	
					$coupon_id = usam_insert_coupon( $coupon );		
					$args = array(							
						'shop_name'       => get_option( 'blogname' ),
						'coupon_sum'      => $coupon['value'],						
						'coupon_code'     => $coupon['coupon_code'],
						'procent'         => $rule['discount']	
					);					
					$args = array_map( 'esc_html', $args );	
					$tokens = array_keys( $args );
					$values = array_values( $args );
					foreach ( $tokens as &$token ) 
					{	
						$token = "%{$token}%";
					}		
					if ( !empty($user_info->data->user_email) && !empty($rule['message']) )
					{
						$html = str_replace( $tokens, $values, $rule['message'] );
						$html = wpautop( $html );		
						$message = str_replace( "<br />", '<br /><br />',$html);						
						usam_send_mail_by_id(['message' => $message, 'title' => $rule['subject'], 'email' => $user_info->data->user_email]);
					}			
					$sms_message = str_replace( $tokens, $values, $rule['sms_message'] );	
					usam_add_send_sms( array('phone' => $phone, 'message' => $sms_message) );					
					return true;
				}	
			}	
		}		
	}	
}
?>