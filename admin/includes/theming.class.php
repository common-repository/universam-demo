<?php
/**
 * Класс переноса темы
 *
 * Этот класс отвечает за перемещение всех основных файлов шаблона из папки плагинов к активной папке темы при новой установке.
 * Он отвечает за проверку соответствующих папках по темам, конвертации и переноса в активную папку темы.
 */
class USAM_Theming 
{	
	private $active_wp_style = USAM_THEMES_PATH;	
	private $templates_to_move;
	private $list_of_templates;	
	
	private $error = '';
	private $files_error = array();

	public function __construct( $templates_to_move )
	{		
		if ( !file_exists( USAM_THEMES_PATH ) )
			mkdir( USAM_THEMES_PATH ); //создать папку			
		$this->templates_to_move = $templates_to_move;	
		$this->list_of_templates = usam_list_product_templates( $this->active_wp_style );		
		if ( $this->files_exist() ) 		
			return;
		else 
		{					
			$this->move_theme( USAM_CORE_THEME_PATH, $this->active_wp_style );
			if ( !empty($this->files_error) )
				$this->error = __('Некоторые файлы не могут быть скопированы. Пожалуйста, убедитесь, что папка с темой доступна для записи.', 'usam')."<br>".implode(', ', $this->files_error);
		}
	}
	
	public function get_errors()
	{
		return $this->error;
	}	

	/**
	 * Проверяет файлы в теме, которого существуют в текущей папке темы и которые были выбраны, но не были перемещены
	 */
	function files_exist()
	{		
		if( empty( $this->templates_to_move ) )
		{
			$this->error = __('Вы не указали файлы шаблонов для перемещения', 'usam');			
		}
		$results = array_diff( $this->templates_to_move, $this->list_of_templates );
		$this->templates_to_move = $results;	
		if ( count( $results ) == 0 )
			return true;
		else
			return false;
	}

	function recursive_copy( $src, $dst ) 
	{
		$dir = opendir( $src );	
		while ( false !== ( $file = readdir( $dir )) ) 
		{			
			if ( in_array( $file, $this->templates_to_move ) ) 
			{
				if ( is_dir( $src . '/' . $file ) )
					$this->recursive_copy( $src . '/' . $file, $dst . '/' . $file );
				else
				{
					$result = @ copy( $src . '/' . $file, $dst . '/' . $file );
					if ( !$result )
						$this->files_error[] = $src . '/' . $file;
				}
			}
		}
		closedir( $dir );
	}
	
	function move_theme_images()
	{
		$image_dir = USAM_CORE_THEME_PATH.'images';
		$end_location = $this->active_wp_style.'images';
		@mkdir( $end_location );
		$imgdr = opendir($image_dir);
		while ( false !== ( $file = readdir( $imgdr )) ) {
			@ copy( $image_dir . '/' . $file, $end_location . '/' . $file );
		}
		closedir( $imgdr );
	}

	/* Перемещать, переименовать, и добавляет верхний и нижний колонтитулы функции темы, если они в настоящее время не имеют его.
	 */
	function move_theme( $old, $new ) 
	{			
		$this->recursive_copy( $old, $new );
		$path = $new;
		$dh   = opendir( $old );
		while ( false !== ( $file = readdir( $dh ) ) )
		{
			if ( $file != "." && $file != ".." && !strstr( $file, ".svn" ) && !strstr( $file, "images" ) && ( strstr( $file, 'usam-' ) || strstr($file, '.css') ) ) 
			{
				if('usam-default.css' == $file)
					$this->move_theme_images();
				if ( in_array( $file, $this->templates_to_move ) ) 
				{					
					if ( !strstr( $file, "functions" ) && !strstr( $file, "widget" ) ) 
					{
						$file_data = file_get_contents( $old . "/" . $file );
						$result = @file_put_contents( $path . "/" . $file, $file_data );
						if ( !$result )
							$this->files_error[] = $path . '/' . $file;						
						rename( $path . "/" . $file, $path . "/" . $file );
					}
				}
			}
		}		
		closedir( $dh );
		do_action( 'usam_move_theme' );
	}
}	

/**
 * Проверяет путь до темы, сравнивает активную тему и тему в рамках USAM_CORE_THEME_PATH находит файлы с тем же именем.
 */
function usam_check_theme_location() 
{                       	
	$current_theme_files = usam_list_product_templates( USAM_THEMES_PATH );	// Загрузите файлы из текущей темы	
	$template_files      = usam_list_product_templates( USAM_CORE_THEME_PATH ); // Загрузите файлы в папке темы
	$results             = array_intersect( $current_theme_files, $template_files );	
	if ( count( $results ) > 0 ) // Возвращаем различия
		return $results;
	else
		return false;
}

/**
 * Получить файлы в заданном в каталоге 
 */
function usam_list_product_templates( $path ) 
{
	$templates = array();
	if ( is_dir($path) )	
	{
		$dh = opendir( $path );
		while ( ( $file = readdir( $dh ) ) !== false ) 
		{		
			if ( $file != "." && $file != ".." && !strstr( $file, ".svn" ) && !strstr( $file, "images" ) && is_file( $path . $file ) )
				$templates[] = $file;
		}
	}
	return $templates; // Возвращает имя шаблона
}
?>