<?php
class USAM_Submit_Checkout
{	
	function __construct() 
	{ 
		if ( !empty($_REQUEST['submit_checkout']) )
			add_action( 'template_redirect', array( $this, 'purchase'), 13 );
		if ( !empty( $_REQUEST['pay_the_order'] ) )
			add_action( 'init', array( $this, 'pay_the_order' ) );
	}
	
	private function redirect_transact( $args = array(), $type_display = 'fail' ) 
	{		
		$url = add_query_arg( $args, usam_get_url_system_page( 'transaction-results' ).'/'.$type_display );
		wp_redirect( $url );
		exit;
	} 
	
	
	public function purchase( )
	{ 		
		do_action( 'usam_before_submit_checkout' );			
		
		$cart = USAM_CART::instance( );	
		$gateway_id = $cart->get_property( 'selected_payment' );				
		$merchant_instance = usam_get_merchant_class( $gateway_id );		
		if ( is_object($merchant_instance) )						
		{ 			
			$order_id = $cart->get_property( 'order_id' );	
			$order = usam_get_order( $order_id );			
			if ( $order )
			{
				$total = $order['totalprice'];				
				$total -= $cart->get_cost_paid();			
								
				$payment['gateway_id'] = $cart->get_property( 'selected_payment' );
				$payment['sum'] = $total;	
				$payment['document_id'] = $order_id;		
		
				do_action_ref_array( 'usam_pre_submit_gateway', [ &$merchant_instance ]);	
		
				$merchant_instance->send_gateway_parameters( $payment );
			
				do_action( 'usam_submit_checkout', $order_id );			
							
				$merchant_instance->submit();			
				exit();
			}
		}
	}	
	
	// Оплатить заказ, из кабинета
	public function pay_the_order() 
	{		
		$user_id = get_current_user_id();
		if ( empty($_POST['new_transaction']) || !wp_verify_nonce($_POST['new_transaction'],'purchase_'.$user_id) || empty($_REQUEST['gateway']) || empty($_REQUEST['order_id']) )
		{		
			wp_redirect( usam_get_url_system_page('your-account') );
			exit;
		}		
		$gateway_id = absint($_REQUEST['gateway']);	
		$order_id = absint($_REQUEST['order_id']);	
	
		$order = usam_get_order( $order_id );		
		$pay_up = usam_get_order_metadata($order['id'], 'date_pay_up' );
		if ( empty($pay_up) )	
		{						
			$this->redirect_transact( array( 'result' => 3 ) );					
		}	
		elseif ( date( "Y-m-d H:i:s", strtotime($pay_up) ) < date( "Y-m-d H:i:s" )  )
		{
			$this->redirect_transact( array( 'result' => 3 )) ;					
		}		
		elseif ( usam_check_object_is_completed( $order['status'], 'order' ) )	
		{
			$this->redirect_transact( array( 'result' => 4 ));					
		}					
		$payment['gateway_id'] = $gateway_id;
		$payment['document_id'] = $order_id;				
		$merchant_instance = usam_get_merchant_class( $gateway_id );
		if ( !$merchant_instance )
			$this->redirect_transact(['result' => 5]);				
	
		do_action_ref_array( 'usam_pre_submit_gateway', array( &$merchant_instance ) );				
		$merchant_instance->send_gateway_parameters( $payment );
		$merchant_instance->submit();
	}	
}
new USAM_Submit_Checkout();
?>