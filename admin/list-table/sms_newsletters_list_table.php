<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/newsletter_table.php' );
class USAM_List_Table_sms_newsletters extends USAM_Table_Newsletter
{	
	function column_subject( $item ) 
    { 
		$actions = $this->standart_row_actions( $item->id, 'sms_newsletter', ['copy' => __('Копировать', 'usam')] );
		if( $item->status == 5 || $item->status == 6 )
		{	
			$name = $this->item_view($item->id, $item->subject, 'sms_newsletter');
			$actions = array('view' => '<a class="usam-view-link" href="'.add_query_arg( array('form_name' => 'sms_newsletter', 'form' => 'view', 'id' => $item->id), $this->url).'">'.__('Отчет', 'usam').'</a>') + $actions;
			unset($actions['edit']);
		}	
		else
			$name = $this->item_edit($item->id, $item->subject, 'sms_newsletter');		
		$this->row_actions_table( $name, $actions );	
    }	
	
	function column_opened( $item ) 
    { 
		if ( $item->status == 5 || $item->status == 6 || $item->status == 4 )
		{
			if ( $item->count_subscribers > 0 )
				$p = " (".round($item->number_sent*100/$item->count_subscribers,2)."%)";
			else
				$p = '';			
			echo "<span class ='stat'>".sprintf( __('Отправлено %s из %s', 'usam'), $item->number_sent, $item->count_subscribers ).$p."</span>";
		}
	}
	
	function column_body( $item ) 
    { 
		echo (string)usam_get_newsletter_metadata( $item->id, 'body' );
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'          => '<input type="checkbox" />',
			'action'      => '<span class="usam-dashicons-icon" title="'.esc_attr__('Действия' ).'"></span>',
			'subject'     => __('Название рассылки', 'usam'),
			'body'        => __('Сообщение', 'usam'),
			'status'      => __('Статус', 'usam'),			
			'opened'      => __('Отправлено', 'usam'),			
			'lists'       => __('Списки рассылок', 'usam'),
			'date'        => __('Дата создания', 'usam'),				
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{		
		$this->get_newsletter_query_vars();
		$this->query_vars['type'] = 'sms';
		$this->query_vars['class'] = 'simple';
		$query_orders = new USAM_Newsletters_Query( $this->query_vars );
		$this->items = $query_orders->get_results();
		if ( $this->per_page )
		{
			$total_items = $query_orders->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}
	}
}
?>