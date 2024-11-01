<?php
class USAM_Request_Processing
{		
	public static function verify_nonce( $request ) 
	{				
		$nonce = $request->get_header('X-WP-Nonce');
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) )
			return false;
		return true;
	}
	
	public static function permission( $request ) 
	{				
		if( current_user_can('universam_api') )
			return true;
		$nonce = $request->get_header('X-WP-Nonce');
		if( ! wp_verify_nonce( $nonce, 'wp_rest' ) )
			return false;
		return true;
	}
	
	public static function sanitize_text( $text ) 
	{	
		return sanitize_text_field(trim(stripslashes($text)));
	}
	
	public static function sanitize_textarea( $text ) 
	{	
		return sanitize_textarea_field(trim(stripslashes($text)));
	}
	
	public static function sanitize_date( $date ) 
	{	 
		if( $date )
		{	   
			$date = date("Y-m-d H:i:s", strtotime($date) );
			$date = get_gmt_from_date($date, "Y-m-d H:i:s");
		}
		return $date;
	}
	
	public static function sanitize_array_string( $text ) 
	{			
		if ( $text === 'all' )
			return $text;
		if ( is_string($text) )
			$text = explode(',', $text);
		if ( is_array($text) )
		{
			$text = array_map('stripslashes', $text);
			$text = array_map('trim', $text);
			$text = array_map('sanitize_text_field', $text);			
		}
		return $text;
	}
	
	public static function sanitize_array_bollean( $number ) 
	{	 
		if ( is_string($number) )
			$number = $number === 'true';
		elseif ( is_numeric($number) )
			$number = $number > 0;
		return $number;
	}
	
	public static function sanitize_array_number( $number ) 
	{	
		if ( is_string($number) )
			$number = explode(',', $number);
		if ( is_array($number) )
			$number = array_map('absint', $number);
		return $number;
	}
}
?>