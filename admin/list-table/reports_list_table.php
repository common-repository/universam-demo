<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_Reports extends USAM_List_Table 
{		
	function no_items() 
	{
		_e( 'Нет отчетов' );
	}		
	
	function column_name( $item ) 
    {				
		$url = add_query_arg( array( 'table' => $item['code'] ) );
		echo "<a href='".$url."'>".$item['name']."</a>";	
    }
   
	function get_sortable_columns() 
	{
		$sortable = array(
			'name'   => array('name', false),
			'date'  => array('date', false),			
			);
		return $sortable;
	}
	
	function column_date( $item ) 
	{ 
		echo $item['date'];
    }
		
	function get_columns()
	{
        $columns = array(   
			'cb'           => '<input type="checkbox" />',			
			'name'         => __('Название отчёта', 'usam'),	
			'description'  => __('Описание', 'usam'),			
			'date'         => __('Дата отчета', 'usam'),			
        );
        return $columns;
    }
	
	function prepare_items() 
	{			
		$num = 0;
		$report_directory     = dirname(dirname(__FILE__)) .'/finished_reports/';		
		$usam_files_report_list = usam_list_dir( $report_directory );	
		foreach ( $usam_files_report_list as $file ) 
		{
			if ( stristr( $file, '.php' ) ) 	
			{
				require( $report_directory . $file );			
				$item = get_file_data( $report_directory . $file, ['ver'=>'Version', 'author'=>'Author', 'date'=>'Date', 'description'=>'Description', 'name'=>'Name']);		
				$item['code'] = $bodytag = str_replace( ".php", "", $file );		
				$num++;
				$item['id'] = $num;	
				if ( $this->search == '' || stripos($item['name'], $this->search) !== false || stripos($item['description'], $this->search) !== false )
				{							
					$this->items[] = $item;					
				}
			}
		}		
		$this->total_items = count($this->items);
		$this->forming_tables();	
	}
}