<?php
// Описание: Шаблон каталогов

get_header( 'shop' );
usam_output_breadcrumbs(); 
$title = apply_filters( 'usam_the_title', get_the_title(), 'title' );
?>
<h1 class="title"><?php echo $title; ?></h1>
<?php
do_action( 'usam_before_main_content' ); 
$terms = get_terms(['orderby' => 'sort', 'status' => 'publish', 'taxonomy' => 'usam-brands']);	
if ( !empty($terms) )
{ 
	usam_update_terms_thumbnail_cache( $terms );	
	?>
	<div class="brands list_terms">
		<?php
		foreach($terms as $term) 
		{	
			$term_link = get_term_link($term->term_id, 'usam-brands');
			?>
			<div class="list_terms__term column7">		
				<div class="list_terms__image">
					<a href='<?php echo $term_link ?>' class='list_terms__image_wrap image_container'><?php echo usam_term_image($term->term_id, 'full', ['alt' => $term->name] ) ?></a>
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
	?>
	<div class="empty_page">
		<div class="empty_page__icon"><?php usam_svg_icon('search') ?></div>
		<div class="empty_page__title"><?php  _e('Сейчас нет брендов для вас', 'usam'); ?></div>
		<div class="empty_page__description">
			<p><?php  _e('На нашем каталоге вы найдете много интересных товаров.', 'usam'); ?></p>
		</div>
		<a class = "button" href="<?php echo usam_get_url_system_page('products-list'); ?>"><?php _e('Посмотреть наши товары', 'usam'); ?></a>
	</div>
	<?php
}
do_action( 'usam_after_main_content' ); 
get_footer( 'shop' );
?>