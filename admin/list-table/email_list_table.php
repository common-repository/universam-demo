<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/email_table.php' );
class USAM_List_Table_email extends USAM_Table_Email 
{
	public $orderby = 'date_insert';
	public function return_post()
	{
		return array( 'm', 'f' );
	}	
		
	function column_name( $item ) 
    {					
		if ( $item->type == 'inbox_letter' )
			$text = !empty($item->from_name)?$item->from_name:$item->from_email;
		else					
			$text = !empty($item->to_name)?$item->to_name:$item->to_email;
		
		$objects = usam_get_email_objects( $item->id );
		if ( $objects )
		{
			foreach ( $objects as $object ) 
			{
				switch ( $object->object_type ) 
				{
					case 'order' :
						if ( !empty($order) )
							$text .= '<br><a class="object_link" href="'.usam_get_url_order($object->object_id ).'" target="blank">'.__('Заказ', 'usam').' - №'.$object->object_id.'</a>';						
					break 2;
				}
			}			
		} 		
		if ( empty($item->title) && !empty($item->body) ) 
			$title = usam_limit_words(trim(preg_replace('!\s++!u',' ',strip_tags($item->body)),"&nbsp;"),80);
		else
			$title = $item->title;	

		$actions = array();	
		if( !wp_is_mobile() )
		{				
			$actions['view'] = '<a class="usam-zoom-link" href="">'.__('Посмотреть', 'usam').'</a>';				
			if ( $item->folder == 'drafts' )
				$new_actions['send'] = __('Отправить', 'usam');
			else
				$new_actions['spam'] = __('Спам', 'usam');
			$new_actions['delete'] = __('Удалить', 'usam');
			$actions += $this->standart_row_actions( $item->id, 'email', $new_actions );	
			if ( $item->folder != 'drafts' ) 
				unset($actions['edit']);
		}
		$text .= '<hr size="1" width="90%">'.$title;			
		$this->row_actions_table( $text, $actions );	
	}	
	
	protected function column_cb( $item ) 
	{				
		$checked = in_array($item->id, $this->records )?"checked='checked'":""; 
		echo "<input id='checkbox-".$item->id."' type='checkbox' name='cb[]' value='".$item->id."' ".$checked."/>";	
    }	
		
	public function extra_tablenav_display( $which ) 
	{
		static $pagination_args;
		global $email_folder, $mailbox_id;
		if ( 'top' == $which ) 
		{	
			$pagination_args = $this->_pagination_args; 
			$this->_pagination_args = array(); 
			if ( $email_folder == 'deleted' )
			{
				$url =  wp_nonce_url( add_query_arg( array( 'action' => 'clear', 'm' => $mailbox_id ), $this->url ));
				echo "<a href='$url' class='button secondary clear'>".__('Очистить','usam')."</a>"; 
			}
			?> 
			<select id="set_folder">
				<option value=""><?php _e('В папку', 'usam'); ?></option>		
				<?php 	
				$email_folders = usam_get_email_folders( array( 'mailbox_id' => $mailbox_id ) );					
				$folders = array();
				foreach ( $email_folders as $folder ) 
				{
					if ( $email_folder != $folder->slug )
						$folders[$folder->slug] = $folder->name;								
				}				
				foreach( $folders as $key => $folder_name )
				{	
					?> 
					<option value="<?php echo $key; ?>"><?php echo $folder_name; ?></option>								
					<?php 	
				}				
				?>
			</select>
			<?php 			
		}	
		else
		{
			$this->_pagination_args = $pagination_args;
		}
	}
		
    function get_bulk_actions_display() 
	{
		global $email_folder;	
		$actions = array(
			'delete'       => __('Удалить', 'usam'),
			'read'         => __('Прочитано', 'usam'),
			'not_read'      => __('Не прочитано', 'usam'),
			'important'    => __('Важное', 'usam'),
			'not_important' => __('Не важное', 'usam'),		
		);
		if ( $email_folder == 'inbox' )
			$actions['add_contact'] = __('Добавить контакт', 'usam');
		return $actions;
	}
		
	function get_columns()
	{	
		$columns = array(   
			'cb'            => '<input type="checkbox" />',	
			'name'          => '',
			'date'			=> __('Дата', 'usam')
        );				
        return $columns;
    }
				
	function prepare_items() 
	{			
		global $email_folder, $mailbox_id, $email_id;	
	
		$this->get_query_vars();		
		$this->query_vars['cache_results'] = true;
		$this->query_vars['cache_attachments'] = true;
		$this->query_vars['cache_object'] = true;
		$this->query_vars['object_query'] = [];
		if ( empty($this->query_vars['include']) )
		{
			$this->get_vars_query_filter();			
		}	
		if ( !empty($_REQUEST['company']) )
			$this->query_vars['object_query'][] = ['object_type' => 'company', 'object_id' => absint($_REQUEST['company'])];	
		elseif ( !empty($_REQUEST['contact']) )
			$this->query_vars['object_query'][] = ['object_type' => 'contact', 'object_id' => absint($_REQUEST['contact'])];	
		$this->query_vars['folder'] = $email_folder;		
		$this->query_vars['mailbox'] = $mailbox_id;		
		
		$query_emails = new USAM_Email_Query( $this->query_vars );		
		$this->items = $query_emails->get_results();
		if ( !empty($_REQUEST['email_id']) )
			$email_id = absint($_REQUEST['email_id']);	
		elseif ( isset($this->items[0]) )		
			$email_id = $this->items[0]->id;
		
		if ( $this->per_page )
		{
			$this->total_items = $query_emails->get_total();			
			$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
		}	
	}
}