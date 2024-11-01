<?php
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Export_Table extends USAM_List_Table
{		
	protected $rule_type = '';
	protected $events = [];	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}	
	
	protected function get_table_classes() {
		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$mode_class = esc_attr( 'table-view-' . $mode );

		return array( 'widefat', 'fixed', 'striped', $mode_class, $this->_args['plural'], 'exchange_table' );
	}
		
	function column_name( $item ) 
    {
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, $item->type, ['copy' => __('Копировать', 'usam')] ) );	
	}

	function get_results( $item ) 
    {
		$result_exchange = usam_get_exchange_rule_metadata( $item->id, 'result_exchange' );	
		$add = !empty($result_exchange['add']) ? number_format($result_exchange['add'], 0, '', ' ') : 0;
		$update = !empty($result_exchange['update']) ? number_format($result_exchange['update'], 0, '', ' ') : 0;
		
		return $add.' / '. $update;
	}
	
	function column_results( $item ) 
    {		
		echo $this->get_results( $item );
	}
	
	function column_start_date( $item ) 
    {
		if ( $item->start_date && $item->start_date != '0000-00-00 00:00:00' )
		{
			echo usam_local_formatted_date( $item->start_date );			
		}
	}
	
	function column_active( $item )
    {				
		$text = '';
		foreach ( $this->events as $id => $event ) 
		{		
			if ( 'exchange_'.$item->type."-".$item->id == $id)
			{
				$text = "<span class='item_status_valid item_status'>".__('Работает', 'usam')."</span>";
				break;
			}
			elseif ("load_data-".$item->id == $id )
			{
				$text = "<span class='item_status_notcomplete item_status'>".__('Загрузка данных', 'usam')."</span>";
				break;
			}
			elseif ( 'after_exchange_'.$item->type."-".$item->id == $id)
			{
				$text = "<span class='item_status_notcomplete item_status'>".__('Обработка после импорта', 'usam')."</span>";
				break;
			}
		}
		if ( $text == '' )
		{
			$status_process = usam_get_exchange_rule_metadata($item->id, 'process');
			if ( $status_process == 'column_setup_error' )
				$text = "<span class='item_status_attention item_status'>".__('Ошибка настройки колонок', 'usam')."</span>";	
			elseif ( $status_process == 'no_data' )
				$text = "<span class='item_status_attention item_status'>".__('Данные не найдены', 'usam')."</span>";	
			else
				$text = "<span class='status_blocked item_status'>".__('Не работает', 'usam')."</span>";	
		}
		echo $text;
	}
		
	function column_process( $item ) 
    {
		global $wpdb;
		$name_table = $item->type."_".$item->id;		
		$start = false;	
		foreach ( $this->events as $id => $event ) 
		{				
			if ( 'exchange_'.$item->type."-".$item->id == $id)
			{				
				$p = round($event['done']*100/$event['count'],0);					
				echo "<span class='item_status_valid item_status'>".sprintf( __('Выполнено %s', 'usam'), $p.'%')."</span>";
				$count = "<a href='".add_query_arg(['table' => 'import_data', 'n' => $item->id])."'>".number_format($event['count'], 0, '', ' ')."</a>";
				echo "<p class=''>".sprintf( __('%s из %s', 'usam'), number_format($event['done'], 0, '', ' '), $count )."</p>";
				$start = true;
				break;
			}
			elseif ("load_data-".$item->id == $id )
			{
				$start = true;
				if ( $wpdb->get_var("show tables like '".$name_table."'") != $name_table ) 
					_e('Загрузка файла', 'usam');
				else
				{
					$data = $wpdb->get_var("SELECT COUNT(*) FROM `{$name_table}`");
					$count = "<a href='".add_query_arg(['table' => 'import_data', 'n' => $item->id])."'>".number_format($data, 0, '', ' ')."</a>";
					printf( __('Загружено %s записей', 'usam'), $count );
				}
				break;
			}				
		}
		if ( !$start && !empty($item->end_date) && strtotime($item->end_date) > 0)
		{ 
			printf( __('Работал %s', 'usam'), human_time_diff( strtotime($item->start_date), strtotime($item->end_date) ) );			
		}		
	}
	
	function column_exchange_option( $item ) 
    {
		if ( $item->exchange_option == 'folder' )
		{
			$folder = usam_get_folder( $item->file_data );
			if ( $folder )
			{
				 ?><a href="<?php echo admin_url("admin.php?page=files&view=grid&folder=").$item->file_data; ?>"><?php printf( __('Из папки в библиотеке %s', 'usam'), $folder['name'] ); ?></a><?php
			}
			else
			{
				?><span class="item_status_attention item_status"><?php _e('Папка в библиотеке удалена', 'usam'); ?><span><?php
			}
		}
		else
		{
			$automations = usam_get_automations_exchange_rule();
			if ( isset($automations[$item->exchange_option]) )
				echo $automations[$item->exchange_option];
		}
	}
	
	function is_start( $item ) 
    {
		$start = false;
		foreach ( $this->events as $id => $event ) 
		{				
			if ( 'exchange_'.$item->type."-".$item->id == $id || "load_data-".$item->id == $id  || 'after_exchange_'.$item->type."-".$item->id == $id )
			{	
				$start = true;
				break;
			}
		}
		return $start;
	}
	
	function column_schedule( $item ) 
    {
		foreach ( wp_get_schedules() as $cron => $schedule ) 
		{										
			if ( $item->schedule == $cron )
			{
				echo "<span class='item_status_valid item_status'>".$schedule['display']."</span>";
				return;
			}
		}	
		if ( !$this->is_start( $item ) )
		{
			?>
			<div class='import_attachments usam_attachments' @click="fileAttach" @drop="fileDrop(<?php echo $item->id ?>, $event)" @dragover="allowDrop" v-cloak>
				<div class='usam_attachments__file' v-if="files[<?php echo $item->id ?>] != undefined">
					<a class='usam_attachments__file_delete delete' @click="fileDelete"></a>							
					<progress-circle :percent="files[<?php echo $item->id ?>].percent"></progress-circle>
					<div class='attachment__file_data'>
						<div class='filename'>{{files[<?php echo $item->id ?>].title}}</div>						
						<div v-if="files[<?php echo $item->id ?>].error" class='attachment__file_data__error'>{{files[<?php echo $item->id ?>].error_message}}</div>
						<div v-else class='attachment__file_data__filesize'>{{files[<?php echo $item->id ?>].size}}</div>
					</div>
				</div>
				<div class ='attachments__placeholder' v-else><?php esc_html_e( 'Перетащите или', 'usam'); ?><br><?php esc_html_e( 'нажмите', 'usam'); ?></div> 
				<input type='file' @change="fileChange(<?php echo $item->id ?>, $event)"/>	
			</div>
			<?php
		}
	}	
	
	function column_file( $item ) 
    {	
		global $wpdb;
		$name_table = $item->type."_".$item->id;		
		$start = false;
		foreach ( $this->events as $id => $event ) 
		{
			if ( 'exchange_'.$item->type."-".$item->id == $id)
			{				
				$p = round($event['done']*100/$event['count'],0);				
				echo "<span class='item_status_valid item_status'>".sprintf( __('Выполнено %s', 'usam'), $p.'%')."</span>";
				echo "<p class=''>".sprintf( __('%s из %s', 'usam'), number_format($event['done'], 0, '', ' '), number_format($event['count'], 0, '', ' ') )."</p>";
				$start = true;
				break;
			}			
		}
		if ( !$start )
		{ 
			$types_file = usam_get_types_file_exchange();
			echo "<a href='#' class='js-action-item' data-action='download' data-group='export' data-id='$item->id' title='".__('Скачать файл', 'usam')."'>".$item->name.'.'.$types_file[$item->type_file]['ext']."</a>";			
		}				
	}

	public function single_row( $item ) 
	{				
		echo '<tr class ="row exchange_rule" data-id = "'.$item->id.'">';		
		$this->single_row_columns( $item );
		echo '</tr>';
	}		
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'name'       => ['name', false],		
			'start_date' => ['start_date', false],	
			'active' =>  ['start_date', false],	
			'exchange_option' => ['exchange_option', false],
		);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',
			'name'       => __('Название правила', 'usam'),
			'file'       => __('Файл', 'usam'),			
        );					
        return $columns;
    }
	
	function prepare_items() 
	{		
		$this->get_query_vars();
		$this->events = usam_get_system_process();
	
		$this->query_vars['type'] = $this->rule_type;						
		$query = new USAM_Exchange_Rules_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}	
	}
}
?>