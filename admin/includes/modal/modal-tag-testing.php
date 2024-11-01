<modal-panel ref="modaltagtesting" :size="'50%'">
	<template v-slot:title><?php _e('Тестирование тегов', 'usam'); ?></template>
	<template v-slot:body>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Ссылка на карточку товара', 'usam'); ?>:</div>
				<div class ="edit_form__item_row">
					<input type="text" v-model="test_url">
					<span class="button" @click="testTags" v-if="test_url"><?php _e( 'Проверить', 'usam'); ?></span>
				</div>
			</div>			
		</div>
		<div class="tag_testing">
			<table class="table_options widefat striped" v-if="Object.keys(dataTags).length">
				<thead>
					<tr>					
						<th class="column_name"><?php _e( 'Название тега', 'usam'); ?></th>	
						<th class="column_tag"><?php _e( 'Результат', 'usam'); ?></th>					
					</tr>
				</thead>
				<tbody>
					<tr v-for="(tag, k) in data.tags" v-if="tag.tag">	
						<td class="column_name"><span class="item_status" v-html="tag.title" :class="[dataTags[k].validate?'item_status_valid':'item_status_attention']"></span></td>
						<td class="column_tag" v-html="dataTags[k].tag"></td>
					</tr>
				</tbody>	
			</table>
		</div>
	</template>
</modal-panel>	