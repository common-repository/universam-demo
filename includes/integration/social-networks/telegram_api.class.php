<?php
class USAM_Telegram_API
{
    private $url_api = "https://api.telegram.org/";
    private $token = "";
	private $option = array();

	public function __construct( $option ) 
	{
		$this->token = !empty($option['access_token'])?$option['access_token']:$this->token;
		$this->option = $option;
	}
			
	public function set_webhook( )
    {
        if ( $this->token )
			return $this->send_request('setWebhook', array('url' => home_url()."/api/telegram?token={$this->token}"));
		else
			return false;
    }

	public function set_buttons( $args )
    {        
		return $this->send_request('InlineKeyboardMarkup', $args );
    }	
	
	public function get_user_photos( $args )
    {        
		return $this->send_request('getUserProfilePhotos', $args );
    }	
	
	public function get_photo_url( $file_id )
    {        
		$result = $this->send_request('getFile', array( 'file_id' => $file_id ) );		
		if ( !empty($result['file_path']) )
			return $this->url_api."file/bot{$this->token}/".$result['file_path'];
		else
			return false;
    }	
	
	public function get_account_info( )
    {
       return $this->send_request('getMe');
    }
		
	public function message_post( $chat_id, $text )
    {
        $text = $this->message_decode($text);
		return $this->send_request('sendMessage', ['chat_id' => $chat_id, 'text' => $text]);
    }
	
	public static function escapeMarkdownV2($text)
	{       
		$markdown = ['#', '*', '_', '-', '=', '.', '[', ']', '(', ')', '!'];      
        $replacements = ['\#', '\*', '\_', '\-', '\\=', '\.', '\[',  '\]', '\(', '\)', '\!'];
        return str_replace($markdown, $replacements, $text);
    }
	
	private function message_decode( $message, $mode = 'MarkdownV2' )
	{	 
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8'); 
		$message = htmlspecialchars_decode($message);  
		if ( $mode === 'MarkdownV2' )
			$message = $this->escapeMarkdownV2( $message );
		$message = str_replace(['<b>','</b>'], '*', $message );	
		$message = str_replace(['<i>','</i>'], '_', $message );	
		$message = str_replace(['<h>','</h>'], '```', $message );
		
		$message = strip_tags($message);
		
		$message = str_replace('\n', chr(10), $message );		
		return $message;
	}
	
	public function send_message( $params )
    {			
        if ( !empty($params['contact_id']) )
			$params['user_id'] = usam_get_contact_metadata( $params['contact_id'], 'telegram_user_id' ); 
		if ( !empty($params['user_id']) )
		{		
			$args = ['chat_id' => $params['user_id']];		
			if ( !empty($params['message']) )
			{			
				$args['parse_mode'] = !empty($params['parse_mode'])?$params['parse_mode']:'MarkdownV2';					
				$args['text'] = $this->message_decode($params['message'], $args['parse_mode']);					
			}			
			if ( !empty($params['reply_markup']) )	
				$args['reply_markup'] = json_encode($params['reply_markup'], JSON_UNESCAPED_UNICODE);						
			return $this->send_request('sendMessage', $args );
		}
		else
			$this->set_error( __('Не указан отправитель','usam') );
		return false;
    }
	
	public function get_updates( )
    {
        return $this->send_request( 'getUpdates' );
    }
	
    private function send_request($method, $params = array() )
    {		  
		if ( empty($this->token) )
			return false;			

		$url_api = $this->url_api."bot{$this->token}/{$method}";		
		$headers["Accept"] = 'application/json';
		$headers["Content-type"] = 'application/json';	
		$data = wp_remote_get( $url_api, ['sslverify' => true, 'body' => $params, 'headers' => $headers]);	
		if ( is_wp_error($data) )
			return $data->get_error_message();
		$resp = json_decode($data['body'], true);		  
		if ( isset($resp['error_code'] ) ) 
		{			
			if ( isset($resp['description']) )
				$this->set_error( $resp['description'] );	
			return false;
		}		
		return $resp['result'];		
    }
	
	protected function set_error( $error )
	{
		usam_log_file( __('Telegram','usam').': '.$error );
	}
}
?>