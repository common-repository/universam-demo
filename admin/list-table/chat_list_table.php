<?php
require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_Chat extends USAM_List_Table 
{	
	protected $orderby = 'end_date';		
	protected $order = 'desc';
	protected $status = 'desc';
	
	function __construct( $args = array() )
	{			
		if ( !empty($_REQUEST['status']) )
			$this->status = sanitize_title($_REQUEST['status']);
		else	
			$this->status = 'all';		
		parent::__construct( $args );
    }
	
	public function get_views() 
	{
		$url = remove_query_arg( array('post_status', 'paged', 'action', 'action2', 'm',  'paged', 's', 'orderby','order') );	
		$all_class = $this->status == 'all' ? 'class="current"' : '';	
		$unanswered_class = $this->status == 'unanswered' ? 'class="current"' : '';			
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', add_query_arg('status', 'all', $url ), $all_class, __("Все", "usam") ), 'unanswered' => sprintf('<a href="%s" %s>%s</a>', add_query_arg('status', 'unanswered', $url ), $unanswered_class, __("Не отвеченные", "usam") ));
		return $views;
	}
	
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];		
	}
	
	function column_messages( $item ) 
    {		
		if ( $item->end_message != ''  )
		{
			if ( $item->end_status == 0 )
				$status_message = 'message_not_sent'; 
			elseif ( $item->end_status == 1 )		
				$status_message = 'message_not_read'; 
			else
				$status_message = ''; 
			
			echo "<div class='status_message $status_message'>";
			$contact = usam_get_contact( $item->contact_message );		
			if ( !empty($contact) )
			{			
				echo "<div class='user_block user_block_in_width'>";
				echo "<div class='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $contact['id'] ) )."'></div>";
				echo "<div class='user_block__content'>
					<div class='user_block__user'><div class='user_block__user_name'>".$contact['appeal']."</div><div class='user_block__user_date'>".usam_local_date($item->end_date)."</div></div>
					<div class='user_block__message'><span class='user_block__message_preview'>$item->end_message</span></div>
				</div>";
				echo "</div>";	
			}
			else
				echo $item->end_message;	
			echo "</div>";	
		}
    }	
	
	protected function column_cb( $item ) 
	{			
		$checked = in_array($item->id, $this->records )?"checked='checked'":""; 
		echo "<input id='checkbox-".$item->id."' type='checkbox' name='cb[]' value='".$item->id."' ".$checked."/>";
		echo usam_system_svg_icon( $item->channel );
    }
   
    function get_bulk_actions_display() 
	{
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}	

	function column_customer( $item ) 
	{				
		$contact = usam_get_contact( $item->contact_id );		
		if ( !empty($contact) )
		{			
			$actions = array( );	
			if( current_user_can('view_communication_data') )
			{
				$emails = usam_get_contact_emails( $item->contact_id );	
				$phones = usam_get_contact_phones( $item->contact_id );		
				if ( !empty($emails) )
					$actions['send_message'] = "<a class='js-open-message-send' data-emails='".implode(',',$emails)."' data-name='".$contact['appeal']."'>".__('Сообщение', 'usam')."</a>";
				if ( !empty($phones) )
				{
					$actions['send_sms'] = "<a class='js-open-sms-send' data-phones='".implode(',',$phones)."' data-name='".$contact['appeal']."'>".__('SMS', 'usam')."</a>";
					$actions['add_phone'] = "<a class='js-communication-phone' data-phones='".implode(',',$phones)."' data-name='".$contact['appeal']."'>".__('Звонок', 'usam')."</a>";
				}
			}
			$online = "";
			if ( !empty($contact['online']) )
			{
				if ( strtotime($contact['online']) >= USAM_CONTACT_ONLINE )
					$online = "<span class='customer_online'></span>";
				else
					$online = "<span class='date_visit'>".sprintf( __('был %s', 'usam'), get_date_from_gmt($contact['online'], 'd.m.Y H:i'))."</span>";
			}			
			if ( !empty($item->not_read) )		
				$count_new = '<div class="count_new">+'.$item->not_read.'</div>';
			else
				$count_new = '<div class="count_new"></div>';
			$url = usam_get_contact_url( $item->contact_id );			
			echo "<div class='user_block'>";
			echo "<a href='$url' class='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $item->contact_id ) )."'>$count_new</a>";
			echo "<div class='user_block__name'>";
			$this->row_actions_table( "<a href='$url'>".$contact['appeal']. "</a>$online", $actions );
			echo "</div></div>";			
				
		}
	}
	
	function column_manager( $item ) 
	{				
		$contact = usam_get_contact( $item->manager_id );	
		if ( !empty($contact) )
		{		
			?><a href="<?php echo usam_get_contact_url($item->manager_id); ?>"><?php echo $contact['appeal']; ?></a><?php
		}
		else
			_e("Менеджер не назначен","usam");
	}	
	
	function get_sortable_columns() 
	{
		$sortable = array(			
			'manager'    => array('manager_id', false),		
			'date'       => array('date_insert', false)
			);
		return $sortable;
	}
		
	function get_columns(){
        $columns = array(   
			'cb'            => '<input type="checkbox" />',						
			'messages'      => __('Последнее сообщение', 'usam'),		
			'customer'      => __('Клиент', 'usam'),							
			'manager'       => __('Менеджер', 'usam')		
        );
        return $columns;
    }
	
	public function single_row( $item ) 
	{
		static $row_class = '';
		$row_class = ( isset($_REQUEST['sel']) && $item->id == $_REQUEST['sel'] ? 'current_sel' : '' );
		$new_message_class = ( !empty($item->not_read) ? 'new_message' : '' );		
		$new_dialog_class = ( $item->manager_id != 0 ? 'new_dialog' : '' );		
		
		echo "<tr class ='row $row_class $new_message_class $new_dialog_class' dialog_id='$item->id'>";
		$this->single_row_columns( $item );
		echo '</tr>';
	}	
	
	function prepare_items() 
	{						
		$this->get_query_vars();
		$this->query_vars['cache_contacts'] = true;
		$this->query_vars['add_fields'] = ['end_message'];
				
		if ( empty($this->query_vars['include']) )
		{		
			if ( $this->status == 'unanswered' )
				$this->query_vars['unanswered'] = true;	
			
			$selected = $this->get_filter_value( 'users' );
			if ( $selected ) 
				$this->query_vars['contacts_id'] = array_map('intval', (array)$selected);
			
			$selected = $this->get_filter_value( 'chat_channel' );
			if ( $selected ) 
				$this->query_vars['channel'] = array_map('sanitize_title', (array)$selected);			
		} 		
		$dialogs = new USAM_Chat_Dialogs_Query( $this->query_vars );	
		$this->items = $dialogs->get_results();
		
		if ( $this->per_page )
		{
			$this->total_items = $dialogs->get_total();			
			$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
		}	
	}
}