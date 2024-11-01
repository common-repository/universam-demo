<?php
/*
 * Панель настройки Виджет
 * Добавляет Виджеты
 */
 
new USAM_Dashboard_Widget();
class USAM_Dashboard_Widget
{
	function __construct( ) 
	{	
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget_setup') );		
	}
	
	function dashboard_widget_setup()
	{
		if ( current_user_can('read_product') )
			wp_add_dashboard_widget( 'usam_dashboard_popularity_widget', __('Самые популярные товары', 'usam'), array( $this, 'dashboard_popularity_widget') );			
	}
	
	/*
	 * Виджет приборной панели показывающий популярность
	 */
	function dashboard_popularity_widget()
	{		
		$max_product = 10; //Количество товаров, для вывода			
		$query_vars = array('posts_per_page' => $max_product, 'post_parent' => 0, 'post_type' => "usam-product", 'post_status' => array('publish'), 'orderby' => 'views', 'order' => 'DESC' );	
		$product_query = new WP_Query( $query_vars );
		ob_start();
		?>
		<div style="padding-bottom:15px; "><?php echo $max_product,' '; esc_html_e( ' самых популярных товаров', 'usam'); ?>:</div>    
		<table style="width:100%" border="0" cellspacing="0">   
			<tr height="20">	
				<td width="20" style="font-weight:bold; color:#008080; border-bottom:solid 1px #000;">№</td>			
				<td style="border-bottom:solid 1px #000;width:60px"><?php _e('Имя продукта','usam'); ?></td>
				<td style="border-bottom:solid 1px #000;width:60px"><?php _e('Просмотры','usam'); ?></td>			
			</tr>
			<?php		
			if (!empty($product_query)) 
			{
				$number = 0;
				global $post;		
				while ($product_query->have_posts())
				{ 
					$product_query->the_post();
					$number++;
					?>
					<tr height="20">	
						<td width="20" style="font-weight:bold; color:#008080; border-bottom:solid 1px #000;"><?php echo $number; ?></td>			
						<td style="border-bottom:solid 1px #000;width:60px"><a href="<?php echo usam_product_url( $post->ID ); ?>"><?php echo $post->post_title; ?></a></td>
						<td style="border-bottom:solid 1px #000;width:60px"><?php echo usam_get_post_meta($post->ID, 'views' ); ?></td>			
					</tr>
				<?php
				}
			} ?>
		</table>
		<?php
		ob_end_flush();
	}
}