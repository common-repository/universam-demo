<?php
require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
require_once( USAM_FILE_PATH.'/admin/includes/admin_query.class.php' );
require_once( USAM_FILE_PATH . '/admin/includes/grid_view.class.php' );
class USAM_sold_Grid_View extends USAM_Grid_View
{		
	protected $products_order = array();
	protected $sum = 0;	
	protected function prepare_items( ) 
	{
	//	$this->get_query_vars();					
		$this->query_vars['posts_per_page'] = 500;
		$this->query_vars['post_type'] = 'usam-product';
		if ( empty($this->query_vars['include']) )
		{			
			/*$selected = $this->get_filter_value( 'folder' );
			if ( $selected )
				$this->query_vars['folder_id'] = sanitize_title($selected);	
			else
				$this->query_vars['folder_id'] = 0;	*/
		} 		
		$query = new WP_Query( );
		$this->items = $query->query( $this->query_vars );
		usam_product_thumbnail_cache( $query );	
		$this->total_items = $query->found_posts;		
		
		$product_ids = array();
		foreach ( $this->items as $item )
		{
			$product_ids[] = $item->ID;
		}
		$products = usam_get_products_order_query( array('fields' => array('product_id','sum'), 'product_ids' => $product_ids, 'groupby' => 'product_id') );
		foreach ( $products as $product )
		{
			$this->products_order[$product->product_id] = $product->sum;
		}		
		$sum = usam_get_products_order_query( array('fields' => 'sum', 'groupby' => 'product_id', 'number' => 10, 'orderby' => 'sum', 'order' => 'DESC' ) );
		$this->sum = array_sum($sum)/count($sum);
	}
	
	public function display_grid() 
	{  			
		$level = $this->sum/3;
		$level2 = $level*2;		
		?>		
		<div class="grid_view_icons">			
			<?php 			
			$k = 3;
			foreach ( $this->items as $item )
			{	
				$sum = isset($this->products_order[$item->ID])?$this->products_order[$item->ID]:0;
				$sum = rand(5, $this->sum);
				if ( $level > $sum )
				{// красная зона
					$red = 11148834; 
					$p = $sum*100/($level*$k); 
					$color = $p > 50 ? $red+$p : $red-$p;
					$class = 'grid_item__color_green';
				}
				elseif( $level2 > $sum )
				{
					$yellow = 16767075;
					$p = $sum*100/($level2*$k); 
					$color = $p > 50 ? $yellow+$p : $yellow-$p;	
					$class = 'grid_item__color_yellow';					
				}
				else
				{
					$green = 1996090;
					$p = $sum*100/($this->sum*$k); 
					$color = $p > 50 ? $green+$p : $green-$p;
					$class = 'grid_item__color_green';
				}
				$color = dechex($color);					
				$view = usam_get_post_meta( $item->ID, 'views' );
				?>
				<div class="grid_item <?php echo $class; ?>">
					<div class="grid_item__icon">						
						<?php usam_product_thumbnail( $item->ID ); ?>
						<div class="grid_item__view"><?php echo $view; ?></div>
					</div>
					<div class="grid_item__name grid_item__color" style="background-color:#<?php echo $color; ?>;">
						<a href='<?php echo usam_product_url(); ?>'><?php echo get_the_title( $item->ID ); ?></a>
					</div>					
					<div class="grid_item__price"><?php echo usam_get_product_price_currency( $item->ID ); ?></div>
				</div>	
				<?php 
			}				
			?>
		</div>
		<?php 
	}	
}
?>