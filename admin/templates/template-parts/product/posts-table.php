<?php
$columns = [
		'n'       => __('№', 'usam'),
		'title'   => __('Название поста', 'usam'),
		'date'    => __('Дата', 'usam'),
		'views'   => __('Просмотры', 'usam'),
		'tools'    => ''
	];
?>
<form-table :lists='posts' :edit='true' @change="posts=$event" @add="addPost" :table_name="'post'" :columns='<?php echo json_encode( $columns ); ?>'>
	<template v-slot:tbodyrow="slotProps">	
		<td class="column-title"><a :href="slotProps.item.edit_link" v-html="slotProps.item.post_title"></a></td>
		<td class="column-date">{{localDate(slotProps.item.post_modified,'<?php echo get_option('date_format', 'Y/m/j'); ?>')}}<input type="hidden" v-model="slotProps.item.ID" name="posts[]"></td>
		<td class="column-views">{{slotProps.item.views}}</td>		
	</template>
	<template v-slot:tautocomplete="slotProps">
		<autocomplete @change="slotProps.selectElement" :request="'posts'" :objectname="'post_title'" :clearselected='1' :none="'<?php _e('Нет данных','usam'); ?>'"></autocomplete>
	</template>
</form-table>