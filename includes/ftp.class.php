<?php
final class USAM_FTP
{
	private  $errors  = array();
	private $connect = false;
	private $settings_ftp;	
	
	public function __construct( )
	{	
		$this->settings_ftp = get_option( 'usam_ftp_settings', '' );		
	}
	
	public function get_errors( )
	{	
		return $this->errors;
	}		
	
	public function ftp_open()
	{		
		if ( $this->connect === false )
		{
			$result = false;				
			if ( empty($this->settings_ftp['host']) )
				$this->errors[] = __('Нет настроек для FTP','usam');
			else
			{					
				if ( empty($this->settings_ftp['timeout']) )
					$timeout = 30;
				else
					$timeout = $this->settings_ftp['timeout'];
				$timeout = 1;
				$connect = @ftp_connect( $this->settings_ftp['host'], $this->settings_ftp['port'], $timeout);
				if( !$connect )		
					$this->errors[] = __('Ошибка соединения c хостом','usam')." - ".$this->settings_ftp['host'];		
				else
				{	
					if ( @ftp_login( $connect, $this->settings_ftp['user'], $this->settings_ftp['pass'] ) ) 						
					{						
						set_time_limit(1800);
						$result	= true;							
						$this->connect = $connect;
						
						ftp_pasv($this->connect, (bool)$this->settings_ftp['mode']);		// Включает или выключает пассивный режим	
					}
					else						
						$this->errors[] = __('Не удается войти на сервер под именем','usam')." ".$this->settings_ftp['user'];	
				}			
			}
		}
		else
			$result	= true;	
		return $result;
	}
	
	public function ftp_close( )
	{
		if ( $this->connect !== false )
		{
			ftp_close($this->connect);
			$this->connect = false;
		}
	}	
	
	public function copy_files( $local_dir, $ftp_dir, $max = 200 )
	{
		if ( $this->connect !== false )
		{
			$local_dir = USAM_UPLOAD_DIR."exchange/{$local_dir}/";						
			if ( @ftp_chdir($this->connect, $ftp_dir ) )
			{
				$FILE_LIST = ftp_nlist($this->connect, ".");
				$count_file = !empty($FILE_LIST)?count($FILE_LIST):0;		
				if ( $count_file == 0 )
					return false; 
				elseif ( $count_file > $max )
					$count_file = $max;
					
				$mode = $this->settings_ftp['get_mode']=='FTP_ASCII'?FTP_ASCII:FTP_BINARY;
				for ($i = 0; $i < $count_file; $i++)
				{
					$ftp_get = @ftp_get($this->connect, $local_dir . $FILE_LIST[$i], $FILE_LIST[$i], $mode );
					if ( $ftp_get )
					{	
						@ftp_delete($this->connect, $FILE_LIST[$i]);// удаление файлов на ftp
					}
					else			
					{
						$this->errors[] = __("Ошибка копирования","usam" )." ".$local_dir . $FILE_LIST[$i];			
					}
				}	
				return $count_file; 				
			}	
			else
				$this->errors[] = sprintf(__("Путь %s не найден","usam" ), $ftp_dir);	
		}
		return false;
	}
	
	public function ftp_delete( $ftp_path )
	{
		if ( $this->connect !== false )		
			$result = @ftp_delete($this->connect, $ftp_path);
		return $result;
	}
	
	public function ftp_get( $ftp_path, $local_path = '')
	{	
		$result = false;
		if ( $this->connect !== false )
		{
			if ( $local_path )
				$local_file = USAM_UPLOAD_DIR."exchange/{$local_path}/".basename($ftp_path);	
			else				
				$local_file = USAM_UPLOAD_DIR."exchange/".basename($ftp_path);	
			try 
			{
				$mode = $this->settings_ftp['get_mode']=='FTP_ASCII'?FTP_ASCII:FTP_BINARY;
				$result = @ftp_get($this->connect, $local_file, $ftp_path, $mode );
			} 
			catch (PDOException $e) 
			{				
				$this->errors[] = $e->getMessage();
			}	
			if ( !$result )
				$this->errors[] = sprintf( __('Не удается загрузить файл &laquo;%s&raquo; с FTP-сервера','usam'), basename($ftp_path));	
			else
				$result = $local_file;
		}
		return $result;
	}	
	
	public function ftp_put( $ftp_file, $local_file )
	{			
		$result = false;
		if ( @!ftp_put($this->connect, $ftp_file, $local_file, FTP_ASCII) )											
			$this->errors[]  = __('Не удается скачать файл','usam');	
		else				
			$result = true;	
		return $result;
	}	
	
	/* $string Строка,  $file Название файла, $ftp_file Путь на ftp */
	public function fwrite_tp_put( $string, $file, $ftp_folder, $encoding = 'utf-8' )
	{			
		$local_file = USAM_UPLOAD_DIR.'exchange/'. $file;			
		switch( $encoding )
		{
			case 'windows-1251': 
				$out_encoding = iconv ('utf-8', 'windows-1251', $string);
			break;
			case 'utf-8': 		
				$out_encoding = $string;
			break;
			default:
				$out_encoding = $string;
			break;			
		}			
		$f = fopen($local_file,"w");	
		$result = fwrite($f, $out_encoding);			
		if ( $result )
		{								
			fclose($f);
			$result = $this->ftp_put( $ftp_folder.'/'.$file, $local_file );
			unlink( $local_file );				
		}			
		return $result;
	}	
}
?>