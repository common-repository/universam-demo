<?php
/*
Template Name: Шаблон вывода акции
*/
?>
<?php 

get_header( 'shop' );
do_action( 'usam_before_main_content' );
global $wp_query, $usam_query; 
if ( isset($wp_query->query['usam-category_sale']) )
{  
	$term = get_term_by('slug', $wp_query->query['usam-category_sale'], 'usam-category_sale');	
	if ( !empty($term->description) )
	{		
		?>
		<div class='product_category_sale_description'>		
			<div class='product_category_sale_description__text'><?php echo do_shortcode( nl2br($term->description) ); ?></div>
		</div>
		<?php	
	}
} 	
if( !empty($usam_query->post) )
{	
	?>
	<div class="product_list_columns">
		<div class="usam_product_display">
			<?php usam_load_template("content-page-products"); ?>
		</div>
		<div class = "sidebar"><?php dynamic_sidebar('product'); ?></div>
	</div>
	<?php
}
do_action( 'usam_after_main_content' );
get_footer( 'shop' );