<?php
/**
 * Создать резервную копию базы банных
 */
class USAM_MySQL_Backup 
{
	private $mysqli = NULL;
	private $handle = NULL;
	public $tables_to_dump = array();
	private $table_types = array();
	private $table_status = array();
	private $dbname = '';
	private $compression = '';
	
	public function __construct( $args = array() ) 
	{
		if ( ! class_exists( 'mysqli' ) )
			throw new USAM_Exception( __('Не найдено расширение MySQLi. Пожалуйста, установите его.', 'usam') );

		$default_args = array(
			'dbhost' 	  => DB_HOST,
			'dbname' 	  => DB_NAME,
			'dbuser' 	  => DB_USER,
			'dbpassword'  => DB_PASSWORD,
			'dbcharset'   => defined( 'DB_CHARSET' ) ? DB_CHARSET : '',
			'dumpfilehandle' => fopen( 'php://output', 'wb' ),
			'dumpfile' 	  => NULL,
			'compression' => ''
		);
		$args = wp_parse_args( $args , $default_args );
		
		if ( empty( $args[ 'dbhost' ] ) )
			$args[ 'dbhost' ] = NULL;
		
		$args[ 'dbport' ]   = NULL;
		$args[ 'dbsocket' ] = NULL;
		if ( strstr( $args[ 'dbhost' ], ':' ) ) {
			$hostparts = explode( ':', $args[ 'dbhost' ], 2 );
			$hostparts[ 0 ] = trim( $hostparts[ 0 ] );
			$hostparts[ 1 ] = trim( $hostparts[ 1 ] );
			if ( empty( $hostparts[ 0 ] ) )
				$args[ 'dbhost' ] = NULL;
			else
				$args[ 'dbhost' ] = $hostparts[ 0 ];
			if ( is_numeric( $hostparts[ 1 ] ) )
				$args[ 'dbport' ] = (int) $hostparts[ 1 ];
			else
				$args[ 'dbsocket' ] = $hostparts[ 1 ];
		}

		$this->mysqli = mysqli_init();
		if ( ! $this->mysqli )
			throw new USAM_Exception( __('Не удается подключиться к базе данных MySQLi', 'usam') );

		if ( !empty( $args[ 'dbcharset' ] ) ) {
			if ( ! $this->mysqli->options( MYSQLI_INIT_COMMAND, 'SET NAMES ' . $args[ 'dbcharset' ] . ';' ) )
				throw new USAM_Exception( sprintf( __('Setting of MySQLi init command "%s" failed', 'usam'), 'SET NAMES ' . $args[ 'dbcharset' ] . ';' ) );
		}

		if ( ! $this->mysqli->options( MYSQLI_OPT_CONNECT_TIMEOUT, 5 ) )
			throw new USAM_Exception( __('Setting of MySQLi connection timeout failed', 'usam') );
		
		if ( ! $this->mysqli->real_connect( $args[ 'dbhost' ], $args[ 'dbuser' ], $args[ 'dbpassword' ], $args[ 'dbname' ], $args[ 'dbport' ], $args[ 'dbsocket' ] ) )
			throw new USAM_Exception( sprintf( __('Cannot connect to MySQL database %1$d: %2$s', 'usam'), mysqli_connect_errno(), mysqli_connect_error() ) );
	
		if ( !empty( $args[ 'dbcharset' ] ) && method_exists( $this->mysqli, 'set_charset' ) ) {
			$res = $this->mysqli->set_charset( $args[ 'dbcharset' ] );
			if ( ! $res )
				throw new USAM_Exception( sprintf( _x( 'Cannot set DB charset to %s','Database Charset', 'usam'), $args[ 'dbcharset' ] ) );
		}		
		$this->dbname = $args[ 'dbname' ];
		
		if ( !empty( $args[ 'compression' ] ) && in_array( $args[ 'compression' ], array( 'gz' ) ) )
			$this->compression = $args[ 'compression' ]; // сжатие
	
		if ( $args[ 'dumpfile' ] ) 
		{
			if ( substr( strtolower( $args[ 'dumpfile' ] ), -3 ) == '.gz' ) {
				if ( ! function_exists( 'gzencode' ) )
					throw new USAM_Exception( __('Functions for gz compression not available', 'usam') );
				$this->compression = 'gz';
				$this->handle = fopen( 'compress.zlib://' . $args[ 'dumpfile' ], 'ab' );
			}  else {
				$this->compression = '';
				$this->handle = fopen( $args[ 'dumpfile' ], 'ab' );
			}
		}
		else 
		{
			$this->handle = $args[ 'dumpfilehandle' ];
		}
		if ( ! is_resource( $this->handle ) )
			throw new USAM_Exception( __('Cannot open SQL backup file', 'usam') );

		$res = $this->mysqli->query( 'SHOW FULL TABLES FROM `' . $this->dbname . '`' );
		$GLOBALS[ 'wpdb' ]->num_queries ++;
		if ( $this->mysqli->error )
			throw new USAM_Exception( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, 'SHOW FULL TABLES FROM `' . $this->dbname . '`' ) );
		while ( $table = $res->fetch_array( MYSQLI_NUM ) ) {
			$this->tables_to_dump[] = $table[ 0 ];
			$this->table_types[ $table[ 0 ] ] = $table[ 1 ];
		}
		$res->close();

		$res = $this->mysqli->query( "SHOW TABLE STATUS FROM `" . $this->dbname . "`" );
		$GLOBALS[ 'wpdb' ]->num_queries ++;
		if ( $this->mysqli->error )
			throw new USAM_Exception( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, "SHOW TABLE STATUS FROM `" .$this->dbname . "`" ) );
		while ( $tablestatus = $res->fetch_assoc() ) {
			$this->table_status[ $tablestatus[ 'Name' ] ] = $tablestatus;
		}
		$res->close();
	}

	public function execute() 
	{
		@set_time_limit( 300 );		
		$this->dump_head();		
		foreach( $this->tables_to_dump as $table ) 
		{
			$this->dump_table_head( $table );
			$this->dump_table( $table );
			$this->dump_table_footer( $table );
		}		
		$this->dump_footer();
	}

	public function dump_head( $wp_info = FALSE ) 
	{		
		$res = $this->mysqli->query( "SELECT @@time_zone" );
		$GLOBALS[ 'wpdb' ]->num_queries ++;
		$mysqltimezone = $res->fetch_row();
		$mysqltimezone = $mysqltimezone[0];
		$res->close();

	
		$dbdumpheader  = "-- ---------------------------------------------------------\n";
		$dbdumpheader .= "-- UNIVERSAM ver.: " . USAM_VERSION . "\n";
		if ( $wp_info ) 
		{
			$dbdumpheader .= "-- Blog Name: " . get_bloginfo( 'name' ) . "\n";
			$dbdumpheader .= "-- Blog URL: " . trailingslashit( get_bloginfo( 'url' ) ) . "\n";
			$dbdumpheader .= "-- Blog ABSPATH: " . trailingslashit( str_replace( '\\', '/', ABSPATH ) ) . "\n";
			$dbdumpheader .= "-- Blog Charset: " . get_bloginfo( 'charset' ) . "\n";
			$dbdumpheader .= "-- Table Prefix: " . $GLOBALS[ 'wpdb' ]->prefix . "\n";
		}
		$dbdumpheader .= "-- ".__('Имя базы данных','usam').": " . $this->dbname . "\n";
		$dbdumpheader .= "-- ".__('Время создания копии базы','usam').": " . date_i18n( 'Y-m-d H:i.s' ) . "\n";
		$dbdumpheader .= "-- ---------------------------------------------------------\n\n";
	
		$dbdumpheader .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
		$dbdumpheader .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
		$dbdumpheader .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
		$dbdumpheader .= "/*!40101 SET NAMES " . $this->mysqli->character_set_name() . " */;\n";
		$dbdumpheader .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
		$dbdumpheader .= "/*!40103 SET TIME_ZONE='" . $mysqltimezone . "' */;\n";
		$dbdumpheader .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
		$dbdumpheader .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
		$dbdumpheader .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
		$dbdumpheader .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
		$this->write( $dbdumpheader );
	}

	public function dump_footer() 
	{		
		$this->write( "\n--\n-- Backup routines for database '" . $this->dbname . "'\n--\n" );
		
		$res = $this->mysqli->query( "SHOW FUNCTION STATUS" );
		$GLOBALS[ 'wpdb' ]->num_queries ++;
		if ( $this->mysqli->error ) {
			trigger_error( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, "SHOW FUNCTION STATUS" ), E_USER_WARNING );
		} else {
			while ( $function_status = $res->fetch_assoc() ) {
				if ( $this->dbname != $function_status[ 'Db' ] )
					continue;
				$create = "\n--\n-- Function structure for " . $function_status[ 'Name' ] . "\n--\n\n";
				$create .= "DROP FUNCTION IF EXISTS `" . $function_status[ 'Name' ] . "`;\n";
				$create .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
				$create .= "/*!40101 SET character_set_client = '" . $this->mysqli->character_set_name() . "' */;\n";
				$res2 = $this->mysqli->query( "SHOW CREATE FUNCTION `" .  $function_status[ 'Db' ] . "`.`" . $function_status[ 'Name' ] . "`" );
				$GLOBALS[ 'wpdb' ]->num_queries ++;
				if ( $this->mysqli->error )
					trigger_error( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, "SHOW CREATE FUNCTION `" .  $function_status[ 'Db' ] . "`.`" . $function_status[ 'Name' ] . "`" ), E_USER_WARNING );
				$create_function = $res2->fetch_assoc();
				$res2->close();
				$create .= $create_function[ 'Create Function' ] . ";\n";
				$create .= "/*!40101 SET character_set_client = @saved_cs_client */;\n";
				$this->write( $create );
			}
			$res->close();
		}	
		$res = $this->mysqli->query( "SHOW PROCEDURE STATUS" );
		$GLOBALS[ 'wpdb' ]->num_queries ++;
		if ( $this->mysqli->error ) {
			trigger_error( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, "SHOW PROCEDURE STATUS" ), E_USER_WARNING );
		} 
		else 
		{
			while ( $procedure_status = $res->fetch_assoc() ) {
				if ( $this->dbname != $procedure_status[ 'Db' ] )
					continue;
				$create = "\n--\n-- Procedure structure for " . $procedure_status[ 'Name' ] . "\n--\n\n";
				$create .= "DROP PROCEDURE IF EXISTS `" . $procedure_status[ 'Name' ] . "`;\n";
				$create .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
				$create .= "/*!40101 SET character_set_client = '" . $this->mysqli->character_set_name() . "' */;\n";
				$res2 = $this->mysqli->query( "SHOW CREATE PROCEDURE `" . $procedure_status[ 'Db' ] . "`.`" . $procedure_status[ 'Name' ] . "`" );
				$GLOBALS[ 'wpdb' ]->num_queries ++;
				if ( $this->mysqli->error )
					trigger_error( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, "SHOW CREATE PROCEDURE `" . $procedure_status[ 'Db' ] . "`.`" . $procedure_status[ 'Name' ] . "`" ), E_USER_WARNING );
				$create_procedure = $res2->fetch_assoc();
				$res2->close();
				$create .= $create_procedure[ 'Create Procedure' ] . ";\n";
				$create .= "/*!40101 SET character_set_client = @saved_cs_client */;\n";
				$this->write( $create );
			}
			$res->close();
		}		
		$dbdumpfooter  = "\n/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
		$dbdumpfooter .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
		$dbdumpfooter .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
		$dbdumpfooter .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
		$dbdumpfooter .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
		$dbdumpfooter .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
		$dbdumpfooter .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
		$dbdumpfooter .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";
		$dbdumpfooter .= "\n-- Backup completed on " . date_i18n( 'Y-m-d H:i:s' ). "\n";
		$this->write( $dbdumpfooter );
	}

	public function dump_table_head( $table ) 
	{
		if ( $this->table_types[ $table ] == 'VIEW' ) 
		{
			$tablecreate = "\n--\n-- View structure for `" . $table . "`\n--\n\n";
			$tablecreate .= "DROP VIEW IF EXISTS `" . $table . "`;\n";
			$tablecreate .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
			$tablecreate .= "/*!40101 SET character_set_client = '" . $this->mysqli->character_set_name() . "' */;\n";
			$res = $this->mysqli->query( "SHOW CREATE VIEW `" . $table . "`" );
			$GLOBALS[ 'wpdb' ]->num_queries ++;
			if ( $this->mysqli->error )
				trigger_error( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, "SHOW CREATE VIEW `" . $table . "`" ), E_USER_WARNING );
			
			$createview = $res->fetch_assoc();
			$res->close();
			$tablecreate .= $createview[ 'Create View' ] . ";\n";
			$tablecreate .= "/*!40101 SET character_set_client = @saved_cs_client */;\n";
			$this->write( $tablecreate );

			return 0;
		}

		if ( $this->table_types[ $table ] != 'BASE TABLE' )
			return 0;

		$tablecreate = "\n--\n-- Table structure for `" . $table . "`\n--\n\n";
		$tablecreate .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
		$tablecreate .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
		$tablecreate .= "/*!40101 SET character_set_client = '" . $this->mysqli->character_set_name() . "' */;\n";
		$res = $this->mysqli->query( "SHOW CREATE TABLE `" . $table . "`" );
		$GLOBALS[ 'wpdb' ]->num_queries ++;
		if ( $this->mysqli->error )
			trigger_error( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, "SHOW CREATE TABLE `" . $table . "`" ), E_USER_WARNING );
		$createtable = $res->fetch_assoc();
		$res->close();
		$tablecreate .= $createtable[ 'Create Table' ] . ";\n";
		$tablecreate .= "/*!40101 SET character_set_client = @saved_cs_client */;\n";
		$this->write( $tablecreate );

		if ( $this->table_status[ $table ][ 'Engine' ] !== 'MyISAM' ) {
			$this->table_status[ $table ][ 'Rows' ] = '~' . $this->table_status[ $table ][ 'Rows' ];
		}

		if ( $this->table_status[ $table ][ 'Rows' ] !== 0 ) 
		{
			$this->write( "\n--\n-- Backup data for table `" . $table . "`\n--\n\nLOCK TABLES `" . $table . "` WRITE;\n/*!40000 ALTER TABLE `" . $table . "` DISABLE KEYS */;\n" );
		}
		return $this->table_status[ $table ][ 'Rows' ];
	}

	public function dump_table_footer( $table ) {

		if ( $this->table_status[ $table ][ 'Rows' ] !== 0 ) {
			$this->write( "/*!40000 ALTER TABLE `" . $table . "` ENABLE KEYS */;\nUNLOCK TABLES;\n" );
		}
	}

	public function dump_table( $table, $start = 0, $length = 0 ) 
	{
		if ( ! is_numeric( $start ) ) {
			throw new USAM_Exception( sprintf( __('Неверное задание для резервного копирования таблицы: %1$s ', 'usam'), $start ) );
		}

		if ( ! is_numeric( $length ) ) {
			throw new USAM_Exception( sprintf( __('Неправильно задана длина для резервного копирования таблицы: %1$s ', 'usam'), $length ) );
		}

		$done_records = 0;
		if ( $length == 0 && $start == 0 ) {
			$res = $this->mysqli->query( "SELECT * FROM `" . $table . "` ", MYSQLI_USE_RESULT );
		} else {
			$res = $this->mysqli->query( "SELECT * FROM `" . $table . "` LIMIT " . $start . ", " . $length, MYSQLI_USE_RESULT );
		}
		$GLOBALS[ 'wpdb' ]->num_queries ++;
		if ( $this->mysqli->error )
			trigger_error( sprintf( __('Database error %1$s for query %2$s', 'usam'), $this->mysqli->error, "SELECT * FROM `" . $table . "`" ), E_USER_WARNING );

		$fieldsarray = array();
		$fieldinfo   = array();
		$fields      = $res->fetch_fields();
		$i = 0;
		foreach ( $fields as $filed ) {
			$fieldsarray[ $i ]               = $filed->orgname;
			$fieldinfo[ $fieldsarray[ $i ] ] = $filed;
			$i ++;
		}

		$dump = '';
		while ( $data = $res->fetch_assoc() ) 
		{
			$values = array();
			foreach ( $data as $key => $value ) {
				if ( is_null( $value ) || ! isset($value ) ) // Make Value NULL to string NULL
					$value = "NULL";
				elseif ( in_array($fieldinfo[ $key ]->type, array( MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG,  MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24 ) ) ) //is value numeric no esc
					$value = empty( $value ) ? 0 : $value;
				else
					$value = "'" . $this->mysqli->real_escape_string( $value ) . "'";
				$values[ ] = $value;
			}
			//new query in dump on more than 50000 chars.
			if ( empty( $dump ) )
				$dump = "INSERT INTO `" . $table . "` (`" . implode( "`, `", $fieldsarray ) . "`) VALUES \n";
			if ( strlen( $dump ) <= 50000  ) {
				$dump .= "(" . implode( ", ", $values ) . "),\n";
			} else {
				$dump .= "(" . implode( ", ", $values ) . ");\n";
				$this->write( $dump );
				$dump = '';
			}
			$done_records ++;
		}
		if ( !empty( $dump ) ) {
			$dump = substr( $dump, 0, -2 ) . ";\n" ;
			$this->write( $dump );
		}
		$res->close();

		return $done_records;
	}

	private function write( $data ) 
	{
		$written = fwrite( $this->handle, $data );
		if ( ! $written )			
			throw new USAM_Exception( __('Ошибка при записи файла!', 'usam') );
	}

	public function __destruct() 
	{		
		$this->mysqli->close();	
		if ( is_resource( $this->handle ) )
			fclose( $this->handle );
	}
}