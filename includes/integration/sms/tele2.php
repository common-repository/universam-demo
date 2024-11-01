<?php
// Name: TELE2 шлюз
class USAM_SMS_Gateway_TELE2 extends USAM_SMS_Gateway
{		
	protected $API_URL = 'https://target.tele2.ru/api/v2/';
	protected function send( $phone_id, $message, $naming )
	{ 
		$naming = mb_convert_encoding($naming, 'windows-1251', mb_detect_encoding($naming) );
		$params = array( 'operation' => 'send', 'msisdn' => $phone_id, 'text' => $message, 'shortcode' => $naming, 'login' => $this->login, 'password' => $this->password );
			
		$response = $this->send_request( $params, 'send_message' );	
		$response = json_decode($response, true);	
		if ( !empty($response) )
		{				
			if ( $response['status'] != 'error' )
				return $response['result']['uid'];	
			else
			{
				$errors = array('wrong_ip' => 'IP, с которого поступил запрос, не входит в список разрешённых для данной рассылки.', 'wrong_credentials' => 'Неверный логин или пароль', 'auth_required' => 'Требуется HTTP авторизация', 'transaction_not_found' => 'Формат указанной транзакции корректный, но сообщение не найдено', 'system_error' => 'Внутренняя ошибка сервиса; из-за системной ошибки невозможно сохранение сообщения.', 'invalid_request_body' => 'Неверный текст запроса', 'invalid_field_msisdn' => 'Неверный формат MSISDN', 'invalid_field_shortcode' => 'Неверное имя отправителя', 'invalid_field_text' => 'Неверный текст сообщения: пустой или null' );	
				$error = isset($errors[$response['reason']])?$errors[$response['reason']]:$response['reason'];
				$this->set_error( $error );
			}
		}
		return false;
	}
	
	protected function get_headers(  )
	{
		$headers["Authorization"] = 'Basic '.base64_encode($this->login.':'.$this->password);
		return $headers;
	}	
}
?>