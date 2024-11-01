<?php
class USAM_Page_Viewed
{	 // строковые
	private static $string_cols = [
		'url',
		'date_insert',
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'post_id',	
		'visit_id',		
		'term_id',		
	];
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
			
		return false;
	}
	private $data    = array();
	private $fetched = false;	
	private $args    = array( 'col'   => '', 'value' => '' );
	private $exists  = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( false === $value )
			return;

		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id') ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_page_viewed' );			
		}
		// кэш существует
		if ( $this->data ) 
		{	
			$this->fetched = true;
			$this->exists = true;			
		}	
		else
			$this->fetch();	
	}

	/**
	 * Обновить кеш
	 */
	public function update_cache() 
	{		
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_page_viewed' );	
		do_action( 'usam_page_viewed_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_page_viewed' );	
		do_action( 'usam_page_viewed_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_page_viewed_before_delete', $data );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_PAGE_VIEWED . " WHERE id = %d", $id ) );	
		
		$this->delete_cache( );	
		do_action( 'usam_page_viewed_delete', $id );
	}	

	/**
	 * Выбирает фактические записи из базы данных
	 */
	private function fetch() 
	{	
		global $wpdb;
		if ( $this->fetched )
			return;

		if ( ! $this->args['col'] || ! $this->args['value'] )
			return;

		extract( $this->args );

		$format = self::get_column_format( $col );		
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_PAGE_VIEWED . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_page_viewed_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->update_cache();	
		}
		do_action( 'usam_page_viewed_fetched', $this );
		$this->fetched = true;
	}	

	/**
	 * Проверить существует ли строка в БД
	 */
	public function exists() 
	{
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства из БД
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_page_viewed_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_page_viewed_get_data', $this->data, $this );
	}
	
	/**
	 * Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
	 * @since 4.9
	 */
	public function set( $key, $value = null ) 
	{
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = array( $key => $value );
		}
		$properties = apply_filters( 'usam_page_viewed_set_properties', $properties, $this );	

		$this->fetch();		
		if ( ! is_array( $this->data ) )
			$this->data = array();

		$this->data = array_merge( $this->data, $properties );
		return $this;
	}

	private function get_data_format( ) 
	{
		$formats = array();
		foreach ( $this->data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;	
			else
				unset($this->data[$key]);
		}
		return $formats;
	}	
	
	/**
	 * Сохраняет в базу данных
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_page_viewed_pre_save', $this );	
		
		$result = false;		
		if ( $this->args['col'] ) 
		{	// обновление			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_page_viewed_pre_update', $this );	
			$this->data = apply_filters( 'usam_page_viewed_update_data', $this->data );	
			$formats = $this->get_data_format( );			
			
			$result = $wpdb->update( USAM_TABLE_PAGE_VIEWED, $this->data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );						
			}			
			do_action( 'usam_page_viewed_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_page_viewed_pre_insert' );	
				
			if ( empty($this->data['visit_id']) )
				$this->data['visit_id'] = usam_get_contact_visit_id();	
			if ( !$this->data['visit_id'] )							
				return false;	
			if ( empty($this->data['url']) )
				$this->data['url'] = $_SERVER["REQUEST_URI"];	

			if ( !empty($_SERVER['HTTP_REFERER']) )					
				$referer = sanitize_text_field($_SERVER['HTTP_REFERER']);	
			else
			{
				require_once( USAM_FILE_PATH . '/includes/analytics/pages_viewed_query.class.php' );
				$referer = usam_get_pages_viewed(['fields' => 'url', 'visit_id' => $this->data['visit_id'], 'number' => 1, 'order' => 'DESC']);	
			}
			if ( $this->data['url'] == $referer )
				return false;
			if ( !isset($this->data['post_id']) )
			{
				global $wp_query;					
				if ( !empty($wp_query->query_vars['term']) )
				{
					$taxonomies = get_taxonomies();
					foreach( $taxonomies as $taxonomy )
					{
						if ( isset($wp_query->query_vars[$taxonomy]) )
						{
							$term = get_term_by( 'slug', $wp_query->query_vars[$taxonomy], $taxonomy );
							if ( !empty($term->term_id)  )
								$this->data['term_id'] = $term->term_id;
						}
						
					}					
					if ( !empty($term->term_id)  )
						$this->data['term_id'] = $term->term_id;
				}
				else
				{	
					wp_reset_postdata();
					$this->data['post_id'] = get_the_ID();	
				}
			}						
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );		
			$this->data = apply_filters( 'usam_page_viewed_insert_data', $this->data );				
			$format = $this->get_data_format( );			
		
			$result = $wpdb->insert( USAM_TABLE_PAGE_VIEWED, $this->data, $format );		
			if ( $result ) 
			{									
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col'   => 'id',  'value' => $this->get( 'id' )];	
				$this->update_cache();
				
				$visit = usam_get_visit( $this->data['visit_id'] );
				if ( !empty($visit) )
				{
					$visit['views']++;
					usam_update_visit( $this->data['visit_id'], ['views' => $visit['views']] );
				}								
				do_action( 'usam_page_viewed_insert', $this );				
			}			
		} 		
		do_action( 'usam_page_viewed_save', $this );
		return $result;
	}
}

function usam_get_page_viewed( $value, $colum = 'id' )
{	
	$page_viewed = new USAM_Page_Viewed($value, $colum);	
	$page_viewed_data = $page_viewed->get_data();	
	return $page_viewed_data;	
}

function usam_update_page_viewed( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$page_viewed = new USAM_Page_Viewed( $id );	
		$page_viewed->set( $data );	
		return $page_viewed->save();
	}
	return true;
}

function usam_insert_page_viewed( $value = array() )
{	
	$page_viewed = new USAM_Page_Viewed( $value );	
	$page_viewed->save();
	$page_viewed_id = $page_viewed->get('id');	
	return $page_viewed_id;		 
}

function usam_delete_page_viewed( $value, $colum = 'id' )
{	
	$subscriber = new USAM_Page_Viewed( $value, $colum );
	return $subscriber->delete();		 
}