<?php 
class USAM_Profile_API extends USAM_API
{		
	public static function delete_user( WP_REST_Request $request )
	{
		$user_id = get_current_user_id();
		require_once ABSPATH . 'wp-admin/includes/user.php';
		return wp_delete_user( $user_id );
	}
	
	public static function get_referral_url( WP_REST_Request $request )
	{
		$user_id = get_current_user_id();
		$results = ['referral' => []];
		require_once( USAM_FILE_PATH . '/includes/basket/coupons_query.class.php' );	
		$results['coupon'] = usam_get_coupons(['user_id' => $user_id, 'coupon_type' => 'referral', 'number' => 1]);
		if ( !empty($results['coupon']) )
		{
			$results['coupon']['url'] = usam_get_coupon_url( $results['coupon']['coupon_code'] );
		}
		$rules = usam_get_bonuses_rules(['rule_type' => 'open_url', 'active' => 1]);	
		if ( !empty($rules) )
		{
			$rule = array_shift($rules);
			require_once( USAM_FILE_PATH . '/includes/customer/user_referrals_query.class.php' );
			$results['referral'] = usam_get_user_referrals(['user_id' => $user_id, 'number' => 1]);			
			if ( empty($results['referral']) )
			{
				require_once( USAM_FILE_PATH . '/includes/customer/user_referral.class.php' );	
				$id = usam_insert_user_referral(['user_id' => $user_id]);
				$results['referral'] = usam_get_user_referral( $id );
			}
			$results['referral']['url'] = get_bloginfo('url').'/r/'.$results['referral']['id'];
			if ( $rule['what'] == 'bonus_card' )
			{
				require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php');
				$amount = usam_get_bonuses(['user_id' => $user_id, 'transaction_code' => 'open_url', 'fields' => 'SUM_Bonuses', 'type_transaction' => 0, 'number' => 1]);
				$last = usam_get_bonuses(['user_id' => $user_id, 'transaction_code' => $rule['rule_type'], 'orderby' => 'date', 'order' => 'DESC', 'number' => 1]);
			}
			else
			{
				require_once( USAM_FILE_PATH . '/includes/customer/account_transactions_query.class.php'  );
				$amount = usam_get_account_transactions(['user_id' => $user_id, 'transaction_code' => 'open_url', 'fields' => 'SUM_money', 'type_transaction' => 0, 'number' => 1]);	
				$last = usam_get_account_transactions(['user_id' => $user_id, 'transaction_code' => $rule['rule_type'], 'orderby' => 'date', 'order' => 'DESC', 'number' => 1]);
			}				
			require_once( USAM_FILE_PATH . '/includes/customer/user_referrals_links_query.class.php'  );
			$results['referral']['contacts'] = usam_get_user_referrals_links(['user_id' => $user_id, 'fields' => 'count', 'number' => 1]);
			$results['referral']['amount'] = $amount;
			if ( $last )
			{
				$results['referral']['last_payout'] = usam_local_date($last['date_insert']);
				$results['referral']['last_amount'] = $last['sum'];
			}
			else
			{
				$results['referral']['last_payout'] = '';
				$results['referral']['last_amount'] = '';
			}
		}
		return $results;
	}
	
	public static function verification_phone( WP_REST_Request $request )
	{
		$phone = $request->get_param( 'number' );
		$code = mt_rand(1000, 9999);
		$code = apply_filters( 'usam_verification_phone', $code, $phone );
		$message = sprintf( __('%s - код для подтверждения телефона', 'usam'), $code);
		$contact_id = usam_get_contact_id();
		$second = 300;
		usam_update_contact_metadata( $contact_id, 'code_phone_verification', $code );
		usam_update_contact_metadata( $contact_id, 'time_phone_verification', time() + $second );
		return usam_send_sms( $phone, $message );
	}
	
	public static function check_phone_verification_code( WP_REST_Request $request )
	{
		$phone = $request->get_param( 'number' );
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		$contact_id = usam_get_contact_id();
		$code = usam_get_contact_metadata( $contact_id, 'code_phone_verification' );
		$time = usam_get_contact_metadata( $contact_id, 'time_phone_verification' );	
		$result = false;
		if ( $time > time() )
		{
			$result = $parameters['code'] == $code;
			if ( $result )
			{
				if ( apply_filters( 'usam_phone_confirmed', true, $phone ) )
					usam_update_contact_metadata( $contact_id, 'phone_verification', $phone );
				usam_delete_contact_metadata( $contact_id, 'code_phone_verification' );
				usam_delete_contact_metadata( $contact_id, 'time_phone_verification' );
			}
		}
		return $result;
	}	
}
?>