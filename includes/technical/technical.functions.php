<?php
/* Технические функции работы кода магазина
 * @since 3.7
 */
 
/**
 * Исключение обработчика
 */
class USAM_Exception extends Exception { }


/**
 * Вывод информации
 */
class USAM_Debug
{
	private $debug = array();	
	private $title_header = [];

	public function __construct()
	{					
		if ( defined('WP_DEBUG') && WP_DEBUG )
		{
			$anonymous_function = function($a) { return true; };	
			add_filter( 'http_request_host_is_external', $anonymous_function);	
		}
		add_action('init', array( $this,'init' ), 999 );
	}
	
	function init()
	{
		$this->debug = (array)get_user_option( 'usam_debug' );	
		if ( !empty($this->debug['location']) )
		{
			switch( $this->debug['location'] )				
			{							
				case 'all':		
					add_action('wp_footer', [$this,'display_css'] );
					add_action('admin_footer', [$this,'display_css'] );
					
					add_action('wp_footer', [$this,'display'], 111 );
					add_action('admin_footer', [$this,'display'], 111 );
				break;
				case 'admin':			
					add_action('admin_footer', [$this,'display_css'] );
					add_action('admin_footer', [$this,'display'], 111 );	
				break;
				case 'site':			
					add_action('wp_footer', [$this,'display_css'] );
					add_action('wp_footer', [$this,'display'], 111 );	
				break;
			}				
		}		
		$show_server_load = get_user_option( 'usam_show_server_load' );	
		if ( !empty($show_server_load) )
		{
			add_action('wp_footer', array( $this,'insert_performance_numbers' ),999 );
			add_action('admin_footer', array( $this,'insert_performance_numbers' ),999 );
		}
		if( (int)get_option('usam_server_load_log', 0 ) )
		{
			register_shutdown_function(['USAM_Debug', 'shutdown']);
			add_action('log_query_custom_data', ['USAM_Debug', 'log_query'],999, 5 );
		}		
	}
	
	public static function log_query($query_data, $query, $query_time, $query_callstack, $query_start)
	{			
		static $i = 0;
		$i++;
		
		$log = new USAM_Log_File( 'log_query' ); 	
		$log->write("\r\n");		
		if( $i === 1 )
		{
			$log->fwrite( $_SERVER['REQUEST_URI'] );	
		}
		$log->fwrite( "$i ".$query, false );
		$log->fwrite( "query time ". $query_time, false );
		if ( $query_time > 1 )
			$log->fwrite( "big time ". $query_time, false );
		if( is_array($query_data) )
			$log->fwrite( "query_data ".count($query_data), false );
		$log->fwrite_array( $query_callstack, false );
	}
	
	public static function shutdown()
	{	
		$log = new USAM_Log_File( 'server_load_log' ); 	
		$log->write("\r\n");		
		$log->fwrite( $_SERVER['REQUEST_URI'] );
		$log->fwrite( round(memory_get_usage()/1024/1024, 2).' MB '.' | '.get_num_queries().' SQL | '.timer_stop(0, 1).' '.__('сек','usam'), false );		
	}
	
	function insert_performance_numbers()
	{	
		?>	
		<script>
			jQuery(document).ready(function() 
			{				
				jQuery("#wp-admin-bar-performance_site .ab-label").html('<?php echo round(memory_get_usage()/1024/1024, 2).' MB '.' | '.get_num_queries().' SQL | '.timer_stop(0, 1).' '.__('сек','usam') ?>');				
			});
		</script>
		<?php
	}
	
	function display_css()
	{	
		wp_enqueue_style( 'usam-technical', USAM_URL.'/admin/assets/css/technical.css', false, '1.1', 'all' );	
	}

	function display()
	{					
		$this->title_header = ['sql' => __("SQL запросы", 'usam'), 'globals' => __("GLOBALS", 'usam')];
		
		if ( is_admin() )
			$class = 'admin';
		else
			$class = 'site';		
		echo '
		<div class = "technical_display">
		<div class = "usam_tabs technical_display_'.$class.'">		
		<div class = "header_tab">';
			$current = true;
			foreach ( $this->debug['display'] as $tab => $display ) 
			{
				if ( $display == 1 )
				{						
					echo '<a class="tab" href="#technical_'.$tab.'">'.$this->title_header[$tab].'</a>';
					$current = false;
				}
			}							
		echo '</div>';
		echo '<div class = "countent_tabs">';
		$current = true;
		foreach ( $this->debug['display'] as $tab => $display ) 
		{
			if ( $display == 1 )
			{
				echo '<div id = "technical_'.$tab.'" class = "tab  '.($current?'current':'hidden').'">';
					$method = 'tab_'.$tab;	
					$this->$method();	
				echo '</div>';
				$current = false;
			}
		}
		echo '</div></div></div>';
	}
 
 //Производительность
	function tab_sql()
	{
		if ( defined('SAVEQUERIES') && SAVEQUERIES ) 
		{
			global $wpdb;
			echo '########## SQL запросы ##########<br /><br />'; 
			echo '<span style="margin-left: 10px;"><b>'; echo 'Количество SQL-запросов = ' . sizeof($wpdb->queries); echo '</b>
			</span><br />'; 
			$sqlstime = 0; 
			foreach ( $wpdb->queries as $qrarr ) 
			{ 
				$sqlstime += $qrarr[1]; 
			} 
			echo '<span style="margin-left: 10px;"><b>'; echo 'Затрачено времени = ' . round( $sqlstime, 4 ) . ' секунд'; echo '</b></span><br /><br />'; 
			$i = 1;
			foreach ( $wpdb->queries as $qrarr )
			{ 
					echo '<div class="technical__sql_querie"><span style="color: #EDE7C2;margin-left: 10px;">№ '.$i.' </span>' . $qrarr[0] . '</div>';
					echo '<div style="color: #EDE7C2;margin-left: 10px;">Время выполнения = </span>' . round( $qrarr[1], 4 ) . ' секунд</div>';
					echo '<span style="color: #EDE7C2;margin-left: 10px;">Файлы и функции: </span><br />'; $filesfunc = explode( ",", $qrarr[2] ); 
				foreach ( $filesfunc as $funcs ) 
				{ 
					echo '<span style="margin-left: 20px;">+ ' . $funcs . '</span><br />'; 
				} echo '<br />'; 
				$i++;
			} 
			echo '<br />########## END: SQL запросы ##########<br /><br />'; 
		}
	}
	
	function tab_globals()
	{		
		foreach ( $GLOBALS as $key => $value )
		{
			ob_start();	
			print_r($value);
			$html = ob_get_contents();
			ob_end_clean();	
			echo '<span style="color: #EDE7C2;margin-right: 10px;">'.$key.'</span>' . esc_html($html) . '<br />';
		}
	}		
}
new USAM_Debug();
/*

$Log = new USAM_Log_File( 'notifications' ); 
$Log->fwrite_array( $request );	
$Log->debug_backtrace( );
	
*/

/*	Описание: класс для создания лог файлов
 */
class USAM_Log_File
{
	private $f_log;
	private $file_log;
	
	function __construct( $file_name = 'log', $remove = false, $file_text_header = '' )
	{			
		$ext = 'txt'; 
		$this->file_log  = USAM_UPLOAD_DIR.'Log/'.current_time("m-Y").'_'.$file_name.'.'.$ext;			
		$mode = $remove ? 'w': 'a';
		$this->f_log = fopen($this->file_log, $mode);		
		if (file_exists($file_name)) 
		{		
			$this->file_fwrite_header( $file_text_header );
		}
	}
	
	public function fwrite( $text, $write_time = true ) 
	{					
		if ( empty($text) )
			return false;
		
		if ( $write_time )
			$text = "[".current_time("d-m-Y H:i:s")."] ".$text;			
		$text .= "\r\n";
		return fwrite($this->f_log, $text );
	}
	
	private function file_fwrite_header( $text ) 
	{			
		if ( !$text )
			return false;
		
		$text = mb_strtoupper($text, 'UTF-8'); 
		$text .= "______________________________ $text ______________________________\r\n";
		return fwrite($this->f_log, $text );
	}
	
	private function text_filter( $text ) 
	{			
		return preg_replace('#<br(\s+)?\/?>#i', "\r\n", $text);
	}
	
	public function file_fwrite( $file_text ) 
	{	
		if ( $file_text !== '' && $this->f_log !== false )
		{			
			if (is_array($file_text) )
			{
				$str = "\r\n";
				foreach ($file_text as $key => $value) 
				{
					$str .= $this->text_filter( $value )."\r\n";	
				}
			}
			else
			{			
				$str = $this->text_filter( $file_text );				
			}
			$this->fwrite( $str );
		}		
	}
	
	//DEBUG_BACKTRACE_PROVIDE_OBJECT
	public function debug_backtrace( $options = 'functions' ) 
	{					
		fwrite($this->f_log, date("Y-m-d H:i:s")."\r\n" );
		if ( $options === 'functions' )
		{
			$results = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			foreach ($results as $result ) 
			{
				$text = '';
				if ( isset($result['class']) )
				{
					$result['class'] = strtoupper($result['class']);			
					if ( $result['class'] != 'USAM_LOG_FILE' )				
						$text = $result['class'].' -> ';
				}
				if ( $result['function'] != 'debug_backtrace' && $result['function'] != 'require_once' && $result['function'] != 'require')
					fwrite($this->f_log, $text.$result['function']."\r\n" );
			}
		}
		else
		{		
			ob_start();		
			print_r( debug_backtrace( $options ) );
			$text = ob_get_clean();
			fwrite($this->f_log, $text."\r\n" );
		}		
		fwrite($this->f_log, memory_get_usage()."\r\n" );
	}	
	
	function sql()
	{
		if ( defined('SAVEQUERIES') && SAVEQUERIES ) 
		{
			global $wpdb; 
			$sqlstime = 0; 
			foreach ( $wpdb->queries as $qrarr ) 
			{ 
				$sqlstime += $qrarr[1]; 
			} 		
			$this->fwrite('Количество SQL-запросов = ' . sizeof($wpdb->queries).' Затрачено времени = ' . round( $sqlstime, 4 ) . ' секунд' );			
			$i = 1;
			foreach ( $wpdb->queries as $qrarr )
			{ 
				$this->fwrite("№ $i SQL-запрос = ".$qrarr[0].' Время выполнения = '.round( $qrarr[1], 4 ) . ' секунд'  );
				$filesfunc = explode( ",", $qrarr[2] ); 
				foreach ( $filesfunc as $funcs ) 
				{ 
					$this->fwrite( $funcs );
				}
				$i++;
			} 
		}
	}
	
	public function write( $text ) 
	{	
		fwrite( $this->f_log, $text );
	}
	
	public function fwrite_array( $file_text, $write_time = true ) 
	{	
		ob_start();		
		
		print_r( $file_text );
		
		$str = ob_get_clean();		
		$this->fwrite( $str, $write_time );
	}	
	
	public function file_fclose( $delimiter = false ) 
	{
		if ( $this->f_log !== false )
		{
			if ( $delimiter )
				fwrite($this->f_log, "_________________________________________________\r\n");
			
			fclose($this->f_log);
		}
	}
}	
	
function usam_log_file( $error, $file_name = 'log', $delimiter = false ) 
{
	if ( empty($error) )
		return false;
	
	$log = new USAM_Log_File( $file_name ); 							
	$log->file_fwrite( $error );			
	$log->file_fclose( $delimiter );
}

function usam_start_measure_performance() 
{
	global $usam_performance;
	
	$usam_performance = ['timer' => microtime(true), 'queries' => get_num_queries(), 'memory' => round(memory_get_usage()/1024/1024, 2)];
}

function usam_end_measure_performance() 
{
	global $usam_performance;
	
	$timer = microtime( true ) - $usam_performance['timer'];
	$memory = round(memory_get_usage()/1024/1024, 2) - $usam_performance['memory'];
	$queries = get_num_queries() - $usam_performance['queries'];
			
	return "$memory MB | $queries SQL | $timer ".__('сек','usam');	
}
?>