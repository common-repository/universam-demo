<?php usam_include_template_file('list-empty', 'template-parts'); ?>
<div class = 'lists' :class="{'list_loading':request}">
	<div class = 'notification list' v-for="(notification, k) in items">
		<div class="product_header">
			<div class="product_header__title">
				<span class="product_header__title_product" v-html="notification.title"></span>
				<span class="product_header__title_date"><?php _e('от','usam'); ?> {{localDate(notification.date_insert,'d.m.Y')}}</span>
			</div>				
		</div>
	</div>
	<paginated-list @change="page=$event" :page="page" :count='count'></paginated-list>
</div>
<?php