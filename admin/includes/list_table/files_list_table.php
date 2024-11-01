<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Table_files extends USAM_List_Table
{		
	function get_bulk_actions_display() 
	{
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}
	
	function column_filename( $item )
	{	
		if ( isset($_POST['usam_ajax_action']) )					
			$img = '<div class="file_icon"><img src="'.usam_get_file_icon( $item->id ).'"></div>';
		 else
			$img = '<div class="file_icon"><img src="'.usam_get_file_icon( $item->id ).'" loading="lazy"></div>';	
		$title = "<div class='file'>$img<div class='file_data'><div class='file_title'>$item->title</div><div class='file_size'>".size_format( $item->size )."</div></div></div>";
		$this->row_actions_table( $title, $this->standart_row_actions( $item->id, 'file' ) );
	}
	
	function column_status( $item )
	{	
		if ( $item->status == 'closed' )
		{
			?><span class="status_blocked item_status"><?php echo usam_get_file_status_name( $item->status ); ?></span><?php
		}
		elseif ( $item->status == 'open' )
		{
			?><span class="item_status_valid item_status"><?php echo usam_get_file_status_name( $item->status ); ?></span><?php
		}
		elseif ( $item->status == 'limited' )
		{
			?><span class="item_status_notcomplete item_status"><?php echo usam_get_file_status_name( $item->status ); ?></span><?php
		}
		else
			echo usam_get_file_status_name( $item->status );
	}
	
	function column_folder( $item )
	{	
		if ( $item->folder_id )
		{
			$folder = usam_get_folder( $item->folder_id );
			echo isset($folder['name'])?$folder['name']:'';
		}
		else
			_e("Корневая папка","usam");
	}

	function column_object( $item )
	{	
		$object = new stdClass();
		$object->object_type = $item->type;
		$object->object_id = $item->object_id;
		$result = usam_get_object( $object );
		if ( $result )
		{
			echo "<span class='name'>".$result['name']."</span>";	
			echo "<div class='event_object_title'><a href='".$result['url']."'>".$result['title']."</a></div></div>";
		}
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(
			'id'       => array('id', false),
			'title'    => array('title', false),
			'size'     => array('size', false),
			'date'     => array('date', false),	
			'status'   => array('status', false),	
			'object'   => array('type', false),	
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   
			'cb'          => '<input type="checkbox" />',
			'filename'    => __('Название', 'usam'),	
			'folder'      => __('Папка', 'usam'),
			'object'      => __('Объект', 'usam'),		
			'status'      => __('Статус', 'usam'),	
			'date'        => __('Дата', 'usam'),		
        );		
        return $columns;
    }
	
	public function single_row( $item ) 
	{				
		echo '<tr class ="row file_status_'.$item->status.'" >';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	public function get_views() 
	{				
		$url = remove_query_arg( array('post_status', 'paged', 'action2', 'm',  'paged', 's', 'orderby','order','status') );	
		$views_query_vars = $this->get_views_query_vars();
		$views_query_vars['status'] = 'all';
		$results = usam_get_files( $views_query_vars );
		
		$statuses = array();		
		$total_count = 0;	
		if ( !empty( $results ) )
		{			
			foreach ( $results as $result )
			{				
				$statuses[$result->status] = $result->count;	
				if ( $result->status != 'delete' )
					$total_count += $result->count;	
			}
		} 
		$all_text = sprintf( _n('Все <span class="count">(%s)</span>', 'Все <span class="count">(%s)</span>', $total_count, 'usam'), number_format_i18n($total_count) );
		$all_class = $this->status == 'all' && $this->search == '' ? 'class="current"' : '';	
		$href = add_query_arg( 'status', 'all', $url );
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( $href ), $all_class, $all_text ), );
		foreach ( usam_get_statuses_files() as $key => $title )		
		{			
			$number = !empty($statuses[$key])?$statuses[$key]:0;
			if ( !$number )
				continue;
			$text = $text = sprintf( $title.' <span class="count">(%s)</span>', number_format_i18n( $number )	);
			$href = add_query_arg( 'status', $key, $url );
			$class = $this->status == (string)$key ? 'class="current"' : '';	
			$views[$key] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );			
		}		
		return $views;
	}
}