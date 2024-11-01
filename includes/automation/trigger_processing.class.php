<?php
/*

if( !apply_filters( 'usam_prevent_notification_change_status', true ) ) 	
			return false;

*/
class USAM_Trigger_Processing
{
	private static $trigger = [];
	function __construct() 
	{		
		$items = usam_get_triggers();
		$triggers = usam_get_list_triggers();
		foreach( $items as $item)
		{			
			self::$trigger = $item;
			if ( method_exists(__CLASS__, $item->event) )
				add_action( $item->event, [__CLASS__, $item->event], 10, $triggers[$item->event]['args'] );
			if ( !usam_is_license_type('BUSINESS') && !usam_is_license_type('ENTERPRISE') )
				break;
		}
	}
	
	public static function usam_new_letter_received( $email, $mailbox ) 
	{			
		if ( self::$trigger->conditions )
		{
			$contact_ids = usam_get_contact_ids_by_field('email', $email['from_email']);			
			$company_ids = usam_get_company_ids_by_field('email', $email['from_email']);
			
			require_once( USAM_FILE_PATH . '/includes/expression-parser.class.php' );
			$t = new USAM_Expression_Parser();	
			$result = $t->parser(self::$trigger->conditions, ['email' => $email, 'mailbox' => $mailbox, 'contact_ids' => $contact_ids, 'company_ids' => $company_ids]);
			if ( !$result )
				return false;	
		}
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
		$actions = usam_get_array_metadata( self::$trigger->id, 'trigger', 'actions' );	
		foreach( $actions as $action)
		{			
			switch( $action['id'] )
			{
				case 'creating_lead':
					require_once( USAM_FILE_PATH . '/includes/automation/'.$action['id'].'.class.php' );
					$class = 'usam_'.$action['id'];
					$c = new $class();	
					$c->from_letter( $email );
				break;			
			}
		}
	}
	
	public static function usam_new_request_customer( $event_id, $webform, $webform_data, $properties ) 
	{			
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		$event = usam_get_event( $event_id );
		if ( self::$trigger->conditions )
		{			
			require_once( USAM_FILE_PATH . '/includes/expression-parser.class.php' );		
			$t = new USAM_Expression_Parser();	
			$result = $t->parser(self::$trigger->conditions, ['event' => $event, 'webform' => $webform, 'webform_data' => $webform_data]);
			if ( !$result )
				return false;	
		}
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
		$actions = usam_get_array_metadata( self::$trigger->id, 'trigger', 'actions' );	
		foreach( $actions as $action)
		{			
			switch( $action['id'] )
			{
				case 'creating_lead':
					require_once( USAM_FILE_PATH . '/includes/automation/'.$action['id'].'.class.php' );
					$class = 'usam_'.$action['id'];
					$c = new $class();	
					$c->from_webform( $event, $webform, $webform_data, $properties );
				break;
				case 'send_letter':
					if ( !empty($action['settings']['newsletter_id']) )
					{
						$email = '';
						foreach( $properties as $property)
							if( $property->field_type == 'email' && !empty($webform_data[$property->code]) )
							{
								$email = $webform_data[$property->code];
								break;
							}	
						if ( $email )
						{
							$args = [];
							$_newsletter = new USAM_Send_Newsletter( $action['settings']['newsletter_id'], $args );		
							$_newsletter->send_email_trigger( $email, [], [['object_id' => $event['id'], 'object_type' => $event['type']], ['object_id' => 'contact', 'object_type' => $event['user_id']]] );
						}
					}
				break;				
			}
		}
	}
	
	public static function usam_competitor_price_changed( $data, $changed_data ) 
	{
		if ( !empty($data['product_id']) )
		{
			if ( self::$trigger->conditions )
			{				
				require_once( USAM_FILE_PATH . '/includes/expression-parser.class.php' );		
				$t = new USAM_Expression_Parser();	
				$result = $t->parser(self::$trigger->conditions, ['product' => $data]);
				if ( !$result )
					return false;	
			}
			require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
			$actions = usam_get_array_metadata( self::$trigger->id, 'trigger', 'actions' );	
			foreach( $actions as $action)
			{			
				switch( $action['id'] )
				{
					case 'change_price':						
						$price = $data['current_price'];
						if ( !empty($action['settings']['percent']) )
							$price = $price + $action['settings']['percent']*$price/100;						
						usam_edit_product_prices($data['product_id'], [$action['settings']['type_price'] => $price]);
					break;			
				}
			}			
		}		
	}
	
	public static function usam_update_order_status( $order_id, $current_status, $old_status, $t ) 
	{
		$data = $t->get_data();
		if ( self::$trigger->conditions )
		{					
			require_once( USAM_FILE_PATH . '/includes/expression-parser.class.php' );		
			$t = new USAM_Expression_Parser();	
			$result = $t->parser(self::$trigger->conditions, ['order' => $data]);
			if ( !$result )
				return false;	
		}
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
		$actions = usam_get_array_metadata( self::$trigger->id, 'trigger', 'actions' );			
		$links = [['object_id' => $order_id, 'object_type' => 'order']];
		if( isset($data['contact_id']) )
			$links[] = ['object_id' => $data['contact_id'], 'object_type' => 'contact'];
		if( isset($data['company_id']) )
			$links[] = ['object_id' => $data['company_id'], 'object_type' => 'company'];
		foreach( $actions as $action)
		{			
			switch( $action['id'] )
			{
				case 'send_letter':
					if ( !empty($action['settings']['newsletter_id']) )
					{
						$email = usam_get_order_customerdata( $order_id, 'email' );
						if ( $email )
						{
							$order_shortcode = new USAM_Order_Shortcode( $order_id );
							$args = $order_shortcode->get_plaintext_args();	
							$_newsletter = new USAM_Send_Newsletter( $action['settings']['newsletter_id'], $args );		
							$_newsletter->send_email_trigger( $email, [], $links );
						}
					}
				break;
				case 'send_sms':
					if ( !empty($action['settings']['newsletter_id']) )
					{
						$phone = usam_get_order_customerdata( $order_id, 'mobile_phone' );
						if ( $phone )
						{						
							$order_shortcode = new USAM_Order_Shortcode( $order_id );
							$args = $order_shortcode->get_plaintext_args();	
							$_newsletter = new USAM_Send_Newsletter( $action['settings']['newsletter_id'], $args );
							$_newsletter->send_sms_trigger( $phone, $links );
						}
					}
				break;	
			}		
		}		
	}
	
	public static function usam_bonus_update( $t ) 
	{
		$data = $t->get_data();
		$bonus_card = usam_get_bonus_card( $data['code'] );
		if( !$bonus_card )
			return false;
		$contact = usam_get_contact( $bonus_card['user_id'], 'user_id' );
		if( !$contact )
			return false;
		if ( self::$trigger->conditions )
		{					
			require_once( USAM_FILE_PATH . '/includes/expression-parser.class.php' );		
			$t = new USAM_Expression_Parser();	
			$result = $t->parser(self::$trigger->conditions, ['bonus' => $data, 'bonus_card' => $bonus_card, 'contact' => $contact]);
			if ( !$result )
				return false;	
		}
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
		$actions = usam_get_array_metadata( self::$trigger->id, 'trigger', 'actions' );		
		$links = [['object_id' => $contact['id'], 'object_type' => 'contact']];
		$args = [
			'bonus_card' => $bonus_card['code'],
			'bonus_card_sum' => $bonus_card['sum'],		
			'bonus_sum' => $data['sum'],								
		];	
		foreach( $actions as $action)
		{			
			switch( $action['id'] )
			{
				case 'send_letter':
					if ( !empty($action['settings']['newsletter_id']) )
					{
						$emails = usam_get_contact_emails( $contact['id'] );
						if ( $emails )
						{								
							$_newsletter = new USAM_Send_Newsletter( $action['settings']['newsletter_id'], $args );		
							$_newsletter->send_email_trigger( $emails[0], [], $links );
						}
					}
				break;
				case 'send_sms':
					if ( !empty($action['settings']['newsletter_id']) )
					{
						$phones = usam_get_contact_phones( $contact['id'] );
						if ( $phones )
						{									
							$_newsletter = new USAM_Send_Newsletter( $action['settings']['newsletter_id'], $args );
							$_newsletter->send_sms_trigger( $phones[0], $links );
						}
					}
				break;	
			}		
		}		
	}
	
	
	public static function usam_subscription_expired( $data, $day ) 
	{
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php'  );
		$data['products'] = usam_get_products_subscription( $data['id'] );
		if ( !empty($data['products']) )
		{
			if ( self::$trigger->conditions )
			{				
				require_once( USAM_FILE_PATH . '/includes/expression-parser.class.php' );		
				$t = new USAM_Expression_Parser();
				$data['days_to_go'] = $day;
				$result = $t->parser(self::$trigger->conditions, ['subscription' => $data]);
				if ( !$result )
					return false;	
			}	
			
	$Log = new USAM_Log_File( 'usam_subscription_expired' ); 
	$Log->fwrite_array( $day );
		$Log->fwrite_array( $data );
	
			require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
			$actions = usam_get_array_metadata( self::$trigger->id, 'trigger', 'actions' );	
			$documents = [];
			foreach( $actions as $action)
			{			
				switch( $action['id'] )
				{
					case 'create_invoice':
						$documents['invoice'][] = usam_insert_document(['name' => sprintf( __('Подписка №%d', 'usam'), $data['id'] ), 'type_price' => $data['type_price'], 'customer_type' => $data['customer_type'], 'customer_id' => $data['customer_id'], 'manager_id' => $data['manager_id'], 'type' => 'invoice'], $data['products']);
					break;
					case 'create_act':
						$links = [];
						foreach( $documents as $type => $document_ids )
						{
							foreach( $document_ids as $document_id )
								$links[] = ['document_id' => $document_id, 'document_type' => $type];
						}
						$documents['act'][] = usam_insert_document(['name' => sprintf( __('Подписка №%d', 'usam'), $data['id'] ), 'type_price' => $data['type_price'], 'customer_type' => $data['customer_type'], 'customer_id' => $data['customer_id'], 'manager_id' => $data['manager_id'], 'type' => 'act'], $data['products'], [], $links);
						
					break;
					case 'renew_subscription':
						usam_insert_subscription_renewal(['status' => 'not_paid', 'sum' => $data['totalprice'], 'start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'subscription_id' => $data['id']]);
					break;
					case 'send_letter':					
						if ( !empty($action['settings']['newsletter_id']) )
						{							
							if ( $data['customer_type'] == 'contact' )
								$email = usam_get_contact_metadata( $data['customer_id'], 'email' );
							else
								$email = usam_get_company_metadata( $data['customer_id'], 'email' );
							if ( $email )
							{
								$attachments = [];
								if ( $documents )
								{
									foreach( $documents as $type => $document_ids )
									{
										foreach( $document_ids as $document_id )
											$attachments[usam_get_document_name($type)." №".$document_id.'.pdf'] = usam_get_pdf_document( $document_id );
									}
								}	
								$args = [];
								$_newsletter = new USAM_Send_Newsletter( $action['settings']['newsletter_id'], $args );		
								if ( $_newsletter->send_email_trigger( $email, $attachments, [['object_id' => $data['customer_id'], 'object_type' => $data['customer_type']]] ) )
								{
									if ( $documents )
									{
										foreach( $documents as $type => $document_ids )
										{
											foreach( $document_ids as $document_id )
												usam_update_document($document_id, ['status' => 'sent']);
										}
									}
								}
							}
						}
					break;
				}				
			}			
		}		
	}	
}
new USAM_Trigger_Processing();
?>