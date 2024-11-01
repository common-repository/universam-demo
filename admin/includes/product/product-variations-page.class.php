<?php
// Класс страницы вариантов товара. Здесь сохранения вариантов товара
 
class USAM_Product_Variations_Page
{
	private $parent_id;
	private $current_tab = 'manage';

	public function __construct() 
	{		
		$GLOBALS['hook_suffix'] = 'usam-product-variations-iframe';	
		$this->parent_id = absint( $_REQUEST['product_id'] );
		set_current_screen();

		if ( !empty( $_REQUEST['tab'] ) )
			$this->current_tab = sanitize_title($_REQUEST['tab']);
		
		add_action( 'admin_init', array( $this, 'update_variations'), 50 );
	}
	
	function update_variations() 
	{ 
		if ( !empty($_POST["product_id"]) )
		{
			$product_id = absint( $_POST["product_id"] );			
			
			$post_data = array( );
			$post_data['variations'] = isset($_POST['edit_var_val'] ) ? $_POST["edit_var_val"] : array();	
			usam_edit_product_variations( $product_id, $post_data );
		}
	}

	public function display() 
	{
		global $title, $hook_suffix, $current_screen, $wp_locale, $wp_version, $is_iphone, $current_site, $update_title, $total_update_count, $parent_file;

		$current_screen = get_current_screen();
		$admin_body_class = $hook_suffix;
		$post_type_object = get_post_type_object( 'usam-product' );

		wp_enqueue_style( 'global' );
		wp_enqueue_style( 'wp-admin' );
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'ie'     );
		wp_enqueue_script( 'common'       );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_script( 'utils'        );
		wp_enqueue_script( 'jquery-query' );

		$callback = "callback_tab_{$this->current_tab}";	
		if ( method_exists($this, $callback) )
			$this->$callback( );

		@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
		require_once( USAM_FILE_PATH . "/admin/includes/product/product-variations.page.php" );
	}
	
	private function callback_tab_setup()
	{
		$this->generate_variations();
	}
	
	public function display_current_tab()
	{ 
		require_once( USAM_FILE_PATH . "/admin/includes/product/product-variations-{$this->current_tab}.page.php" );
	}

	private function display_tabs() 
	{
		$tabs = array(
			'manage'   => _x( 'Управление', 'manage product variations', 'usam'),
			'setup'    => __('Установки', 'usam'),
		);
		echo '<ul class="usam-product-variations-tabs">';
		foreach ( $tabs as $tab => $title ) {
			$class = ( $tab == $this->current_tab ) ? ' class="active"' : '';
			$item = '<li' . $class . '>';
			$item .= '<a href="' . add_query_arg( 'tab', $tab ) . '">' . esc_html( $title ) . '</a></li>';
			echo $item;
		}
		echo '</ul>';
	}
	
// Генерация вариаций
	private function generate_variations() 
	{
		if ( ! isset($_REQUEST['action2'] ) || $_REQUEST['action2'] != 'generate' )
			return;

		check_admin_referer( 'usam_generate_product_variations', '_usam_generate_product_variations_nonce' );
		$this->update_variations();
		$sendback = remove_query_arg( array( '_wp_http_referer', 'updated',	) );
		wp_redirect( add_query_arg( 'tab', 'manage', $sendback ) );
		exit;
	}
}