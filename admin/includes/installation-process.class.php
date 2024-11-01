<?php
/**
 *  Процесс установки
 */
class USAM_Installation_Process
{	
	public function __construct()
	{ 
		add_action('admin_menu', array( $this, 'admin_menus') );
		add_action('admin_init', array( $this, 'display') );		
	}	
	
	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'usam-install', '' );
	}
	
	public function display()
	{	
		set_current_screen( 'usam_installation_process' );	
		wp_enqueue_style( 'usam-install', USAM_URL . '/admin/assets/css/install.css', [], USAM_VERSION_ASSETS );
		
		ob_start( );
			
		$this->content();
				
		exit;
	}
	
	public function content() 
	{
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'Установка платформы УНИВЕРСАМ', 'usam'); ?></title>			
			<?php wp_print_scripts( 'usam_setup' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_print_scripts' ); ?>	
			<script>
			var ajaxurl = "<?php echo get_home_url(); ?>";
			</script>
		</head>
		<body class="wp-core-ui">
			<h1>UNIVERSAM</h1>
			<h2><?php esc_html_e( 'Операционная система для вашего бизнеса', 'usam'); ?></h2>
			<div class="title install"><?php esc_html_e('Идет установка, подождите пару минут...', 'usam'); ?></div>
			<div class="title welcome"><?php esc_html_e('Добро пожаловать!', 'usam'); ?></div>	
			<?php do_action("admin_footer", 'usam_setup_wizard'); ?>
			<?php do_action('admin_print_footer_scripts'); ?>		
			<script>
				jQuery.get(ajaxurl, {install: 'start'}, (r) => {							
					document.querySelector('body').classList.add('ready');
					setTimeout(() => window.location.replace('<?php echo admin_url("admin.php?page=usam-setup") ?>'), 2000);
				}, 'json');
			</script>
			</body>
		</html>
		<?php
	}
}
new USAM_Installation_Process();
?>