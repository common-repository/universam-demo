<?php 
class USAM_Newsletter_API extends USAM_API
{	
	public static function insert_newsletter( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$id = usam_insert_newsletter( $parameters );
		if ( isset($parameters['settings']) )
		{
			$settings = stripslashes_deep($parameters['settings']);	
			if( usam_update_newsletter_metadata( $id, 'settings', $settings ) )
			{
				require_once( USAM_FILE_PATH . '/admin/includes/mail/usam_edit_mail.class.php' );			
				$mail = new USAM_Edit_Newsletter( $id );
				$mail->save_mailcontent();
			}
			usam_delete_newsletter_metadata( $id, 'content_blocks' );	
		}
		if( isset($parameters['body']) && isset($parameters['type']) && $parameters['type'] == 'sms' )
			usam_update_newsletter_metadata( $id, 'body', $parameters['body'] );
		if( isset($parameters['event_start']) )
			usam_update_newsletter_metadata( $id, 'event_start', $parameters['event_start'] );
		if( isset($parameters['conditions']) )
			usam_update_newsletter_metadata( $id, 'conditions', $parameters['conditions'] );
		if( !empty($parameters['lists']) )
		{
			$lists = array_map('intval', $parameters['lists']);		
			usam_update_newsletter_lists( $id, $lists );	
		}		
		return $id;
	}
	
	public static function update_newsletter( WP_REST_Request $request ) 
	{				
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		if ( isset($parameters['pricelist']) )
		{
			$ids = !empty($parameters['pricelist'])?array_map('intval', $parameters['pricelist']):[];
			usam_save_array_metadata($id, 'newsletter', 'pricelist', $ids);	
		}
		if ( isset($parameters['settings']) )
		{
			$settings = stripslashes_deep($parameters['settings']);	
			if( usam_update_newsletter_metadata( $id, 'settings', $settings ) )
			{
				require_once( USAM_FILE_PATH . '/admin/includes/mail/usam_edit_mail.class.php' );			
				$mail = new USAM_Edit_Newsletter( $id );
				$mail->save_mailcontent();
			}
			usam_delete_newsletter_metadata( $id, 'content_blocks' );	
		}
		if( isset($parameters['body']) && isset($parameters['type']) && $parameters['type'] == 'sms' )
			usam_update_newsletter_metadata( $id, 'body', $parameters['body'] );
		if( !empty($parameters['lists']) )
		{
			$lists = array_map('intval', $parameters['lists']);		
			usam_update_newsletter_lists( $id, $lists );	
		}
		if( isset($parameters['event_start']) )
			usam_update_newsletter_metadata( $id, 'event_start', $parameters['event_start'] );
		if( isset($parameters['conditions']) )
			usam_update_newsletter_metadata( $id, 'conditions', $parameters['conditions'] );	
		return usam_update_newsletter( $id, $parameters );
	}
	
	public static function sent_preview_newsletter( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		$newsletter = new USAM_Send_Newsletter( $id );	
		return $newsletter->send_mail_preview( $_GET['email'] );	
	}
	
	public static function get_newsletter( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );		
		$newsletter = usam_get_newsletter( $id );
		return $newsletter;
	}	
	
	public static function delete_newsletter( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );		
		return usam_delete_newsletter( $id );
	}
	
	public static function get_newsletters( WP_REST_Request $request, $parameters = null ) 
	{	
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );	
		
		$query = new USAM_Newsletters_Query( self::$query_vars );	
		$items = $query->get_results();			
		if ( !empty($items) )
		{				
		//	foreach( $items as &$item )
		//		$item->subject = stripslashes($item->subject);
			$count = $query->get_total();
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}	
}
?>