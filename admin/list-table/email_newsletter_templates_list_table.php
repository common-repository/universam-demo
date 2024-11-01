<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/newsletter_table.php' );
class USAM_List_Table_email_newsletter_templates extends USAM_Table_Newsletter
{
	public function get_default_primary_column_name() {
		return 'subject';
	}
	
	function column_subject( $item ) 
    {		
		$actions = $this->standart_row_actions( $item->id, 'email_newsletter', ['copy' => __('Копировать', 'usam')] );
		if( $item->status == 5 || $item->status == 4 || $item->status == 6  )
		{
			$name = $this->item_view($item->id, $item->subject, 'email_newsletter');
			$actions = ['view' => '<a class="usam-view-link" href="'.add_query_arg(['form_name' => 'email_newsletter', 'form' => 'view', 'id' => $item->id], $this->url).'">'.__('Отчет', 'usam').'</a>'] + $actions;
		//	$actions['ya'] = '<a class="usam-view-link" href="'.add_query_arg( array('page' => 'newsletter','tab' => 'email_newsletters', 'form_name' => 'ya', 'form' => 'view', 'id' => $item->id), admin_url('admin.php')).'">'.__('Яндекс', 'usam').'</a>';			
		}
		else
			$name = $this->item_edit($item->id, $item->subject, 'email_newsletter');	
		if ( $item->class != 'trigger' && $item->repeat_days == '' && $item->status == 6 )
			unset($actions['edit']);
		$this->row_actions_table( $name, $actions );	
    }	
	
	function get_columns()
	{
        $columns = [
			'cb'          => '<input type="checkbox" />',
			'action'      => '<span class="usam-dashicons-icon" title="' . esc_attr__('Действия' ) . '"></span>',
			'subject'     => __('Название рассылки', 'usam'),
			'status'      => __('Статус', 'usam'),
			'opened'      => __('Открыли, нажали, отписались', 'usam'),
			'date'        => __('Дата создания', 'usam'),			
        ];		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$this->get_newsletter_query_vars();
		$this->query_vars['type'] = 'mail';
		$this->query_vars['class'] = 'template';
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