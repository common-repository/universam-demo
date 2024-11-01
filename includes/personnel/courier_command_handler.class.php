<?php
require_once( USAM_FILE_PATH . '/includes/personnel/command_handler.class.php');
class USAM_Courier_Command_Handler extends USAM_Command_Handler
{
	private static $contact_id;
	private static $contact;
	public static function start( $contact_id, $message )
	{				
		self::$contact_id = $contact_id;
		
		self::$contact = usam_get_contact( self::$contact_id );
		if ( strtotime(self::$contact['online']) < USAM_CONTACT_ONLINE )
		{ //не было 10 часов													
			$update['online'] = date("Y-m-d H:i:s");	
			usam_update_contact( self::$contact_id, $update );
		}		
		preg_match('/_usam[ ]?-[ ]?(.[^\s]*)/i', $message, $m);		
		if ( !empty($m[1]) )
		{
			$str = explode('-',$m[1]);		
			if ( isset($str[0]) )
			{
				$method = 'command_'.$str[0];			
				if ( method_exists(__CLASS__, $method) )
				{				
					$code = isset($str[1])?$str[1]:'';
					$message = str_replace($m[0],'',$message);	
					return self::$method( $message, $code );					
				}
			}
		}
		else
		{
			return self::message_handler( $message );			
		}
		return false;
	}
	
	//Информация о доставке
	private static function command_delivery_view( $message, $id ) 
	{		
		$document = self::get_shipped_document( $id );		
		if ( empty($document['error']) )
		{	
			$property_types = usam_get_order_property_types( $document['order_id'] );		
			$address = !empty($property_types['delivery_address']['_name'])?$property_types['delivery_address']['_name']:__("Нет данных","usam");
			$contact = !empty($property_types['delivery_contact']['_name'])?$property_types['delivery_contact']['_name']:__("Нет данных","usam");			
			$courier_delivery = usam_get_contact_metadata(self::$contact_id, 'courier_delivery');			
			if ( $courier_delivery == $id )
				$buttons = [['text' => __('Везу','usam'), 'callback_data' => '_usam-delivery_deliver-'.$document['id']]];
			else
				$buttons = [
					['text' => __('Отвез','usam'), 'callback_data' => '_usam-delivery_delivered-'.$document['id']], 
					['text' => __('Проблема с отгрузкой','usam'), 'callback_data' => '_usam-delivery_problem-'.$document['id']]
				];
			$date_delivery = self::get_date_delivery( $document['id'] );
			if ( $document['order_id'] )
				$message = sprintf( __("Доставка №%s %s\nЗаказ №%s\nПолучатель %s\nАдрес %s","usam"), $id, $date_delivery, $document['order_id'], $contact, $address );
			else
				$message = sprintf( __("Доставка №%s %s. Получатель %s. Адрес %s","usam"), $id, $date_delivery, $contact, $address );
			$phone = usam_get_order_customerdata($document['order_id'], 'mobilephone' );
			if ( !$phone )
				$phone = usam_get_order_customerdata($document['order_id'], 'phone' );
			if ( $phone )				
				$message .= ' '.__("т.","usam").' '.$phone;
			return ['message' => $message, 'buttons' => [$buttons]];			
		}
		return $document['error'];	
	}
	//Отвез доставку
	private static function command_delivery_delivered( $message, $id ) 
	{
		$document = self::get_shipped_document( $id );
		if ( empty($document['error']) )
		{	
			usam_work_completed(['operation' => 'delivery_deliver', 'object_id' => $id, 'object_type' => 'shipped_document']);
			usam_update_contact_metadata(self::$contact_id, 'courier_delivery', '');
			usam_update_shipped_document( $id, ['status' => 'shipped']);
			return array( 'message' => __("Принято","usam"), 'buttons2' => self::get_main_menu() );
		}
		return $document['error'];	
	}	
		
	//Не отвез доставку
	private static function command_delivery_problem( $message, $id ) 
	{
		$document = self::get_shipped_document( $id );
		if ( empty($document['error']) )
		{
			usam_work_completed(['operation' => 'delivery_deliver', 'object_id' => $id, 'object_type' => 'shipped_document']);
	
			usam_update_contact_metadata(self::$contact_id, 'courier_delivery', '');
			usam_update_shipped_document( $id, ['status' => 'delivery_problem']);
			
			$buttons = array();
			$problems = usam_get_standard_delivery_problems();
			foreach( $problems as $key => $title )
			{
				$buttons[] = [['text' => $title, 'callback_data' => "_usam-delivery_code_problem-{$key}_".$document['id']]];
			}
			return ['message' => __("Укажите причину","usam"), 'buttons' => $buttons];
		}
		return $document['error'];	
	}
	
	//Везу доставку
	private static function command_delivery_deliver( $message, $id ) 
	{
		$document = self::get_shipped_document( $id );
		if ( empty($document['error']) )
		{
			usam_insert_change_history(['object_id' => $id, 'object_type' => 'shipped_document', 'operation' => 'delivery_deliver']);
			usam_update_contact_metadata(self::$contact_id, 'courier_delivery', $id);
			$buttons = [['text' => __('Отвез','usam'), 'callback_data' => '_usam-delivery_delivered-'.$document['id']], ['text' => __('Проблема с отгрузкой','usam'), 'callback_data' => '_usam-delivery_problem-'.$document['id']]];				
			return ['message' => __("Принято! Что дальше?","usam"), 'buttons' => [$buttons]];
		}
		return $document['error'];		
	}
		
	//Проблема с доставкой
	private static function command_delivery_code_problem( $problem, $code ) 
	{
		$str = explode('_', $code);		
		$document = self::get_shipped_document( $str[1] );
		if ( empty($document['error']) )
		{			
			$problems = usam_get_standard_delivery_problems();
			if ( isset($problems[$str[0]]) )			
				usam_update_shipped_document_metadata( $document['id'], 'delivery_problem', $str[0] );
			return ['message' => __("Принято","usam"), 'buttons2' => self::get_main_menu()];
		}
		return $document['error'];	
	}
		
	private static function get_shipped_document( $id ) 
	{
		if ( $id )
		{
			$id = (int)$id;
			$document = usam_get_shipped_document( $id );
			if ( empty($document) )
				return array( 'error' => __('Документ не найден','usam'));
			
			if ( self::$contact_id == $document['courier'] || true )
				return $document;		
			else
				return ['error' => __('Вам не разрешено это действие','usam')];	
		}
		return ['error' => __('Что-то не так','usam') ];		
	}
	
	private static function get_courier_main_menu( ) 
	{	
		require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
		$statuses = usam_get_object_statuses(['type' => 'courier', 'fields' => 'code=>name']);	
		if ( isset($statuses[self::$contact['status']]) )
			unset($statuses[self::$contact['status']]);
		return array_merge( ['delivery_lists' => __('Заказы','usam')], $statuses );	
	}
	
	private static function get_main_menu( $exclude = '' ) 
	{
		$buttons = [];
		$menu = self::get_courier_main_menu();
		foreach( $menu as $key => $title )
		{
			if ( $exclude == '' || $exclude != $key  )
				$buttons[] = ['text' => $title];
		}
		return $buttons;
	}
	
	private static function get_date_delivery( $id ) 
	{
		$date_delivery = (string)usam_get_shipped_document_metadata( $id, 'date_delivery' );
		if ( $date_delivery )
		{
			if ( date("Y-m-d") !== date("Y-m-d", strtotime($date_delivery)) )
				$format = get_option( 'date_format', "d.m.Y" ).' H:i';
			else 
				$format = "H:i";
			$date_delivery = usam_local_date($date_delivery, $format);
		}
		return $date_delivery;
	}
	
	private static function message_handler( $message ) 
	{		
		$results = [];
		$message = mb_strtolower($message);	
		if ( $message == 'заказы' )			
		{		
			$meta_query = ['date_delivery' => ['key' => 'date_delivery', 'compare' => 'BETWEEN', 'type' => 'DATETIME', 'value' => ['0000-00-00 00:00:00', get_gmt_from_date(date('Y-m-d H:i:s', strtotime('now 00:00:00')+86400), "Y-m-d H:i:s")]]];
			require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
			$documents = usam_get_shipping_documents(['order' => 'DESC', 'orderby' => 'date_delivery', 'meta_query' => $meta_query, 'status' => 'expect_tc', 'courier' => self::$contact['user_id']]);	
			$buttons = [];
			foreach ( $documents as $document )	
			{
				$date_delivery = self::get_date_delivery( $document->id );	
				$property_types = usam_get_order_property_types( $document->id );
				$address = !empty($property_types['delivery_address']['_name'])?$property_types['delivery_address']['_name']:__("Нет данных","usam");
				$buttons[] = [['text' => "№".$document->id.' '.$date_delivery.' '.$address, 'callback_data' => '_usam-delivery_view-'.$document->id]];
			} 			
			if ( !empty($buttons) )
			{
			//	$buttons[] = [['text' => __('Обновить список доставок','usam'), 'callback_data' => __('Заказы','usam')]];	
				$results['message'] = __('Выберете доставку','usam');
				$results['buttons'] = $buttons;
				$results['buttons2'] = self::get_main_menu();				
			}
			else
			{
				$results['buttons2'] = self::get_main_menu();
				$results['message'] = __('Нет доставок для вас','usam');				
			}				
		}	
		else
		{
			$results = ['message' => __("Команда не понятна","usam"), 'buttons2' => self::get_main_menu()];
			$menu = self::get_courier_main_menu();	
			foreach( $menu as $key => $title )
			{
				if ( $message == mb_strtolower($title) )		
				{					
					usam_update_contact(self::$contact_id, ['status' => $key]);
					$results = ['message' => __("Принято","usam"), 'buttons2' => self::get_main_menu($key)];
					break;
				}
			}
		}		
		return $results;
	
	}
}
?>