<template v-slot:button><a @click="sidebar('contacts')"><?php _e( 'Добавить', 'usam'); ?></a></template>
<template v-slot:body>
	<div class='contacts object_column' v-if="contacts.length">
		<div class='user_block' v-for="(item, k) in contacts">	
			<div class='user_foto'><a :href="item.url" class='image_container usam_foto'><img :src='item.foto'></a></div>	
			<a class='user_name':href="item.url" v-html="item.appeal"></a>	
			<a class="dashicons dashicons-no-alt delete_action" @click="deleteContact(k)"></a>
		</div>
	</div>
	<div class='no_items' v-else><?php _e( 'Не выбраны', 'usam'); ?></div>
</template>
<?php
usam_vue_module('list-table');
add_action('usam_after_edit_form',function() {
	require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-contacts.php' );
});
?>