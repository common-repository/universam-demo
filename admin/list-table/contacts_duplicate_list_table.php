<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_contacts_duplicate extends USAM_List_Table
{	
	private $contacts = array();
	public function extra_tablenav( $which ) 
	{
		if ( 'top' == $which && $this->filter_box ) 
		{		
			?>
			<div class="alignleft actions bulkactions">
				<div class = "usam_manage usam_manage-contacts_duplicate">	
					<input type='checkbox' id ="find_duplicate_phone"/>
					<label for="phone"><?php esc_html_e( 'Телефон', 'usam'); ?></label>
					<input type='checkbox' id ="find_duplicate_email"/>
					<label for="email"><?php esc_html_e( 'E-mail', 'usam'); ?></label>
					<input type='checkbox' id ="find_duplicate_user"/>
					<label for="user"><?php esc_html_e( 'Личные кабинеты', 'usam'); ?></label>					
					
					<?php submit_button( __('Найти дубликаты', 'usam'), 'button-secondary', 'find-duplicates', false ); ?>
					<?php submit_button( __('Объединить', 'usam'), 'button-secondary', 'button-combine', false ); ?>
				</div>				
			</div>
			<?php			
		}			
	}		
		
	function column_ID( $item ) 
    {			
		$out = "<a href='".usam_get_contact_url( $item['id'] )."' target='_blank'>".$item['id']."</a>";
		foreach( $item['items'] as $id )
		{				
			$out .= "<hr size='1' width='100%'><a href='".usam_get_contact_url( $id )."' target='_blank'>$id</a>";;	
			$out .= "<input type='hidden' name='duplicate_".$item['id']."' value='$id' />";
		}			
		echo $out;
	}	
	
	function column_contact( $item ) 
    {		
		$count = 0;	
		$out = '';
		foreach( $item['items'] as $id )
		{			
			if ( $this->contacts[$id]->appeal != '' )
				$count++;			
			$out .= '<br><hr size="1" width="100%">&nbsp;&nbsp;&nbsp;&nbsp;<i>'.$this->contacts[$id]->appeal.'</i>';		
			$out .= ' - <span class="object_date">'.usam_local_date( $this->contacts[$id]->date_insert, "d.m.Y" ).'</span> ';
			if ( $this->contacts[$id]->user_id )
				$out .= '<span class="item_status_valid item_status"><a href="'.add_query_arg(['user_id' => $this->contacts[$id]->user_id], admin_url('user-edit.php') ).'">ЛК</a></span> ';				
			ob_start();
			if ( $this->contacts[$id]->contact_source == 'employee' )
				usam_display_status( $this->contacts[$id]->status, 'employee' );
			else
				usam_display_status( $this->contacts[$id]->status, 'contact' );
			$out .= ob_get_clean();				
			$out .= ' <strong>'.usam_get_name_contact_source( $this->contacts[$id]->contact_source ).'</strong>';
		}		
		echo '<i>'.$this->contacts[$item['id']]->appeal.'</i>';
		echo ' - <span class="object_date">'.usam_local_date( $this->contacts[$item['id']]->date_insert, "d.m.Y" ).'</span> ';		
		if ( $this->contacts[$item['id']]->user_id )
			echo '<span class="item_status_valid item_status"><a href="'.add_query_arg(['user_id' => $this->contacts[$item['id']]->user_id], admin_url('user-edit.php') ).'">ЛК</a></span> ';			
		if ( $this->contacts[$item['id']]->contact_source == 'employee' )
			usam_display_status( $this->contacts[$item['id']]->status, 'employee' );
		else
			usam_display_status( $this->contacts[$item['id']]->status, 'contact' );		
		
		echo ' '.usam_get_name_contact_source( $this->contacts[$item['id']]->contact_source );
		echo ' '.$this->get_message( $count );
		echo $out;
	}	

	function column_email( $item ) 
    {		
		$count = 0;	
		$out = '';		
		foreach( $item['items'] as $id )
		{
			if ( $this->contacts[$id]->email != '' )
				$count++;
			$out .= '<br><hr size="1" width="100%">&nbsp;&nbsp;&nbsp;&nbsp;'.$this->contacts[$id]->email;			
		}		
		echo $this->contacts[$item['id']]->email.$this->get_message( $count ).$out;	
	}	
	
	function column_phone( $item ) 
    {		
		$count = 0;	
		$out = '';
		foreach( $item['items'] as $id )
		{
			if ( $this->contacts[$id]->phone != '' )
				$count++;
			$out .= '<br><hr size="1" width="100%">&nbsp;&nbsp;&nbsp;&nbsp;'.$this->contacts[$id]->phone;			
		}	
		echo $this->contacts[$item['id']]->phone.$this->get_message( $count ).$out;	
	}
	
	private function get_message( $count ) 
    {
		return $count>0?' '.'<b>'.sprintf( _n('Найдено %s совпадений','Найдено %s совпадений', $count, 'usam'), $count ).'</b>':'';	
	}
	 	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',			
			'id'             => __('ID', 'usam'),		
			'contact'        => __('Контакт', 'usam'),	
        );	
		if ( isset($_REQUEST['email']) )
			$columns['email'] = __('Почта', 'usam');
		if ( isset($_REQUEST['phone']) )
			$columns['phone'] = __('Телефон', 'usam');
        return $columns;
    }	
	
	function prepare_items() 
	{		
		global $wpdb;					
	
		if ( !isset($_REQUEST['phone']) && !isset($_REQUEST['email']) && !isset($_REQUEST['user']))			
			return;
		
		set_time_limit(2400);

		$this->get_standart_query_parent( );
		
		$select = ['contact.appeal', 'contact.id', 'contact.date_insert', 'contact.status', 'contact.number_orders', 'contact.contact_source', 'contact.user_id']; 
		$this->where = array( "1=1" );
		$phone_properties = usam_get_properties(['type' => 'contact', 'active' => 1, 'field_type' => array('mobile_phone', 'phone'), 'fields' => 'code']);
		$email_properties = usam_get_properties(['type' => 'contact', 'active' => 1, 'field_type' => 'email', 'fields' => 'code']);	
		if ( isset($_REQUEST['user']) )	
		{
			$this->joins[] = "LEFT OUTER JOIN (SELECT MIN(id) AS id, user_id FROM `".USAM_TABLE_CONTACTS."` WHERE user_id != '0') AS tmp2 ON (contact.id=tmp2.id)";
			$this->where[] = "tmp2.id IS NULL";
			$this->where[] = "contact.user_id != 0";
		}		
		$phone_join = USAM_TABLE_CONTACT_META." AS com_phone ON (com_phone.contact_id=contact.id AND com_phone.meta_key IN ('".implode("','",$phone_properties)."'))";
		if ( isset($_REQUEST['phone']) )	
		{							
			$this->joins[] = "INNER JOIN ".$phone_join;			
			$this->joins[] = "INNER JOIN (SELECT meta_value FROM `".USAM_TABLE_CONTACT_META."` WHERE meta_key IN ('".implode("','",$phone_properties)."') AND meta_value != '' GROUP BY meta_value HAVING COUNT(meta_value) >1) AS phone_dupes ON (com_phone.meta_value=phone_dupes.meta_value)";		
			$select[] = "IFNULL( com_phone.meta_value,'') AS phone";
		}				
		$email_join = USAM_TABLE_CONTACT_META." AS com_email ON (com_email.contact_id=contact.id AND com_email.meta_key IN ('".implode("','",$email_properties)."'))";
		if ( isset($_REQUEST['email']) )
		{
			$this->joins[] = "INNER JOIN ".$email_join;		
			$this->joins[] = "INNER JOIN (SELECT meta_value FROM `".USAM_TABLE_CONTACT_META."` WHERE meta_key IN ('".implode("','",$email_properties)."') AND meta_value != '' GROUP BY meta_value HAVING COUNT( meta_value ) >1) AS email_dupes ON (com_email.meta_value=email_dupes.meta_value)";				
			$select[] = "IFNULL( com_email.meta_value,'') AS email";				
		}	
		$where = implode( ' AND ', $this->where );		
		$joins = implode( ' ', $this->joins );			
		$sql = "SELECT ".implode( ', ', $select )." FROM ".USAM_TABLE_CONTACTS." AS contact $joins WHERE $where ORDER BY contact.id ASC LIMIT 5000";	
		$contacts = $wpdb->get_results( $sql );				
		foreach(  $contacts as $data )
		{
			$this->contacts[$data->id] = $data;
		}		
		$contacts = $this->contacts;
		foreach( $this->contacts as $key1 => $data1 )
		{
			$items = array();
			unset($contacts[$key1]);
			foreach( $contacts as $key2 => $data2 )
			{					
				if ( isset($_REQUEST['phone']) )	
				{
					if ( $data1->phone == $data2->phone )
					{
						$items[] = $data2->id;
						unset($contacts[$key2]);
						continue;
					}				
				}
				if ( isset($_REQUEST['email']) )	
				{ 
					if ( $data1->email == $data2->email )
					{ 
						$items[] = $data2->id;
						unset($contacts[$key2]);
						continue;
					}				
				}
				if ( isset($_REQUEST['user']) )	
				{ 
					if ( $data1->user_id == $data2->user_id )
					{ 
						$items[] = $data2->id;
						unset($contacts[$key2]);
						continue;
					}				
				}
			}
			if ( !empty($items) )
			{					
				$this->items[] = array( 'id' => $data1->id, 'items' => $items);
			}
		}	
	}
}
?>