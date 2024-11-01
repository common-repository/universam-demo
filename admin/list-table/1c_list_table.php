<?php
require_once( USAM_FILE_PATH . '/includes/technical/system_reports_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php');
class USAM_List_Table_1c extends USAM_List_Table 
{	
	protected $date_column = 'end_date';
	function column_name( $item )
	{	
		if ( $item->type == '1c-catalog' )
			_e('Обновление каталога', 'usam');	
		elseif ( $item->type == '1c-sale' )
			_e('Обновление заказов', 'usam');	
	}
	
	protected function get_filter_tablenav( ) 
	{				
		return ['interval' => ''];
	}

	function column_filename( $item )
	{	
		if( $item->filename )
		{
			$info = pathinfo($item->filename);			
			$newfilepath = USAM_EXCHANGE_DIR .'archive/'.$info['filename'].$item->id.'.'.$info['extension'];
			if ( file_exists($newfilepath) )
				echo "<a href='". add_query_arg(['system_report' => $item->id])."' target='_blank'>$item->filename</a>";
			else
				echo $item->filename;
		}
	}	
		
	function column_status( $item )
	{	
		if ( $item->status == 'started' )
			echo "<span class='item_status_notcomplete item_status'>".__('Работает', 'usam')."</span>";
		elseif( $item->status == 'error' )
			echo "<span class='item_status_attention item_status'>".__('Завершено с ошибкой', 'usam')."</span>";	
		else
			echo "<span class='item_status_valid item_status'>".__('Завершено', 'usam')."</span>";	
	}
	
	function column_results( $item ) 
    {		
		echo number_format($item->add, 0, '', ' ').' / '. number_format($item->update, 0, '', ' ');
	}
	
	function column_start_date( $item ) 
    {			
		if ( $item->start_date && $item->start_date != '0000-00-00 00:00:00' )
			return usam_local_date( $item->start_date, get_option('date_format', 'Y/m/d')." H:i" );
	}
	
	function column_end_date( $item ) 
    {	
		if ( !empty($item->end_date) && strtotime($item->end_date) > 0)
		{ 
			echo "<div>".human_time_diff( strtotime($item->start_date), strtotime($item->end_date) )."</div>";			
		}
	}
		   
	function get_sortable_columns()
	{
		$sortable = [
			'name'  => ['type', false],		
			'status'  => ['status', false],		
			'operation'  => ['operation', false],
			'start_date'  => ['start_date', false],
			'end_date'  => ['end_date', false],
			'filename'  => ['filename', false],			
		];
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = [			
			'name'      => __('Событие', 'usam'),
			'operation' => __('Операция', 'usam'),			
			'status'    => __('Статус', 'usam'),		
			'user'    => __('Пользователь', 'usam'),	
			'start_date' => __('Последний запуск', 'usam'),
			'end_date'   => __('Время работы', 'usam'),	
			'description' => __('Описание', 'usam'),	
			'results'    => __('Добавлено / Обновлено', 'usam'),
			'filename'  => __('Название файла', 'usam'),					
        ];		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$this->get_query_vars();						
		if ( empty($this->query_vars['include']) )
		{		
			
		}
		$query = new USAM_System_Reports_Query( $this->query_vars );
		$this->items = $query->get_results();					
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
		}		
	}
}