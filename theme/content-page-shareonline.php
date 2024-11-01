<?php
// Описание: Шаблон списка акций на сайте

$terms = usam_get_category_sale(); 
$i = 1;
if ( !empty($terms) )
{ 
	foreach($terms as $term) 
	{
		$start_date = usam_get_term_metadata($term->term_id, 'start_date_stock');	
		$end_date = usam_get_term_metadata($term->term_id, 'end_date_stock');					
		
		if ( empty($start_date) )
			$start_date = date_i18n("d.m.Y");
		else
			$start_date = usam_local_date($start_date, "d.m.Y"); 
		if ( empty($end_date) )
			$time_stock = __('бессрочная', 'usam'); 
		else		
			$time_stock = $start_date.' - '.usam_local_date($end_date, "d.m.Y"); 
		
		$term_link = get_term_link($term->term_id, 'usam-category_sale');
		?>
		<div class="category_sale">				
			<a href='<?php echo $term_link ?>' class="category_sale__link"><?php usam_term_image($term->term_id ) ?></a>		
			<div class="category_sale__text">				
				<a href='<?php echo $term_link ?>' class="category_sale__name"><?php echo $term->name ?></a>
				<div class="category_sale__time_stock">
					<div class="category_sale__title"><?php _e('Срок акции','usam') ?></div>
					<div class="category_sale__time"><?php echo $time_stock; ?></div>
				</div>
			</div>
		</div>
		<?php
		$i++;
	}
}
else
{
	?>
	<div class="empty_page">
		<div class="empty_page__icon"><?php usam_svg_icon('search') ?></div>
		<div class="empty_page__title"><?php  _e('Сейчас нет акций', 'usam'); ?></div>
		<div class="empty_page__description">
			<p><?php  _e('На нашем каталоге вы найдете много интересных товаров.', 'usam'); ?></p>
		</div>
		<a class = "button" href="<?php echo usam_get_url_system_page('products-list'); ?>"><?php _e('Посмотреть наши товары', 'usam'); ?></a>
	</div>
	<?php
}
?>