<?php 
/*
  Name: Модуль оплаты Robokassa
 */
class USAM_Merchant_robokassa extends USAM_Merchant 
{	
	protected $api_version = '2.0';
	protected $type_operation = 'a';
	protected $content_type = 'postget';	
	protected $ipn = true;
	protected $payment_link = 'https://auth.robokassa.ru/Merchant/Index.aspx';		
	/**
	* передает полученные данные в платежный шлюз
	*/
	function get_vars( $aggregate = false ) 
	{
		$order_id = $this->purchase_log->get('id'); 
		$args = array(
			'MrchLogin' => $this->gateway_system['login'], 
			'OutSum' => $this->payment['sum'],		
			'InvDesc' => sprintf( __("Оплата заказа №%s","usam"), $order_id),				
			'InvId' => $order_id,	      	//Поддерживает только цифры	
			'Culture' => strtolower($this->get_country_code()),
			'shp_payment_number' => $this->payment_number,
		);		
		if ( $this->is_debug() )
		{			
			$args['IsTest'] = 1;		
			$args['SignatureValue'] = md5($this->gateway_system['login'].':'.$this->payment['sum'].':'.$args['InvId'].':'.$this->gateway_system['key1_test'].':shp_payment_number='.$this->payment_number );
		}	
		else
		{			
			$args['SignatureValue'] = md5($this->gateway_system['login'].':'.$this->payment['sum'].':'.$args['InvId'].':'.$this->gateway_system['key1'].':shp_payment_number='.$this->payment_number );
		}
		return $args;
	}		
	
	/**
	 * Получить номер платежного документа
	 */
	protected function get_payment_number( ) 
	{ 
		if (isset($this->gateway_data['shp_payment_number']))
			return $this->gateway_data['shp_payment_number'];
		else
			return false;
	}
	
	/**
	 * Процесс шлюза уведомления, проверяет и решает, что делать с данными шлюза, выданных в торговых файлы
	*/
	protected function parse_gateway_notification() 
	{ 	
		$inv_id = absint($this->gateway_data['InvId']);
		$this->payment_number = $this->gateway_data['shp_payment_number'];	
		$out_summ = (float)$this->payment['sum'];		
		
		if ( $this->is_debug() )
			$key = $this->gateway_system['key1_test'];			
		else
			$key = $this->gateway_system['key2'];		
		
		if( !$this->gateway_data['hash'] )
		{			
			$this->update_payment_document( array( 'status' => 3 ) );	
			echo 'OK'.$inv_id;	
		}
		elseif( $this->gateway_data['hash'] == 'md5' && $this->gateway_data['SignatureValue'] == strtoupper(md5($this->gateway_data['OutSum'].':'.$this->gateway_data['InvId'].':'.$key.':shp_payment_number='.$this->payment_number)) )
		{				
			$this->update_payment_document( array( 'status' => 3 ) );	
			echo 'OK'.$inv_id;	
		}
		else
			$this->notification_errors( 'signature' );
	}
	
	protected function get_default_option( ) 
	{
		return array( 'login' => '', 'key1' => '', 'key2' => '', 'key1_test' => '', 'key2_test' => '', 'hash' => '');
	}
	
	public function get_form()
	{	
		$output = "
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label for='option_gateway_login'>".esc_html__('Идентификатор магазина', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<input type='text' id='option_gateway_login' size='40' value='".$this->gateway_system['login']."' name='gateway_handler[login]' />
					</div>
				</div>
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label for='option_gateway_key1'>".esc_html__('Пароль #1', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<input type='text' id='option_gateway_key1' size='40' value='".$this->gateway_system['key1']."' name='gateway_handler[key1]' />
					</div>
				</div>
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label for='option_gateway_key2'>".esc_html__('Пароль #2', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<input type='text' id='option_gateway_key2' size='40' value='".$this->gateway_system['key2']."' name='gateway_handler[key2]' />
					</div>
				</div>
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label for='option_gateway_key1_test'>".esc_html__('Пароль #1 тестового режима', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<input type='text' id='option_gateway_key1_test' size='40' value='".$this->gateway_system['key1_test']."' name='gateway_handler[key1_test]' />
					</div>
				</div>
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label for='option_gateway_key2_test'>".esc_html__('Пароль #2 тестового режима', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<input type='text' id='option_gateway_key2_test' size='40' value='".$this->gateway_system['key2_test']."' name='gateway_handler[key2_test]' />
					</div>
				</div>
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label for='option_hash'>".esc_html__('Алгоритм расчета хеша', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<select name='gateway_handler[hash]' id='option_hash'>
							<option value='0' ". selected( $this->gateway_system['hash'],'', false ) .">" . __('Не использовать', 'usam') . "</option>
							<option value='md5' ". selected( $this->gateway_system['hash'], 'md5', false ) .">" . __('MD5', 'usam') . "</option>							
						</select>					
					</div>
				</div>";
		return $output;
	}
}
?>