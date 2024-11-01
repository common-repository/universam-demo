<template v-slot:button>	
	<a @click="sidebar('managers')">					
		<span v-if="data.manager_id"><?php _e( 'Сменить', 'usam'); ?></span>
		<span v-else><?php _e( 'Выбрать', 'usam'); ?></span>
	</a>
</template>
<template v-slot:body>
	<div class='user_block' v-if="data.manager_id>0">	
		<div class='user_foto'><a :href="manager.url" class='image_container usam_foto'><img :src='manager.foto'></a></div>	
		<a class='user_data' :href="manager.url">
			<div class='user_name'>{{manager.appeal}}<span class='customer_online' v-if='manager.online'></span></div>
			<div class='user_post'>{{manager.post}}</div>		
		</a>		
	</div>
	<div class='no_selected' v-else><?php _e('Не выбран ответственный', 'usam'); ?> <a @click="addYourManager()"><?php _e('Выбрать себя', 'usam'); ?></a></div>
</template>
<?php
usam_vue_module('list-table');
add_action('usam_after_edit_form',function() {
	require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-employees.php' );
});
?>