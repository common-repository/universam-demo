<?php 
global $post; 
$product_id = $post->ID;
$title = get_the_title( $product_id );
$price = usam_get_product_price_currency( $product_id );	
$old_price = usam_get_product_price_currency( $product_id, true );
?>
<div class="product"> 			 
	<a class = "product_link" href="<?php echo usam_product_url( ); ?>" title="<?php echo $title; ?>" style = "text-decoration: none; color: #444444;">	
		<div class="image_box" style="text-align: center;">									
			<?php echo usam_get_product_thumbnail($product_id, array(160, 160), $title, false); ?>
		</div>							
		<div  class="ptitle" style="text-align: center;">								
			<p class="title"><?php echo $title; ?></p>				
			<p class="all_price">
				<span class="price" style ="color:#FF6347"><?php echo $price; ?></span>
				<span class="sale" style ="text-decoration:line-through"><?php echo $old_price; ?></span>
			</p>						
		</div>	
	</a>	
</div>			