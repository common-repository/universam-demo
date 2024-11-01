<div class = 'by_categories_list usam_products'>
	<?php
	if ( !empty($title_products_for_buyers) )
	{ ?>
		<h3 class='prodtitles'><span><?php echo $title_products_for_buyers ?></span></h3>
		<?php
	}
	?>
	<div class='usam_tabs'>
		<?php 
		$products_ids = array();
		while (have_posts()) :  	
			the_post(); 
			$products_ids[] = get_the_ID();				
		endwhile; 
		$terms = wp_get_object_terms( $products_ids, 'usam-category', ['fields' => 'all_with_object_id', 'orderby' => 'name', 'update_term_meta_cache' => false]);
		$categories = [];	
		?>
		<div class="header_tab by_categories_list__categories">	
			<?php
			$object_ids = array();
			$i = 0;
			foreach ( $terms as $term ) 
			{			
				if ( !in_array( $term->term_id, $categories) && $i != 5 )
				{
					?><a href='#by_categories_list__category_tab-<?php echo $term->term_id; ?>'  class="tab by_categories_list__category"><?php echo $term->name; ?></a><?php
					$i++;					
				}
				$categories[] = $term->term_id;
				$object_ids[$term->term_id][] = $term->object_id;
			}
			?>
		</div>
		<div class = "countent_tabs">		
			<?php
			global $product_limit;
			$product_limit = 5;
			foreach ( $object_ids as $term_id => $products_ids ) 
			{	
				?>
				<div id = "by_categories_list__category_tab-<?php echo $term_id; ?>" class = "tab by_categories_list__category_tab">
					<div class = "products_grid">
						<?php
						while (have_posts()) : usam_the_product(); 
							$id = get_the_ID();							
							if ( in_array($id, $products_ids) )
							{
								include( usam_get_template_file_path( 'grid_product' ) );
							}
						endwhile; 					
						?>
					</div>
				</div>
				<?php 
			}
			?>
		</div>
	</div>	
</div>	