<?php usam_include_template_file('list-empty', 'template-parts'); ?>
<div class = 'lists' :class="{'list_loading':request}">
	<div class = 'file list' v-for="(item, k) in items">
		<div class="file_header">
			<div class="file_header__title">
				<a class="file_header__title_file" href="'<?php home_url('file/')?>'+item.code" target="_blank" v-html="item.title"></a>			
				<span class="file_header__title_date"><?php _e('от','usam'); ?> {{localDate(item.date_insert,'d.m.Y')}}</span>
			</div>				
		</div>
	</div>
	<paginated-list @change="page=$event" :page="page" :count='count'></paginated-list>
</div>
<?php