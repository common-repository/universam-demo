<h3 class ="products_items__nothing_found" v-if="reputation_items.length"><?php _e('Ваши товары обсуждают в интернете','usam'); ?></h3>
<div class="products_items">
	<div class ="products_items__product" v-for="(item, k) in reputation_items">							
		<div class ="products_items__thumbnail">
			<div class="product_image image_container"><img :src="item.foto_url"></div>
			<div class ="products_items__info">
				<div class ="products_items__author" :title="item.author">
					<span class="svg_icon"><svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+item.source"></use></svg></span>
				</div>	
				<div class ="products_items__like_comment_box">
					<div class ="products_items__like_comment products_items__like"><span class="dashicons dashicons-heart"></span><span class="counter">{{item.likes}}</span></div>
					<div class ="products_items__like_comment products_items__comment"><span class="dashicons dashicons-admin-comments"></span><span class="counter">{{item.comments}}</span></div>
				</div>	
			</div>
		</div>		
		<div class ="products_items__buttons">
			<div class ="products_items__status products_items__status_published" v-if="item.status" @click="reputationItemUpdate(k, {status:0})"><?php _e("Опубликовано","usam"); ?></div>
			<div class ="products_items__status" v-else @click="reputationItemUpdate(k, {status:1})"><?php _e("Не опубликовано","usam"); ?></div>
			<span class="products_items__remove dashicons dashicons-no-alt" @click="reputationItemActionDelete(k)"></span>
		</div>	
	</div>
	<span class ="products_items__nothing_found" v-if="!reputation_items.length"><?php _e('Не найдено','usam'); ?></span>
</div>	