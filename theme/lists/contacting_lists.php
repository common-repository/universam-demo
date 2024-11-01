<?php usam_include_template_file('list-empty', 'template-parts'); ?>
<div class = 'lists' :class="{'list_loading':request}">
	<div class = 'list contacting' v-for="(item, k) in items">
		<div class="list_header">
			<div class="list_header__title" @click="key=k">
				<span class="list_header__title_number"><?php _e('Обращение','usam'); ?> №{{item.id}}</span>
				<span class="list_header__title_date"><?php _e('от','usam'); ?> {{localDate(item.date_insert,'d.m.Y')}}</span>
			</div>		
			<span class='item_status contacting__status_name' :style="'background:'+item.status_color+';color:'+item.status_text_color" v-html="item.status_name"></span>
		</div>
		<div class="contacting__content" v-if="item.request_solution">
			<div class="contacting__content_title"><?php _e('Ответ на ваше обращение','usam'); ?></div>
			<div class="contacting__request_solution" v-html="item.request_solution"></div>
		</div>	
	</div>
	<paginated-list @change="page=$event" :page="page" :count='count'></paginated-list>
</div>
<?php
