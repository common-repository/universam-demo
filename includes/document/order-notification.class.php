<?php
// Форматы сообщений электронной почты

class USAM_Order_Notification extends USAM_Sending_Notification
{
	protected $purchase_log;
	protected $is_order = false;
	protected $shipping_id;
	protected $order_shortcode;	
	
	public function __construct( $order_id, $shipping_id = null ) 
	{	
		if ( empty($order_id) )		
			return false;	
		
		if ( is_numeric( $order_id ) )		
			$purchase_log = new USAM_Order( $order_id );
		else
			$purchase_log = $order_id;
		
		$order = $purchase_log->get_data();		
		if ( empty($order) )		
			return false;	
		
		$this->is_order        = true;				
		$this->purchase_log    = $purchase_log;					
		$this->shipping_id     = $shipping_id;			
		
		$this->object_type     = 'order';
		$this->object_id       = $order['id'];			
		
		$this->phone = usam_get_order_customerdata( $order['id'], 'mobile_phone' );	
		$this->email = usam_get_order_customerdata( $order['id'], 'email' );
		
		$this->order_shortcode = new USAM_Order_Shortcode( $this->purchase_log );
			
		$this->plaintext_message = $this->process_plaintext_args( );			
		$this->html_message      = $this->process_html_args( );		
		$this->sms_message       = $this->process_sms_message_args( );				
	}	
	
	protected function get_plaintext_args() 
	{				
		$args = $this->order_shortcode->get_plaintext_args();	
		$args = apply_filters( 'usam_order_notification_plaintext_args', $args, $this );
		return $args;
	}
	
	protected function get_html_args() 
	{				
		$args = $this->order_shortcode->get_html_args();
		$args = apply_filters( 'usam_order_notification_html_args', $args, $this );
		return $args;
	}	
	
	protected function add_message_customer_data( ) 
	{
		$groups = usam_get_property_groups([]);
		$properties = usam_get_properties(['type' => 'order']);	
		$order_id = $this->purchase_log->get('id');
		$message = "<table style='margin:20px 0; width:100%'>";
		foreach ( $groups as $group ) 
		{   
			$html = '';
			foreach( $properties as $property ) 
			{ 
				if ( $group->code == $property->group )
				{ 
					$value = usam_get_order_metadata( $order_id, $property->code );				
					if ( $value )
					{
						$html .= "<tr>";
						$html .= "<td style='text-align:right; padding:10px; width:30%'>{$property->name}:</td>";		
						$html .= "<td style='text-align:left; padding:10px; width:70%'>".usam_get_formatted_property( $value, $property )."</td>";	
						$html .= "</tr>";						
					}
				}
			}
			if ( $html )
			{
				$message .= "<tr><td style='color:#00; text-align:center; height:50px; font: 400 18px/22px Verdana, Arial, Tahoma;  border-bottom: #6d6a94 3px solid;'>{$group->name}</td></tr>";		
				$message .= "<td><table style='color:#000; width:100%'>$html</table>";
				$message .= "</td>";
			}
		}
		$message .= "</table>";
		return $message;
	}		
}

// Письмо администратору магазина о новом заказе
class USAM_Order_Admin_Notification extends USAM_Order_Notification
{			
//Письмо информация о транзакции
	public function get_raw_message() 
	{		
		$message = "<table style='height:87px; background-color:#6d6a94;color: #fff; width:100%; margin-bottom:100px'><tr>";	
		$message .= "<td style='text-align:center;width:81px;'></td>";	
		$message .= "<td style='font: 400 20px/23px Verdana, Arial, Tahoma;'>" . __('Получен новый заказ', 'usam') . ": #%order_id%</td>";	
		$message .= "<td style='text-align:center;width:81px;'></td>";	
		$message .= "</tr></table>";
		$message .= "<table style='height:87px; background-color: #6d6a94;color: #fff; font: 400 18px/23px Verdana, Arial, Tahoma; width:100%; margin:5px 0;'><tr>";	
		$message .= "<td style='text-align:center;width:33%;padding:0;font:400 12px/23px Verdana, Arial, Tahoma;color:#fff;height:31px;background-color:#7e7ba3;'>".__('Сумма заказа', 'usam')."</td>";	
		$message .= "<td style='text-align:center;width:33%;padding:0;font:400 12px/23px Verdana, Arial, Tahoma;color:#fff;height:31px;text-align:center;background-color:#6f6c97;'>".__('Сумма доставки', 'usam')."</td>";	
		$message .= "<td style='text-align:center;width:33%;padding:0;font:400 12px/23px Verdana, Arial, Tahoma;color:#fff;height:31px;text-align:center;background-color:#66628f;'>".__('Способ доставки', 'usam')."</td>";	
		$message .= "</tr><tr>";	
		$message .= "<td style='text-align:center;width:33%;padding:0 0 1px;font:400 20px/23px Verdana, Arial, Tahoma;color:#fff;height:68px;text-align:center;background-color:#9591ba;'>%order_final_basket_currency%</td>";	
		$message .= "<td style='text-align:center;width:33%;padding:0 0 1px;font:400 20px/23px Verdana, Arial, Tahoma;color:#fff;height:68px;text-align:center;background-color:#827eab;'>%total_shipping_currency%</td>";	
		$message .= "<td style='text-align:center;width:33%;padding:0 0 1px;font:400 20px/23px Verdana, Arial, Tahoma;color:#fff;height:68px;text-align:center;background-color:#7672a1;'>%shipping_method_name%</td>";	
		$message .= "</tr></table>";			
		// Скидка				
		$coupon_name = usam_get_order_metadata( $this->purchase_log->get('id'), 'coupon_name');	
		if ( $coupon_name ) 
		{
			$message .= "<table style='height:40px; background-color: #6d6a94;color: #fff; margin:20px 0; width:100%'><tr>";	
			$message .= "<td style='text-align:center;width:81px;'></td>";	
			$message .= "<td style='font: 400 18px/23px Verdana, Arial, Tahoma;'>" . __('Использован купон', 'usam') . ": %coupon_code%</td>";	
			$message .= "<td style='text-align:center;width:81px;'></td>";	
			$message .= "</tr></table>";
		}
		$message .= "<table style='margin:20px 0; width:100%; border-spacing:0;'>";	
		$message .= "<tr><td style='background-color:#f3f2f5; color:#000; text-align:center; border-left: #9591ba 3px solid; height:87px; font: 400 32px/44px Verdana, Arial, Tahoma;'>".__("Способ оплаты","usam")."</td></tr></table>";
		$message .= "%payment_list%\r\n";		
		$message .= "\r\n";
		// Товары
		$message .= "<table style='margin:20px 0; width:100%; border-spacing:0;'>";	
		$message .= "<tr><td style='background-color:#f3f2f5; color:#000; text-align:center; border-left: #9591ba 3px solid; height:87px; font: 400 32px/44px Verdana, Arial, Tahoma;'>".__("Купленные товары","usam")."</td></tr></table>";
		$message .= "%product_list%\r\n";		
		$message .= "\r\n";				
		$message .= "<table style='margin:20px 0; width:100%; border-spacing:0;'>";	
		$message .= "<tr><td style='background-color:#f3f2f5; color:#000; text-align:center; border-left: #9591ba 3px solid; height:87px; font: 400 32px/44px Verdana, Arial, Tahoma;'>".__("Личные данные клиента","usam")."</td></tr></table>";
		$message .= $this->add_message_customer_data( );				
		return apply_filters( 'usam_order_admin_notification_raw_message', $message, $this );
	}	
	
	public function get_html_message() 
	{
		return $this->html_message;
	}

	public function get_subject() 
	{
		return apply_filters( 'usam_order_admin_notification_subject', __('Получен новый заказ', 'usam'), $this );
	}
}
/* 
 * Подготовка письма при изменении статуса заказа
 */
class USAM_Сustomer_Notification_Change_Order_Status extends USAM_Order_Notification
{		
	public function get_raw_message()
	{	
		$status = $this->purchase_log->get('status');		
		$document_status = usam_get_object_status_by_code( $status, 'order' );
		$email_message = !empty($document_status['email']) ? $document_status['email'] : '';
		return apply_filters( 'usam_customer_notification_change_order_status_raw_message', $email_message, $this );
	}
	
	public function get_subject() 
	{
		$status = $this->purchase_log->get('status');	
		$document_status = usam_get_object_status_by_code( $status, 'order' );
		$subject_email = !empty($document_status['subject_email']) ? $document_status['subject_email'] : '';
		return apply_filters( 'usam_customer_notification_change_order_status_subject', $subject_email, $this );
	}
	
	public function get_raw_sms_message() 
	{	
		$status = $this->purchase_log->get('status');	
		$document_status = usam_get_object_status_by_code( $status, 'order' );
		return !empty($document_status['sms']) ? $document_status['sms'] : '';
	}	
}