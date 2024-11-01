<div class="rows_data__title">
	<span v-if="data.type=='meeting'"><?php _e( 'Ответственный', 'usam'); ?></span>
	<span v-else><?php _e( 'Автор', 'usam'); ?></span>
	<a @click="sidebar('managers', 'employee')">		
		<span v-if="data.user_id"><?php _e( 'Сменить', 'usam'); ?></span>
		<span v-else><?php _e( 'Выбрать', 'usam'); ?></span>
	</a>
</div>	
<div class="rows_data__content">
	<div class='user_block' v-if="data.user_id>0">	
		<div class='user_foto'><a :href="author.url" class='image_container usam_foto'><img :src='author.foto'></a></div>	
		<a class='user_data' :href="author.url">
			<div class='user_name'>{{author.appeal}}<span class='customer_online' v-if='author.online'></span></div>
			<div class='user_post'>{{author.post}}</div>		
		</a>	
	</div>
	<div class='user_block' v-else><?php _e('Не выбран автор', 'usam'); ?></div>
</div>
<div class="rows_data__title" v-if="data.type!='meeting' && data.type!='call'">
	<span v-if="data.type=='project' || data.type=='closed_project'"><?php _e( 'Руководитель', 'usam'); ?></span>
	<span v-else-if="data.type=='convocation'"><?php _e( 'Секретарь', 'usam'); ?></span>
	<span v-else><?php _e( 'Ответственный', 'usam'); ?></span>
	<a @click="sidebar('managers', 'responsible')">					
		<span v-if="data.user_id"><?php _e( 'Сменить', 'usam'); ?></span>
		<span v-else><?php _e( 'Выбрать', 'usam'); ?></span>
	</a>
</div>	
<div class="rows_data__content" v-if="data.type!='meeting' && data.type!='call'">
	<div class='user_block' v-if="data.responsible>0">	
		<div class='user_foto'><a :href="responsible.url" class='image_container usam_foto'><img :src='responsible.foto'></a></div>	
		<a class='user_data' :href="responsible.url">
			<div class='user_name'>{{responsible.appeal}}<span class='customer_online' v-if='responsible.online'></span></div>
			<div class='user_post'>{{responsible.post}}</div>		
		</a>		
		<span class="delete_item" @click="data.responsible=0"></span>								
	</div>
</div>								
<div class="rows_data__title">
	<span v-if="data.type=='project' || data.type=='closed_project' || data.type=='convocation' || data.type=='call' || data.type=='meeting'"><?php _e( 'Участники', 'usam'); ?></span>
	<span v-else><?php _e( 'Исполнители', 'usam'); ?></span>
	<a @click="sidebar('managers', 'participant')"><?php _e( 'Добавить', 'usam'); ?></a>
</div>		
<div class="rows_data__content users_block">
	<div class='user_block' v-for="(item, k) in users.participant">	
		<div class='user_foto'><a :href="item.url" class='image_container usam_foto'><img :src='item.foto'></a></div>	
		<a class='user_data' :href="item.url">
			<div class='user_name'>{{item.appeal}}<span class='customer_online' v-if='item.online'></span></div>
			<div class='user_post'>{{item.post}}</div>		
		</a>		
		<input type="hidden" name="participant[]" v-model="item.user_id"/>	
		<span class="delete_item" @click="users.participant.splice(k, 1)"></span>						
	</div>
</div>
<div class="rows_data__title" v-if="data.type!='meeting' && data.type!='call'">
	<?php _e( 'Наблюдатели', 'usam'); ?>
	<a @click="sidebar('managers', 'observer')"><?php _e( 'Добавить', 'usam'); ?></a>
</div>		
<div class="rows_data__content users_block" v-if="data.type!='meeting' && data.type!='call'">
	<div class='user_block' v-for="(item, k) in users.observer">	
		<div class='user_foto'><a :href="item.url" class='image_container usam_foto'><img :src='item.foto'></a></div>	
		<a class='user_data' :href="item.url">
			<div class='user_name'>{{item.appeal}}<span class='customer_online' v-if='item.online'></span></div>
			<div class='user_post'>{{item.post}}</div>		
		</a>
		<input type="hidden" name="observer[]" v-model="item.user_id"/>	
		<span class="delete_item" @click="users.observer.splice(k, 1)"></span>						
	</div>
</div>	
<?php
usam_vue_module('list-table');
add_action('usam_after_form',function() {
	include( usam_get_filepath_admin('templates/template-parts/modal-panel/modal-panel-employees.php') );
});
 ?>