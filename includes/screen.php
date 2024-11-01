<?php
/* Экранные функции
 */

// Установить сообщение
function usam_set_user_screen_message( $error, $current_screen, $type = 'message' )
{		
	global $user_screen_messages;
	if ( empty($error) )
		return;
	if ( !isset($user_screen_messages[$type]) )
		$user_screen_messages[$type] = array();	
	
	if ( !isset($user_screen_messages[$type][$current_screen]) )
		$user_screen_messages[$type][$current_screen] = array();
	if ( is_array($error))
		$user_screen_messages[$type][$current_screen] = array_merge($user_screen_messages[$type][$current_screen], $error);	
	else
		$user_screen_messages[$type][$current_screen][] = $error;		
}

// Получить сообщение
function usam_get_user_screen_message( $current_screen, $type = 'message' )
{	
	global $user_screen_messages;
	$return = false;
	if ( !empty($user_screen_messages[$type][$current_screen]) )
	{		
		$return = $user_screen_messages[$type][$current_screen];
		unset($user_screen_messages[$type][$current_screen]);
	}	
	return $return;
}

// Установить ошибку
function usam_set_user_screen_error( $error, $screen_id = null )
{		
	if ( $screen_id == null )
	{
		global $current_screen;
		if ( !empty($current_screen->id) )
			$screen_id = $current_screen->id;
		else
			$screen_id = 'user_screen';
	}		
	return usam_set_user_screen_message( $error, $screen_id, 'error' );
}

// Получить ошибку
function usam_get_user_screen_error( $screen_id = null )
{
	if ( $screen_id == null )
	{
		global $current_screen;
		if ( !empty($current_screen->id) )
			$screen_id = $current_screen->id;
		else
			$screen_id = 'user_screen';
	}
	return usam_get_user_screen_message( $screen_id, 'error' );
}

?>