<?php
/**
 * Экспорт 
 */
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
abstract class USAM_Exporter
{	
	protected $rule;
	protected $properties;
	protected $paged = 1;	
	protected $offset = 0;
	protected $number = 50000;
	protected $columns = [];
	
	public function __construct( $id ) 
	{			
		if ( $id )
		{
			$this->rule = usam_get_exchange_rule( $id );
			$metas = usam_get_exchange_rule_metadata( $id );
			foreach($metas as $metadata )
				$this->rule[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
		}
	}
	
	public function get_args( ) 
	{
		return [];	
	}
	
	public function get_total( ) 
	{
		return 0;
	}
	
	protected function export_file( ) 
	{		
		$output = $this->start( );
		$filename = !empty($this->rule['name'])?$this->rule['name']:'export';
		if ( !empty($this->rule['split_into_files']) && isset($this->rule['columns'][$this->rule['split_into_files']]) )
		{
			usam_download_file( $output );
			unlink( $output );
		}
		else
		{
			if ( $this->rule['type_file'] == 'exel' )
				header('Content-Type: application/vnd.ms-excel');
			else
				header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment;filename="'.$filename.'.'.usam_get_type_file_exchange( $this->rule['type_file'], 'ext' ).'"');
			header('Cache-Control: max-age=0');	
			echo $output;
		}
		exit;
	}
	
	public function start( $args = [] ) 
	{			
		if ( empty($this->rule) ) 
			return false;			
		
		set_time_limit(1800);
		$results = [];
		do
		{			
			$output = $this->get_data( $args );
			foreach($output as $key => $row)
			{
				$delete = true;
				foreach($row as $k => $value)
				{
					if ( $value !== '' && $value !== false )
					{
						$delete = false;
						break;
					}
				}				
				if ( $delete )
					unset($output[$key]);
			}
			$this->paged++;
			$results = array_merge( $results, $output );	
		} 
		while ( count($output) > 0 && !$args );
		return $this->get_export_file( $results );
	}
	
	protected function get_exel_args( ) 
	{
		$columns = $this->get_name_columns();		
		return ['headers' => $columns, 'list_title' => __("Данные","usam")];
	}
	
	protected function get_export_file( $data ) 
	{		
		$filename = !empty($this->rule['name'])?$this->rule['name']:'export';		
		if ( $this->rule['type_file'] == 'exel' )
		{				
			$exel_args = $this->get_exel_args();
			if ( !empty($this->rule['split_into_files']) && isset($this->rule['columns'][$this->rule['split_into_files']]) )
			{  
				$zip = new ZipArchive();
				$file_path = USAM_FILE_DIR.$filename.".zip";				
				if ( $zip->open($file_path, ZIPARCHIVE::CREATE) === true ) 
				{
					do 
					{
						$current_values = current($data);
						$column = $current_values[$this->rule['split_into_files']];
						$r = [];
						foreach($data as $key => $values)
						{
							if ( $values[$this->rule['split_into_files']] == $column )
							{
								$r[] = $values;
								unset($data[$key]);
							}
						}						
						$writer = usam_write_exel_file( $r, $exel_args);
						ob_start();	
						$writer->save('php://output');
						$output = ob_get_clean();			
						$zip->addFromString($filename.' - '.$column.'.xlsx', $output);
					}
					while ( count($data) );
					$zip->close();		
					$output = $file_path;					
				}			
			}
			else
			{
				$writer = usam_write_exel_file( $data, $exel_args );			
				ob_start();	
				$writer->save('php://output');
				$output = ob_get_clean();
			}
		}
		else		
		{
			if ( !empty($this->rule['split_into_files']) && isset($this->rule['columns'][$this->rule['split_into_files']]) )
			{
				$zip = new ZipArchive();
				$file_path = USAM_FILE_DIR.$filename.".zip";
				if ( $zip->open($file_path, ZIPARCHIVE::CREATE) === true ) 
				{
					do 
					{
						$current_values = current($data);
						$column = $current_values[$this->rule['split_into_files']];
						$r = [];
						foreach($data as $key => $values)
						{
							if ( $values[$this->rule['split_into_files']] == $column )
							{
								$r[] = $values;
								unset($data[$key]);
							}
						}
						$output = $this->get_file_content( $r );					
						$zip->addFromString($filename.' - '.$column.'.'.usam_get_type_file_exchange($this->rule['type_file'], 'ext'), $output);
					}
					while ( count($data) );
					$zip->close();	
					$output = $file_path;
				}			
			}
			else
				$output = $this->get_file_content( $data );	
		}
		return $output;
	}
	
	function get_name_columns( ) 
	{	
		$names = [];
		$this->columns = $this->rule['columns'];		
		if ( !empty($this->rule['split_into_files']) && isset($this->columns[$this->rule['split_into_files']]) )
			unset($this->columns[$this->rule['split_into_files']]);	
		if ( isset($this->rule['headings']) )
		{	
			if ( $this->rule['headings'] == 1 )
				$names = array_keys($this->columns);
			if ( $this->rule['headings'] == 2 )
			{
				$function = 'usam_get_columns_'.$this->rule['type'];				
				if ( function_exists($function) )
				{
					$columns = $function();					
					foreach($this->columns as $key => $column)
					{
						if ( isset($columns[$column]) )
							$names[$key] = $columns[$key];
						else
							$names[$key] = $column;
					}
				}		
			}
		}		
		return $names;
	}
	
		//Запускает сохранение данных в файл
	public function write_data( $paged ) 
	{
		$this->paged = $paged;
		$output = $this->get_data();
		$this->write_file( $output );		
		return count($output);
	}	
	
	function write_file( $data ) 
	{	
		if ( $this->paged == 1 )
			$this->delete_file();
		
		$local_file = USAM_UPLOAD_DIR.'exchange/exporter_'.$this->rule['id'].'.'.usam_get_type_file_exchange( $this->rule['type_file'], 'ext' );
		if ( $this->rule['type_file'] == 'exel' )
		{ //array_unshift 
			$exel_args = $this->get_exel_args();
			$exel_args['load_file'] = $local_file;
			$writer = usam_write_exel_file( $data, $exel_args );
			$writer->save( $local_file );	
		}
		else
		{						
			$output = $this->get_file_content( $data );		
			if (!$handle = fopen($local_file, 'a')) 
				return false;		

			if (fwrite($handle, $output) === FALSE) 
				return false;	
			fclose($handle);
		}		
	}
	
	function get_file_content( $data ) 
	{
		$delimiter = usam_get_type_file_exchange( $this->rule['type_file'], 'delimiter' );		
		$rows = [];		
		$columns = $this->get_name_columns();		
		if ( $columns )
			$rows[] = implode($delimiter, $columns);		
		foreach($data as $row)
		{		
			$values = [];				
			foreach($this->columns as $column => $value)
			{						
				if ( isset($row[$column]) )
					$values[] = $row[$column];		
			}
			$rows[] = implode( $delimiter, $values );		
		}	
		if ( stripos($delimiter, '"') !== false )
			$output = '"'.implode( "\"\n\"", $rows ).'"';
		else
			$output = implode( "\n", $rows );
		if ( $this->rule['encoding'] && $this->rule['encoding'] != 'utf-8' )
			$output = iconv ('utf-8', $this->rule['encoding'], $output);		
		return $output;
	}
	
	function delete_file( ) 
	{	
		$local_file = USAM_UPLOAD_DIR.'exchange/exporter_'.$this->rule['id'].'.'.usam_get_type_file_exchange( $this->rule['type_file'], 'ext' );
		if ( file_exists($local_file)) 
			unlink($local_file);
	}
}
?>