<?php usam_include_template_file('list-empty', 'template-parts'); ?>
<div class = 'lists' :class="{'list_loading':request}">
	<div class = 'product' v-for="(product, k) in items">
		<div class="product_header">
			<div class="product_header__title">
				<span class="product_header__title_product" v-html="product.post_title" @click="id=product.ID"></span>
				<span class="product_header__title_date"><?php _e('от','usam'); ?> {{localDate(product.post_date,'d.m.Y')}}</span>
			</div>	
			<div class ="product__counters_buttons">	
				<div class ="product__counters">	
					<span class="product__button_favorites product__counter"><?php usam_svg_icon('favorites') ?><span>{{product.desired}}</span></span>
					<span class="product__button_favorites product__counter"><?php usam_svg_icon('basket') ?><span>{{product.basket}}</span></span>
				</div>	
				<div v-if="product.post_status=='draft'" class="item_status" :class="'status_'+product.post_status"><?php _e('Черновик','usam'); ?></div>
				<div v-else-if="product.post_status=='pending'" class="item_status" :class="'status_'+product.post_status"><?php _e('На модерации','usam'); ?></div>
				<div v-else-if="product.post_status=='rejected'" class="item_status" :class="'status_'+product.post_status"><?php _e('Отклонено','usam'); ?></div>				
				<div v-else-if="product.post_status=='publish'" class="item_status" :class="'status_'+product.post_status"><?php _e('Опубликовано','usam'); ?></div>
			</div>
		</div>
		<div class="product__image_content">
			<div class="product__image" @click="id=product.ID" v-if="product.images.length"><img v-for="image in product.images" v-if="image.thumbnail" :src='image.small_image'></div>
			<div class="product__image" @click="id=product.ID" v-else><img src='<?php echo usam_get_no_image_uploaded_file(); ?>'></div>
			<div class="product__content">						
				<div class="product__attributes product_attributes">
					<div class="product__attribute product_attribute_row" v-for="(property, k) in getPropertiesDisplay(product)" v-if="k<3">
						<div class="attribute_name" v-html="property.name+':'"></div>
						<?php usam_include_template_file('view-product-property', 'template-parts'); ?>
					</div>
				</div>
				<div class="product__content_right">	
					<div class="product__category_price">
						<span class="product__category" v-if="product.category.lenght" v-html="product.category[0].name"></span>
						<span class ="product__price" v-html="product.price_currency"></span>
					</div>	
					<div class=" action_menu">
						<div class="action_menu__title" @click="menuOpen(k)"><?php usam_svg_icon("menu-points")?></div>
						<div class="action_menu__content" v-if="menu!==null && menu===k">
							<div class="action_menu__button" v-if="product.post_status=='pending'" @click="setProperty(k, {post_status:'draft'}); menu=null"><?php _e('В черновик','usam'); ?></div>			
							<div class="action_menu__button" v-if="product.post_status=='draft'" @click="setProperty(k, {post_status:'pending'}); menu=null"><?php _e('Опубликовать','usam'); ?></div>				
							<div class="action_menu__button" v-if="product.post_status=='publish' || product.post_status=='pending'"><a :href="product.url"><?php _e('Просмотреть','usam'); ?></a></div>
							<div class="action_menu__button" @click="id=product.ID"><?php _e('Редактировать','usam'); ?></div>
							<div class="action_menu__button" @click="deleteProduct(k)"><?php _e('Удалить','usam'); ?></div>
						</div>
					</div>	
				</div>				
			</div>
		</div>
	</div>
	<paginated-list @change="page=$event" :page="page" :count='count'></paginated-list>
</div>
<?php
