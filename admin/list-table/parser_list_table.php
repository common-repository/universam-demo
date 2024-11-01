<?php
require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_parser extends USAM_List_Table 
{	
	function get_bulk_actions_display() 
	{		
		$actions = [
			'update_products' => __('Обновить товары', 'usam'),
			'delete_products' => __('Удалить загруженные товары', 'usam'),
			'delete'          => __('Удалить', 'usam'),				
		];
		return $actions;
	}	
	
	function column_name( $item ) 
    {		
		$actions = $this->standart_row_actions( $item->id, 'parser_'.$item->site_type, ['download' => __('Загрузить товары', 'usam'), 'copy' => __('Копировать', 'usam')] );
		if ( usam_check_parsing_is_running( (array)$item ) )
			unset($actions['download']);			
		$this->row_actions_table( $this->item_edit($item->id, $item->name, 'parser_'.$item->site_type), $actions );	
	}
	
	function column_site_type( $item ) 
    {	
		if ( $item->site_type == 'competitor' )
			echo "<p>".__('Конкурент', 'usam')."</p>";
		elseif ( $item->site_type == 'supplier' )
			echo "<p>".__('Поставщик', 'usam')."</p>";			
		if ( $item->domain )
			echo "<div><a href='http://{$item->domain}' target='_black'>$item->domain</a></div>";
	}
	
	function column_type_import( $item ) 
    {	
		$type_import = usam_get_parsing_site_metadata( $item->id, 'type_import' );
		if ( $type_import == 'insert' )
			echo "<p>".__('Только создать', 'usam')."</p>";
		elseif ( $type_import == 'update' )
			echo "<p>".__('Только обновить', 'usam')."</p>";		
		else
			echo "<p>".__('Обновлять или создавать', 'usam')."</p>";
	}	
		
	function column_start_date( $item ) 
    {			
		if ( $item->start_date && $item->start_date != '0000-00-00 00:00:00' )
			return usam_local_formatted_date( $item->start_date );
	}
	
	function column_end_date( $item ) 
    {			
		if ( usam_check_parsing_is_running( (array)$item ) )
			echo "<div class='item_status_valid item_status'>".__('Работает', 'usam')."</div>";
		if ( !empty($item->end_date) && strtotime($item->end_date) > 0)
		{ 
			echo "<div>".human_time_diff( strtotime($item->start_date), strtotime($item->end_date) )."</div>";			
		}
	}
	
	function column_count_urls( $item )
    {
		?><a href='<?php echo admin_url("admin.php?page=exchange&tab=parser&table=parser_url&id=".$item->id); ?>'><?php echo number_format_i18n( (int)usam_get_parsing_site_metadata( $item->id, 'count_urls' ) ).' / '. number_format_i18n( (int)usam_get_parsing_site_metadata( $item->id, 'links_processed' ) );?></a><?php
	}
	
	function column_results( $item ) 
    {
		if ( $item->site_type == 'supplier' )
		{
			?><a href='<?php echo add_query_arg(['post_type' => 'usam-product', 'parsing_sites' => $item->id], admin_url('edit.php') ); ?>'><?php echo number_format_i18n( (int)usam_get_parsing_site_metadata( $item->id, 'products_added' ) ).' / '. number_format_i18n( (int)usam_get_parsing_site_metadata( $item->id, 'products_update' ) );?></a><?php
		}
		else
		{		
			?><a href='<?php echo add_query_arg(['page' => 'competitor_analysis', 'tab' => 'competitors_products', 'parsing_sites' => $item->id], admin_url('admin.php') ); ?>'><?php echo number_format_i18n( (int)usam_get_parsing_site_metadata( $item->id, 'products_added' ) ).' / '. number_format_i18n( (int)usam_get_parsing_site_metadata( $item->id, 'products_update' ) );?></a><?php
		}
	}
	
	function get_sortable_columns()
	{
		$sortable = [
			'name' => ['name', false],	
			'type_import' => ['type_import', false],	
			'site_type' => ['site_type', false],			
		];
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(  
			'cb'         => '<input type="checkbox" />',
			'name'       => __('Название сайта', 'usam'),	
			'active'     => __('Авто-запуск', 'usam'),
			'type_import' => __('Вариант импорта', 'usam'),
			'site_type'  => __('Тип', 'usam'),		
			'start_date' => __('Последний запуск', 'usam'),
			'end_date'   => __('Время работы', 'usam'),		
			'count_urls' => __('Найдено / Обработано', 'usam'),
			'results'    => __('Добавлено / Обновлено', 'usam'),
        ); 
        return $columns;
    }	
	
	function prepare_items() 
	{			
		$this->get_query_vars();						
		if ( empty($this->query_vars['include']) )
		{		
			
		}
		$query = new USAM_Parsing_Sites_Query( $this->query_vars );
		$this->items = $query->get_results();					
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
		}		
	}
}