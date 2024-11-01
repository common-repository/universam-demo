<?php 
function usam_get_url_object($url, $args = [] )
{	
	if ( !$url )
		return false;
	
	$ABI_URL_STATUS_OK = 200;
	$ABI_URL_STATUS_REDIRECT_301 = 301;
	$ABI_URL_STATUS_REDIRECT_302 = 302;
	$ABI_URL_STATUS_NOT_FOUND = 404;
	$MAX_REDIRECTS_NUM = 5;	
	$TIME_START = explode(' ', microtime());
	$TRY_ID = 0;
	$URL_RESULT = false;			
	do
	{			
		$URL_PARTS = @parse_url($url);		
		if( !is_array($URL_PARTS))
		{
			break;
		};
		$URL_SCHEME = ( isset($URL_PARTS['scheme']))?$URL_PARTS['scheme']:'http';
		$URL_HOST = ( isset($URL_PARTS['host']))?$URL_PARTS['host']:'';
		$URL_PATH = ( isset($URL_PARTS['path']))?$URL_PARTS['path']:'/';
		if( isset($URL_PARTS['port']) )		
			$URL_PORT = intval($URL_PARTS['port']);
		elseif ( $URL_SCHEME == 'https' )
			$URL_PORT = 443;
		else
			$URL_PORT = 80;
		if( isset($URL_PARTS['query']) && $URL_PARTS['query']!='' )
		{
			$URL_PATH .= '?'.$URL_PARTS['query'];
		};
		$URL_PORT_REQUEST = ( $URL_PORT == 80 )?'':":$URL_PORT";
		//--- build GET request ---
		$default = array( 
			'user_agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0',
			'user' => '',
			'password' => '',		
		);		
		$args = array_merge( $default, $args );			
		
		$GET_REQUEST = "GET $URL_PATH HTTP/1.0\r\n"
		."Host: $URL_HOST$URL_PORT_REQUEST\r\n"
		."Accept: text/plain\r\n"
		."Accept-Encoding: identity\r\n"
		."User-Agent: ".$args['user_agent']."\r\n\r\n";
		if ( $args['user'] != '' && $args['password'] != '' )
			$GET_REQUEST .= "Authorization: Basic ".base64_encode($args['user'].":".$args['password'])." \r\n\r\n";

		//--- open socket ---
		$SOCKET_TIME_OUT = 30;			
		$hostname = $URL_SCHEME == 'https' ?"ssl://".$URL_HOST:$URL_HOST;		

		$SOCKET = fsockopen($hostname, $URL_PORT, $ERROR_NO, $ERROR_STR, $SOCKET_TIME_OUT);		
		if( $SOCKET )
		{
			if( fputs($SOCKET, $GET_REQUEST))
			{ 	
				socket_set_timeout($SOCKET, $SOCKET_TIME_OUT);				
				$header = '';
				$SOCKET_STATUS = socket_get_status($SOCKET);
				while( !feof($SOCKET) && !$SOCKET_STATUS['timed_out'] )
				{
					$temp = fgets($SOCKET, 128);
					if( trim($temp) == '' ) 
						break;
					$header .= $temp;
					$SOCKET_STATUS = socket_get_status($SOCKET);					
				};				
				if( preg_match('~HTTP\/(\d+\.\d+)\s+(\d+)\s+(.*)\s*\\r\\n~si', $header, $res))
				   $SERVER_CODE = $res[2];
				else
				   break;		   
	
				if( $SERVER_CODE == $ABI_URL_STATUS_OK )
				{						
					$content = '';
					$SOCKET_STATUS = socket_get_status($SOCKET);					
					while( !feof($SOCKET) && !$SOCKET_STATUS['timed_out'] )
					{
						$content .= fgets($SOCKET, 1024*8);	
						$SOCKET_STATUS = socket_get_status($SOCKET);
					};			
					//--- time results ---
					$TIME_END = explode(' ', microtime());
					$TIME_TOTAL = ($TIME_END[0]+$TIME_END[1])-($TIME_START[0]+$TIME_START[1]);
					//--- output ---
					$URL_RESULT['header'] = $header;
					$URL_RESULT['content'] = $content;
					$URL_RESULT['time'] = $TIME_TOTAL;
					$URL_RESULT['description'] = '';
					$URL_RESULT['keywords'] = '';
					$URL_RESULT['url'] = $url;					
					$URL_RESULT['title'] =( preg_match('~<title>(.*)<\/title>~U', $content, $res))?strval($res[1]):'';
				
					if( preg_match_all('~<meta\s+name\s*=\s*["\']?([^"\']+)["\']?\s+content\s*=["\']?([^"\']+)["\']?[^>]+>~', $content, $res, PREG_SET_ORDER) > 0 )
					{
						foreach($res as $meta)
							$URL_RESULT[strtolower($meta[1])] = $meta[2];
					}					
				}
				elseif( $SERVER_CODE == $ABI_URL_STATUS_REDIRECT_301 || $SERVER_CODE == $ABI_URL_STATUS_REDIRECT_302 )
				{
					if( preg_match('~location\:\s*(.*?)\\r\\n~si', $header, $res))
					{
						$REDIRECT_URL = rtrim($res[1]);
						$URL_PARTS = @parse_url($REDIRECT_URL);
						if( isset($URL_PARTS['scheme'])&& isset($URL_PARTS['host']))
							$url = $REDIRECT_URL;
						else
							$url = $URL_SCHEME.'://'.$URL_HOST.'/'.ltrim($REDIRECT_URL, '/');
					}
					else
					{
						break;
					};
				};
			};
			fclose($SOCKET);
		}
		else
		{
			break;
		};
		$TRY_ID++; 
	}
	while( $TRY_ID <= $MAX_REDIRECTS_NUM && $URL_RESULT === false );		
	return $URL_RESULT;
};
?>