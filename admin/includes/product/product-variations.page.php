<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php esc_html_e( 'Управление вариациями товара', 'usam'); ?></title>
<script>
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
var 
	ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>',
	pagenow = '<?php echo $current_screen->id; ?>',
	typenow = '<?php echo $current_screen->post_type; ?>',
	adminpage = '<?php echo $admin_body_class; ?>';
</script>
<?php
	do_action('admin_enqueue_scripts', $hook_suffix);
	do_action("admin_print_styles-$hook_suffix");
	do_action('admin_print_styles');
	do_action("admin_print_scripts-$hook_suffix");
	do_action('admin_print_scripts');
	do_action("admin_head-$hook_suffix");
	do_action('admin_head');
?>
<style>
	html { background-color:transparent; }
	body { background: #ffffff; }
</style>
</head>
<body class="no-js wp-admin wp-core-ui usam-product-variation-iframe">
	<script>document.body.className = document.body.className.replace('no-js','js');</script>
	<div id="product_variations">
		<?php $this->display_tabs(); ?>
		<div class="usam-product-variations-tab-content">
			<?php $this->display_current_tab(); ?>
		</div>		
	</div>	
	<?php
	do_action('admin_print_footer_scripts');
	do_action("admin_footer-" . $GLOBALS['hook_suffix']);
	?>
	<script>if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>