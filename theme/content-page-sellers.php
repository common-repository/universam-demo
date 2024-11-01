<?php
// Описание: Шаблон страницы бренды

require_once(USAM_FILE_PATH.'/includes/marketplace/sellers_query.class.php');
$sellers = usam_get_sellers(['cache_thumbnail' => true]);	
if ( !empty($sellers) )
{ 
	?>
	<div class="sellers">
		<?php
		foreach($sellers as $seller) 
		{	
			$link = usam_get_seller_link( $seller->id, 'products' );			
			?>
			<div class="sellers__item column4">		
				<div class="sellers__item_image">
					<a href='<?php echo $link ?>' class='sellers__item_image_link'><img src="<?php echo usam_get_logo_seller( $seller ) ?>" alt ="<?php echo $seller->name ?>"></a>					
				</div>	
				<div class="sellers__item_content">						
					<div class="sellers__item_content_favorites"><?php 
						$class = usam_checks_seller_from_customer_list( 'desired', $seller->id )?'list_selected':''; 
						usam_svg_icon( 'favorites', ['class' => 'desired_seller2 js-seller-list '.$class, 'sellerlist' => 'desired', 'seller' => $seller->id] ); 
					?></div>	
					<a href='<?php echo $link ?>' class='sellers__item_content_title'><?php echo $seller->name ?></a>
					<div class="sellers__item_content_rating"><?php usam_svg_icon('star-selected'); ?><span class="sellers__item_content_rating_message"><?php printf( __('%s из 5 рейтинг товаров', 'usam'), $seller->rating ) ?></span></div>					
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
		<div class="empty_page__title"><?php  _e('Сейчас нет продавцов', 'usam'); ?></div>		
	</div>
	<?php
}
?>