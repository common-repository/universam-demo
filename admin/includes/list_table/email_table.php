<?php 
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Table_Email extends USAM_List_Table 
{
	public $orderby = 'id';			

	function __construct( $args = array() )
	{
		global $email_id, $email_folder, $mailbox_id;
						
		$email_folder = !empty($_REQUEST['f']) ? sanitize_title($_REQUEST['f']):'inbox';
		$email_id = !empty($_REQUEST['email_id']) ? absint($_REQUEST['email_id']):0;
		
		if ( !empty($_REQUEST['m']) )
			$mailbox_id = sanitize_title($_REQUEST['m']);
		else
			$mailbox_id = 0;
		parent::__construct( $args );	
	}
	
	public function return_post()
	{
		return array( 'm', 'f' );
	}	
	
	function column_title( $item ) 
    {									
		$actions = array();
		if ( $item->type == 'inbox_letter' )
			$text = !empty($item->from_name)?$item->from_name:$item->from_email;
		else					
			$text = !empty($item->to_name)?$item->to_name:$item->to_email;
				
		$title = !empty($item->title)?$item->title:usam_limit_words(strip_tags($item->body),50);		
		if( !wp_is_mobile() )			
			$actions['view'] = '<a class="usam-view-link" href="'.add_query_arg( array('form' => 'view', 'form_name' => 'email', 'id' => $item->id, 'm' => $item->mailbox_id), $this->url ).'">'.__('Посмотреть', 'usam').'</a>';
		$text .= "<hr size='1' width='90%'>$title";	
		$this->row_actions_table( $text, $actions );
	}	
	
	protected function column_cb( $item ) 
	{				
		$checked = in_array($item->id, $this->records )?"checked='checked'":""; 
		echo "<input id='checkbox-".$item->id."' type='checkbox' name='cb[]' value='".$item->id."' ".$checked."/>";	
	
		echo '<div class="icons">';
		if ( $item->folder == 'outbox' )			
			echo '<span class="dashicons dashicons-lightbulb" title="'.__('Не отправлено', 'usam').'"></span>';
		else
		{
			if ( $item->type == 'sent_letter' )			
				echo '<span class="dashicons dashicons-undo" title="'.__('Отправлено', 'usam').'"></span>';
			else	
				echo '<span class="dashicons dashicons-redo" title="'.__('Входящие', 'usam').'"></span>';			
		}
		echo '</div>';
    }		
	
	function column_date( $item ) 
    {						
		if ( $item->type == 'inbox_letter' )
			$date = $item->date_insert;
		else
		{
			if ( empty($item->sent_at) )
				$date = $item->date_insert;
			else
				$date = $item->sent_at;
		}
		echo "<div class='date_email'>".usam_local_date( $date, 'd.m.y H:i' )."</div>";
		if ( $item->type == 'sent_letter' )
		{
			echo '<hr size="1" width="90%">';
			$opened_at = usam_get_email_metadata( $item->id, 'opened_at' );
			if ( $opened_at )
				echo "<strong>".usam_local_date( $opened_at, 'd.m.y H:i' )."</strong>";
			else
				_e( 'Не открыли', 'usam');
		}			
		if ( isset($_GET['page']) && $_GET['page'] == 'feedback' )	
		{
			$email = $item->folder=='inbox'?$item->from_email:$item->to_email;
			echo '<span email="'.$email.'" class="dashicons dashicons-search js-search-email"></span>';
		}
		if ( $item->importance )			
			echo '<span class="dashicons dashicons-star-filled importance important"></span>';
		else
			echo '<span class="dashicons dashicons-star-empty importance"></span>';
		
		$attachment = usam_get_email_attachments($item->id);
		if ( count($attachment) > 0 )
			echo '<span class="dashicons dashicons-paperclip"></span>';
	}
	
	function column_mailbox( $item ) 
    {		
		$mailbox = usam_get_mailbox($item->mailbox_id);  
		echo $mailbox['email'];
	}
	
    function get_bulk_actions_display() 
	{		
		$actions = array(
			'delete'       => __('Удалить', 'usam'),
			'read'         => __('Прочитано', 'usam'),
			'not_read'      => __('Не прочитано', 'usam'),
			'important'    => __('Важное', 'usam'),
			'not_important' => __('Не важное', 'usam'),		
		);
		return $actions;
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(			
			'title'    => array('title', false),				
			'date'     => array('date', false),
			'size'     => array('size', false),
		);
		return $sortable;
	}
	
	function get_columns()
	{	
		$columns = array(   
			'cb'          => '<input type="checkbox" />',	
			'title'       => __('Тема', 'usam'),	
			'date'        => __('Дата', 'usam')		
        );	
        return $columns;
    }
		
	public function single_row( $item ) 
	{		
		global $email_id;
	
		if ( $item->read || $item->type == 'sent_letter' )
			$message_unread = "";
		else
			$message_unread = "message_unread";	
		
		if ( $item->id == $email_id )
			$message_current = "message_current";
		else
			$message_current = "";
		
		echo "<tr data-id = '$item->id' class = '$message_current $message_unread'>";
		$this->single_row_columns( $item );
		echo '</tr>';
	}	
	
	function get_vars_query_filter()
	{
		$selected = $this->get_filter_value( 'read' );
		if ( $selected ) 
			$this->query_vars['read'] = $selected == 'read' ? 1 : 0;
		
		$selected = $this->get_filter_value( 'importance' );
		if ( $selected ) 
			$this->query_vars['importance'] = $selected == 'importance' ? 1 : 0;		
		
		$selected = $this->get_filter_value('contacts');
		if ( $selected )
			$this->query_vars['contacts'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value('companies');
		if ( $selected )
			$this->query_vars['companies'] = array_map('intval', (array)$selected);
		
		$this->get_digital_interval_for_query( array('size' ) );
	}
			
	function prepare_items() 
	{		
		global $email_id;
			
		$this->get_query_vars();
		$this->query_vars['cache_results'] = true;
		$this->query_vars['cache_attachments'] = true;
		$this->query_vars['cache_object'] = true;
		$this->query_vars['folder_not_in'] = 'deleted';		
		if ( $this->id )
		{ 
			if ( isset($_REQUEST['form_name']) )
			{ 
				if ( $_REQUEST['form_name'] == 'company' )
				{
					$this->query_vars['emails'] = usam_get_company_emails( $this->id, true );			
					if ( empty($this->query_vars['emails']) )
						return;
				}
				elseif ( $_REQUEST['form_name'] == 'contact' )
				{ 
					$this->query_vars['emails'] = usam_get_contact_emails( $this->id );
					if ( empty($this->query_vars['emails']) )
						return;
				}		
			}
		}			
		if ( empty($this->query_vars['include']) )
		{
			$this->get_vars_query_filter();
		} 
		$query_emails = new USAM_Email_Query( $this->query_vars );
		$this->items = $query_emails->get_results();	
		if ( !empty($_REQUEST['email_id']) )
			$email_id = absint($_REQUEST['email_id']);	
		elseif ( isset($this->items[0]) )		
			$email_id = $this->items[0]->id;	
		if ( $this->per_page )
		{
			$this->total_items = $query_emails->get_total();			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}					
	}
} 