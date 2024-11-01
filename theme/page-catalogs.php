<?php
// Описание: Шаблон каталогов

get_header( 'shop' );
usam_output_breadcrumbs(); 
$title = apply_filters( 'usam_the_title', get_the_title(), 'title' );
?>
<h1 class="title"><?php echo $title; ?></h1>
<?php
do_action( 'usam_before_main_content' ); 
$terms = get_terms(['orderby' => 'sort', 'status' => 'publish', 'taxonomy' => 'usam-catalog']);	
if ( !empty($terms) )
{ 
	usam_update_terms_thumbnail_cache( $terms );
	?>
	<div class="catalogs list_terms">
		<?php
		foreach($terms as $term) 
		{	
			$term_link = get_term_link($term->term_id, 'usam-catalog');
			?>
			<div class="list_terms__term column4">		
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
	_e('Сейчас нет каталогов для вас','usam');
}
do_action( 'usam_after_main_content' ); 
get_footer( 'shop' );
?>