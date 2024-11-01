<?php   
/*
Шаблон списка отзывов клиента
*/ 
global $post;   
$count = count($reviews);   
if ( $count ) 
{ 	
	?>
	<div class="customer_reviews"> 
	<?php
	$business = usam_shop_requisites_shortcode();
	if ( $summary_rating )
	{				
		$properties = usam_get_properties(['type' => 'webform', 'fields' => 'code=>data', 'access' => true]);
		$ratings = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 );		
		unset($query['number']);
		unset($query['page']);
		$query['cache_meta'] = false;			
		$query['groupby'] = 'rating';
		$query['fields'] = ['rating', 'count'];		
		foreach (usam_get_customer_reviews( $query ) as $review) 
		{					
			if ( $review->rating )
				$ratings[$review->rating] = $review->count;	
		} 
		$sum = 0;
		$number_sum = 0;
		foreach ($ratings as $number => $sum_rating ) 
		{					
			$sum += $number*$sum_rating;
			$number_sum += $sum_rating;
		} 
		$average_rating = $number_sum ? round($sum/$number_sum,1) : 0;
		?>
		<div class="summary_rating">					
			<div class="summary_rating__general" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
				<span itemprop="itemReviewed" itemscope itemtype="https://schema.org/Organization">
					<meta itemprop="name" content="<?php echo $business['full_company_name']; ?>">
					<meta itemprop="address" content="<?php echo $business['full_legaladdress']; ?>"/>
					<meta itemprop="telephone" content="<?php echo $business['phone']; ?>">
				</span>
				<div class="summary_rating__general_stars"><?php echo usam_get_rating( $average_rating ) ?></div>
				<div class="summary_rating__general_quantity">
					<span class="summary_rating__general_review_count" itemprop="reviewCount"><?php echo $total_reviews ?></span> <?php echo _n('отзыв','отзывов',$total_reviews,'usam'); ?> | 
					<span class="summary_rating__general_quantity" itemprop="ratingValue"><?php echo $average_rating ?></span> <?php echo __('из','usam'); ?> 5
				</div>
			</div>			
			<div class="summary_rating__distribution">
				<?php			
				for ($i = count($ratings); $i>0; $i--)
				{
					?>
					<div class="summary_rating__distribution_item">  
						<div class="summary_rating__distribution_item_label"><?php echo $i.' '.__('звезд','usam'); ?></div>
						<div class="summary_rating__distribution_item_bar"><?php echo usam_get_rating( $i ); ?></div>
						<div class="summary_rating__distribution_item_value">(<?php echo $ratings[$i]; ?>)</div>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
	} 
	foreach ($reviews as $i => $review) 
	{                
		$hide_name = '';		
		$customer_name = '';		
		if ( isset($properties['name']) ) 
			$customer_name = usam_get_review_metadata( $review->id, 'webform_name' );	
		if ( isset($properties['firstname']) && !$customer_name ) 
			$customer_name = usam_get_review_metadata( $review->id, 'webform_firstname' );	
		
		if ( !$customer_name )
		{
			$customer_name = __('Анонимный','usam');
			$hide_name = 'hide';
		}		
		?>
		<div class="customer_reviews__review" itemprop="review" itemscope="" itemtype="http://schema.org/Review">
			<?php if ( $review->title ) { ?>
				<h2 itemprop="name" class="customer_reviews__review_name"><?php echo esc_html($review->title); ?></h2>	
			<?php } ?>
			<meta itemprop="datePublished" content="<?php echo date('Y-m-d', strtotime($review->date_insert)); ?>">
			<?php if ( $review->rating ) { ?>
				<div class="customer_reviews__review_rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
					<meta itemprop="worstRating" content = "1">
					<meta itemprop="bestRating" content = "5"/>
					<meta itemprop="ratingValue" content = "<?php echo $review->rating; ?>">
					<?php echo usam_get_rating($review->rating) ?>
				</div>
			<?php } ?>
			<div class="customer_reviews__review_date_author">
				<abbr class="dtreviewed"><?php echo date_i18n( get_option('date_format'), strtotime($review->date_insert)); ?></abbr>&nbsp;
				<span class="<?php echo $hide_name; ?>"><?php _e("от","usam"); ?></span>&nbsp;
				<span class="customer_reviews__review_author <?php echo $hide_name; ?>" itemprop="author" itemscope itemtype="http://schema.org/Person"><span itemprop="name"><?php echo $customer_name; ?></span></span>
			</div>			
			<?php $attachments = usam_get_review_attachments( $review->id ); ?>
			<?php if ( $attachments ) { ?>
				<div class="customer_reviews__media slides js-reviews-attachments">
					<?php foreach ($attachments as $attachment) { ?>
						<div class="customer_reviews__media_item">
							<img src="<?php echo usam_get_file_icon( $attachment->id ); ?>" alt="<?php echo $attachment->title; ?>" class="open_reviews_media_viewer">
						</div>
					<?php } ?>
				</div>
			<?php } ?>				
			<blockquote itemprop="reviewBody" class="description"><?php echo wp_unslash(nl2br($review->review_text)); ?></blockquote>
			<?php
			if ( empty($args['hide_response']) )
			{
				if( $review->review_response ) 
				{
					?><div class="customer_reviews__review_response">
						<div class="customer_reviews__review_response_title"><span><?php _e('Официальный ответ','usam'); ?>:</span></div>
						<div class="customer_reviews__review_response_text"><?php echo wp_unslash(nl2br($review->review_response)); ?></div>
						<div class="customer_reviews__review_response_signature"><span><?php _e('Команда сайта','usam'); ?></span></div>
					</div><?php
				}
			}						
			?>
			<span itemprop="itemReviewed" itemscope itemtype="https://schema.org/Organization">
				<meta itemprop="name" content="<?php echo $business['full_company_name']; ?>">
				<meta itemprop="address" content="<?php echo $business['full_legaladdress']; ?>"/>
				<meta itemprop="telephone" content="<?php echo $business['phone']; ?>">
			</span>	
		</div>
		<?php
	} 	
	?>
	</div>
	<?php
	if ( !empty($post) && $total_reviews > count($reviews) && empty($args['pagination']) )
	{
		?><div><a href="<?php echo untrailingslashit(usam_get_url_system_page('reviews'))."/".$post->ID; ?>" class="customer_reviews__show_all"><?php  _e('Показать все отзывы', 'usam'); ?><?php usam_svg_icon("arrow-down"); ?></a></div><?php
	}
}
else
{
	?>
	<div class="empty_page">
		<div class="empty_page__icon"><?php usam_svg_icon('star') ?></div>
		<div class="empty_page__title"><?php _e('Еще нет отзывов', 'usam'); ?></div>
		<div class="empty_page__description">
			<p><?php  _e('Не хотите оставить первый отзыв?', 'usam'); ?></p>
		</div>
	</div>
	<?php
} 
add_action('wp_footer', function() 
{ 
	include_once( usam_get_template_file_path( 'media-viewer', 'template-parts' ) );
});
?>