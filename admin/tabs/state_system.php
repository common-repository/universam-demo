<?php
class USAM_Tab_state_system extends USAM_Page_Tab
{
	protected $views = ['simple'];
	public function get_title_tab()
	{			
		return __('Общие состояние', 'usam');	
	}
	
	public function display() 
	{
		usam_add_box( 'usam_version', __('Версии','usam'), array( $this, 'box_version' ) );	
		usam_add_box( 'usam_server', __('Информация о сервере','usam'), array( $this, 'box_server' ) );	
		usam_add_box( 'usam_showcase', __('Витрина','usam'), array( $this, 'box_showcase' ) );		
	}
	
	function box_version( )
	{
		if( get_bloginfo( 'version' ) < '3.4' )
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
		else
			$theme_data = wp_get_theme();
		$theme = array( 'name' => $theme_data['Name'], 'version' => $theme_data['Version'], 'author' => $theme_data['Author'], 'author_url' => $theme_data['AuthorURI'] );
		$plugins = get_plugins();
		$plugins_option = get_option( 'active_plugins', array() );
		$active_plugins = array();
		foreach( $plugins as $plugin_path => $plugin ) {
			if( !in_array( $plugin_path, $plugins_option ) )
				continue;
			$active_plugins[] = array( 'name' => $plugin['Name'], 'version' => $plugin['Version'], 'url' => $plugin['PluginURI'], 'author' => $plugin['Author'], 'author_url' => $plugin['PluginURI'], 'raw' => print_r( $plugin, true ) );
		}
		?>	
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name">WordPress:</div>
				<div class ="edit_form__item_option"><?php echo get_bloginfo( 'version' ); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'УНИВЕРСАМ', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo USAM_VERSION; ?></div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Версия БД УНИВЕРСАМ', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo get_option( 'usam_db_version', 0 ); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Тема', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo $theme['name']; ?> (<?php _e( 'Автор', 'usam'); ?>: <a href="<?php echo $theme['author_url']; ?>" target="_blank" rel="noopener"><?php echo $theme['author']; ?></a>) <?php _e( 'Версия', 'usam'); ?>: <?php echo $theme['version']; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Активные плагины', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<?php if( $active_plugins ) { ?>
						<ul>
						<?php foreach( $active_plugins as $plugin ) 
						{ ?>
							<li><a href="<?php echo $plugin['url']; ?>"><?php echo $plugin['name']; ?></a> (<?php _e( 'Автор', 'usam'); ?>: <a href="<?php echo $plugin['author_url']; ?>" target="_blank" rel="noopener"><?php echo $plugin['author']; ?></a>) <?php _e( 'Версия', 'usam'); ?>: <?php echo $plugin['version']; ?></li>
						<?php } ?>
					</ul>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php	
	}
	
	function box_server( )
	{		
		global $wpdb;
		$max_upload = round( wp_max_upload_size() / 1024 / 1024, 2 ) . 'MB';
		$max_post = (int)( ini_get( 'post_max_size' ) ) . 'MB';
		$memory_limit = (int)( ini_get( 'memory_limit' ) ) . 'MB';
		$max_execution_time = ini_get( 'max_execution_time' );
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e('Версия PHP', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo PHP_VERSION; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e('Разрадность PHP', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo PHP_INT_SIZE === 4 ?'32 bit':'64 bit'; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e('Версия MySQL', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo $wpdb->db_version(); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e('Максимальный загружаемый файл', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo $max_upload; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Max. POST Size', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo $max_post; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Максимальное время выполнения', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo $max_execution_time; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Распределение памяти', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo $memory_limit; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Ограничение памяти WP', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<?php $memory = usam_filesize_to_bytes( WP_MEMORY_LIMIT );
					if ( $memory < 67108864 )
						echo sprintf( __('%s - Мы рекомендуем устанавливать память не менее 64. Посмотреть: <a href="%s">Увеличение памяти, выделенной PHP</a>', 'usam'), '<span class="item_status_attention item_status">'.size_format( $memory ).'</span>', 'https://docs.wp-universam.ru/document/increasing-the-wordpress-memory-limit' );
					else 
						echo '<span class="item_status_valid item_status">' . size_format( $memory ) . '</span>';
					?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Состояние мультисайта WordPress', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php if ( is_multisite() ) echo __('Включен', 'usam'); else echo __('Выключен', 'usam'); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Информация Веб Сервера', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] ); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Режим отладки Wordpress', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo defined('WP_DEBUG') && WP_DEBUG?'<span class="item_status_attention item_status">'.__('Да', 'usam').'</span>':'<span class="item_status_valid item_status">'.__('Нет', 'usam').'</span>'; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Режим разработчика тем', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo defined('USAM_DEBUG_THEME') && USAM_DEBUG_THEME?'<span class="item_status_attention item_status">'.__('Да', 'usam').'</span>':'<span class="item_status_valid item_status">'.__('Нет', 'usam').'</span>'; ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Язык Wordpress', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php if ( defined( 'WPLANG' ) && WPLANG ) echo WPLANG; else  _e( 'Default', 'usam'); ?></div>
			</div>
			<?php if ( function_exists( 'ini_get' ) ) : ?>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Максимальный входной PHP Vars', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo ini_get('max_input_vars') ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'SUHOSIN Installed', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo extension_loaded( 'suhosin' ) ? __('Да', 'usam') : __('No', 'usam'); ?></div>
			</div>			
		<?php endif; ?>			
		</div>
		<?php	
	}
		
	function box_showcase( )
	{			
		?>				
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e('Товары', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo usam_return_details( 'products' ); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e('Варриации товаров', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo usam_return_details( 'product_variations' ); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e('Изображения товаров', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo usam_return_details( 'images' ); ?></div>
			</div>			
		</div>
		<?php 
	}	
}