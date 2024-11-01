<?php
/**
 * Файл содержит функции и классы для управления сотрудниками компании
 */
 
function usam_get_manager_name( $user_id, $format = 'lastname_f_p' )
{
	$contact = usam_get_contact( $user_id, 'user_id' );	
	if ( empty($contact['appeal']) )
	{
		$user = get_user_by('id', $user_id );
		$name = isset($user->display_name)?"$user->display_name":'';
	}
	else
		 $name = $contact['appeal'];
	return $name; 
}

function usam_get_manager_phone( $user_id = null )
{
	if ( $user_id == null )
		$user_id = get_current_user_id();
	
	$phone = (int)get_the_author_meta('usam_phone', $user_id);
	return $phone; 
}


function usam_get_manager_type_price( $user_id = null )
{
	if ( $user_id == null )
		$user_id = get_current_user_id();

	$type_price = get_the_author_meta( 'manager_type_price', $user_id ); 
	if ( !empty($type_price) )
		$price = usam_get_name_price_by_code( $type_price );
	
	if ( empty($price) )
	{
		$type_prices = usam_get_prices( );			
		foreach ( $type_prices as $id => $value )
		{	
			$type_price = $value['code'];
			break;				
		}					
		update_user_meta($user_id, 'manager_type_price', $type_price ); 		
	}	
	return $type_price; 
}

function usam_get_manager_signature_email( $mailbox_id = null )
{	
	if ( $mailbox_id == null )	
		$mailbox_id = usam_get_customer_primary_mailbox_id( );
	$user_id = get_current_user_id(); 
	
	require_once( USAM_FILE_PATH .'/includes/mailings/signature_query.class.php'  );
	$signatures = usam_get_signatures(['mailbox_id' => array(0, $mailbox_id), 'manager_id' => $user_id]);		
	
	
	$signature_email = isset($signatures[0])?str_replace( array("\n\r","/\n>+/u"), '<br>', '<div class="js-signature">'.$signatures[0]->signature ).'</div>':'';		
	return $signature_email;
}

function usam_get_signature( $id )
{	
	global $wpdb;	
	$result = $wpdb->get_row( "SELECT * FROM ".USAM_TABLE_SIGNATURES." WHERE id=$id", ARRAY_A);
	return $result;	
}

// Получить подчиненных
function usam_get_subordinates( $contact_id = null, $fields = 'user_id' )
{
	if ( $contact_id == null )	
		$contact_id = usam_get_contact_id();
	
	if ( !$contact_id )
		return [];
	
	$results = array();
	if ( current_user_can('personnel_management') && !usam_check_current_user_role('administrator') )
	{
		$results = (array)usam_get_contacts(['fields' => $fields, 'source' => 'employee', 'orderby' => 'name']);
	}
	else
	{ 
		require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
		$department_ids = usam_get_departments(['chief' => $contact_id, 'fields' => 'id']);
		if ( !empty($department_ids) )
		{
			$results = (array)usam_get_contacts(['fields' => $fields, 'source' => 'employee', 'meta_query' => [['key' => 'department', 'value' => $department_ids, 'compare' => 'IN']], 'orderby' => 'name']);
		}
	}
	if ( $fields == 'user_id' )
		$results = array_unique($results);
	return $results;
}

function usam_change_block( $url, $title ) 
{
	if ( current_user_can('edit_theme_options') )
	{
		$user_id = get_current_user_id();
		?><div class="change_block <?php echo !get_user_meta($user_id, 'edit_theme', true)?'hide':''; ?>"><span class="change_block__title"><a href="<?php echo $url; ?>"><?php echo $title; ?></a></span></div><?php
	}
}
?>