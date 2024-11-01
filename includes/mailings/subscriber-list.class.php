<?php
// Добавить в подписку
function usam_set_subscriber_lists( $list )
{
	global $wpdb;		
	if ( empty($list['id']) || empty($list['communication']) )
		return false;
	
	$list_id = absint($list['id']);
	$communication = trim($list['communication']);	
	$status = isset($list['status'])?absint($list['status']):1;
	if ( !empty($list['type']) )
		$type = $list['type'];
	else
		$type = is_email($list['communication'])?'email':'phone';
	if ( $type == 'phone' )
	{
		if ( strlen((string)$communication) != 11 )
			return false;			
	}
	$subscriber_list = $wpdb->get_results( "SELECT status FROM ".USAM_TABLE_SUBSCRIBER_LISTS." WHERE `communication`='$communication' AND `list`='$list_id'" );	
	if ( !empty($subscriber_list) )
		$update = $wpdb->update( USAM_TABLE_SUBSCRIBER_LISTS, ['status' => $status], ['list' => $list_id, 'communication' => $communication, 'type' => $type] );
	else
		$update = $wpdb->insert( USAM_TABLE_SUBSCRIBER_LISTS, ['list' => $list_id, 'communication' => $communication, 'status' => $status, 'type' => $type]);	
	if ( $status == 2 )
		do_action( 'usam_unsubscribed_newsletter', $communication, $list_id ); // отписался	
	else
		do_action( 'usam_ubscribed_newsletter', $communication, $list_id ); // подписался
	return $update;	
}

function usam_update_mailing_statuses( $args = [] )
{
	global $wpdb;
	require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
	require_once( USAM_FILE_PATH .'/includes/feedback/mailing_list.php' );
	$args['fields'] = 'id';
	$lists = usam_get_mailing_lists( $args );
	foreach ( $lists as $list_id )
	{
		$results = $wpdb->get_results("SELECT COUNT(*) AS count, status FROM ".USAM_TABLE_SUBSCRIBER_LISTS." WHERE list='$list_id'");	
		$update = [];			
		foreach ( $results as $result )
		{
			switch ( $result->status ) 
			{
				case 1 :
					$update['subscribed'] = $result->count;
				break;
				case 2 :
					$update['unconfirmed'] = $result->count;
				break;
				case 3 :
					$update['unsubscribed'] = $result->count;
				break;	
			}
		}		
		usam_update_mailing_list($list_id, $update);
	}
}

// Удалить подписку
function usam_delete_subscriber_lists( $args )
{
	global $wpdb;		

	$where = "1=1";
	if ( !empty($args['communication']) ) 
	{
		$communications = implode("','", (array)$args['communication']);
		$where .= " AND communication IN ('$communications')";
	}	
	if ( !empty($args['id']) ) 
	{
		$ids = implode( ',', wp_parse_id_list((array)$args['id']) );
		$where .= " AND list IN ($ids)";
	}
	if ( !empty($args['type']) ) 
	{
		$types = implode( "','", (array)$args['type'] );
		$where .= " AND type IN ('$types')";
	}	
	return $wpdb->query("DELETE FROM ".USAM_TABLE_SUBSCRIBER_LISTS." WHERE $where");
}

// Удалить подписку
function usam_delete_subscriber_list( $communication )
{
	global $wpdb;			
	$wpdb->query( $wpdb->prepare("DELETE FROM ".USAM_TABLE_SUBSCRIBER_LISTS." WHERE communication = '%s'", $communication ));		
}

// Получить списки
function usam_get_subscriber_list( $condition, $colum = 'communication' )
{
	global $wpdb;	
	if ( is_array($condition) )
		$in = implode("','",$condition);
	else
		$in = $condition;
	
	return $wpdb->get_results( "SELECT * FROM " . USAM_TABLE_SUBSCRIBER_LISTS . " WHERE `$colum` IN ('".$in."')" );	
}