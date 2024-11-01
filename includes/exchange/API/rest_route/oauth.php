<?php
class USAM_Oauth
{			
	public static function yandex( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_query_params();			
		if ( !empty($parameters['code']) )
		{
			$yandex = get_option( 'usam_yandex' );
			$result = self::get_access_token('https://oauth.yandex.ru/token', ['grant_type' => 'authorization_code', 'client_id' => $yandex['client_id'], 'client_secret' => $yandex['client_secret'], 'code' => $parameters['code']]);			
			if ( !empty($result['access_token']) )
			{
				$yandex = get_option( 'usam_yandex' );			
				$yandex['access_token'] = $result['access_token'];
				$yandex['refresh_token'] = $result['refresh_token'];
				$yandex['expires_in'] = $result['expires_in']+time();	
				update_option('usam_yandex', $yandex);
				return 'OK';
			}
			elseif ( isset($resp['error']) ) 
			{			
				usam_log_file( $resp['error'] );
				return false;
			}	
		}
		return false;
	}
	
	private static function get_access_token( $url, $params )
	{	
		$data = wp_remote_post($url, ['method' => 'POST', 'sslverify' => true, 'body' => $params]);
		if ( is_wp_error($data) )
		{
			usam_log_file( $data->get_error_message() );
			return false;
		}
		return json_decode($data['body'], true);
	}	
}
?>