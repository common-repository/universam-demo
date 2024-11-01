<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/sms_table.php' );
class USAM_List_Table_sms extends USAM_Table_SMS 
{			
	function get_columns()
	{	
		$columns = array(   
			'cb'            => '<input type="checkbox" />',	
			'phone'         => __('Получатель', 'usam'),			
			'message'       => __('Сообщение', 'usam'),	
        );
		
		if ( $this->folder == 'sent' )
			$columns['date'] = __('Дата отправки', 'usam');		
		else
			$columns['date'] = __('Дата', 'usam');	
	
        return $columns;
    }	
			
	function prepare_items() 
	{			
		global $email_id;
				
		$this->get_query_vars();
		$this->query_vars['cache_results'] = true;	
		$this->query_vars['folder'] = $this->folder;		
		if ( empty($this->query_vars['include']) )
		{
		
		}				
		$query = new USAM_SMS_Query( $this->query_vars );
		$this->items = $query->get_results();
		
		if ( !empty($_REQUEST['email_id']) )
			$email_id = absint($_REQUEST['email_id']);	
		elseif ( isset($this->items[0]) )		
			$email_id = $this->items[0]->id;
		
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}