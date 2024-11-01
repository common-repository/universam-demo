<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_ftp extends USAM_List_Table 
{		
	function no_items() 
	{
		_e( 'Нет файлов обмена', 'usam');
	}		
		
	function column_name( $item ) 
    {
		if ( $item['type'] == 'file' )
			$this->row_actions_table( $item['name'], $this->standart_row_actions( $item['name'], 'ftp_file' ) );
		elseif ( $item['type'] == '...' )
		{
			$this->row_actions_table( "<a href='".remove_query_arg( array('f'), $this->url )."'>".$item['name']."</a>", array() );
		}
		else
			$this->row_actions_table( "<a href='".add_query_arg( array('f' => $item['name']), $this->url )."'>".$item['name']."</a>", array() );
    }
   
	function get_sortable_columns() 
	{
		$sortable = array(
			'name'   => array('name', false),
			'date'  => array('date', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   
			'cb'           => '<input type="checkbox" />',			
			'name'         => __('Название файла', 'usam'),	
			'date'         => __('Дата', 'usam'),			
        );
        return $columns;
    }
	
	function prepare_items() 
	{					
		$num = 0;	
		$folder = USAM_EXCHANGE_DIR;
		if ( !empty($_GET['f']) )
		{
			$f = sanitize_title($_GET['f']);
			$this->items[] = array( 'id' => '', 'name' => '...', 'type' => '...', 'date_insert' => '' );		
			$this->url = add_query_arg( array('f' => $f), $this->url );
			$folder .= $f.'/';
		}
		$usam_files_report_list = usam_list_dir( $folder );	
		foreach ( $usam_files_report_list as $file ) 
		{	
			$item['name'] = $file;		
			$num++;
			$item['id'] = $num;
			if ( is_file($folder.$file) )
				$item['type'] = 'file';
			else			
				$item['type'] = 'folder';
			
			$item['date_insert'] = date("d-m-Y H:i", filectime($folder.$file) );
			if ( $this->search == '' || stripos($item['name'], $this->search) !== false )
			{							
				$this->items[] = $item;					
			}
		}		
		$this->total_items = count($this->items);
		$this->forming_tables();	
	}
}