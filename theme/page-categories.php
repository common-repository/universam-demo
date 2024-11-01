<?php
// Описание: Шаблон категорий если в настройках указано отображать категории на странице Каталог

get_header( 'shop' );
usam_output_breadcrumbs(); 
global $usam_query;
$title = apply_filters( 'usam_the_title', get_the_title(), 'title' );
?>
<h1 class="title"><?php echo $title; ?></h1>
<?php
do_action( 'usam_before_main_content' ); 

$args = ['orderby' => 'sort', 'status' => 'publish', 'taxonomy' => 'usam-category'];
if ( !empty($usam_query->query['usam-category']) )
{
	$term = get_term_by('slug', $usam_query->query['usam-category'], 'usam-category');
	$args['parent'] = $term->term_id;	
}
else
	$args['parent'] = 0;
if ( get_option("usam_default_category", 'all') === 'brands-category-products' && get_query_var('usam-brands') )
{
	$category_args = $args;
	unset($category_args['parent']);
	$term = get_term_by('slug', get_query_var('usam-brands'), 'usam-brands');
	if ( isset($term->term_id) )
		$category_args['connection']['usam-brands'] = [ $term->term_id ];
	$category_args['fields'] = 'id=>slug';
	$terms = get_terms( $category_args );
	$args['include'] = [];
	foreach($terms as $term_id => $slug ) 
	{
		if ( $args['parent'] )
		{
			$childrens = get_term_children( $args['parent'], "usam-category" );	
			$args['include'] = array_merge( $childrens, $args['include'] );
		}
		else
		{
			$ancestors = usam_get_ancestors( $term_id );
			$args['include'][] = current($ancestors);
		}
	}
	$args['include'] = array_unique($args['include']);
}
$terms = get_terms( $args );	
if ( !empty($terms) )
{ 
	usam_update_terms_thumbnail_cache( $terms );
	?>
	<div class="categories list_terms">
		<?php		
		foreach($terms as $term) 
		{	
			$term_link = get_term_link($term->term_id, 'usam-category');			
			?>
			<div class="list_terms__term column3">		
				<div class="list_terms__image">
					<a href='<?php echo $term_link ?>' class="list_terms__image_wrap image_container"><?php echo usam_term_image($term->term_id, 'full', ['alt' => $term->name] ) ?></a>
				</div>
				<div class="list_terms__name">
					<a href='<?php echo $term_link ?>'><?php echo $term->name ?></a>		
				</div>	
			</div>
			<?php
		}
		?>
	</div>
	<?php
}
else
{
	_e('Сейчас нет категорий для вас','usam');
}
do_action( 'usam_after_main_content' ); 
get_footer( 'shop' );
?>