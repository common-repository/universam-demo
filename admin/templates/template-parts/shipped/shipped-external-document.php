<div class="edit_form">
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Номер документа', 'usam'); ?>:</div>
		<div class ="edit_form__item_option">				
			<input type="text" v-model="data.external_document" v-if="edit">
			<span v-else v-html="data.external_document"></span>		
		</div>
	</div>
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Дата документа', 'usam'); ?>:</div>
		<div class ="edit_form__item_option" v-if="edit">
			<datetime-picker v-model="data.external_document_date"/>
		</div>
		<div class ="edit_form__item_option" v-else>{{localDate(data.external_document_date,'<?php echo get_option('date_format', 'Y/m/j'); ?>')}}</div>
	</div>
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Номер отслеживания', 'usam'); ?>:</div>
		<div class ="edit_form__item_option" v-if="edit">
			<input type="text" v-model="data.track_id">
		</div>	
		<div class ="edit_form__item_option" v-else>			
			<a title='<?php _e( 'Посмотреть историю почтового отправления', 'usam'); ?>' v-if="data.method!==''" :href="'<?php echo add_query_arg(['form' => 'view', 'form_name' => 'tracking']); ?>'+'&id='+data.id">{{data.track_id}}</a>
			<span v-else>{{data.track_id}}</span>					
		</div>		
	</div>	
	<div class ="edit_form__item" v-if='data.exchange!==false'>
		<div class ="edit_form__item_name"><?php _e( 'Статус выгрузки', 'usam'); ?>:</div>
		<div class ="edit_form__item_option">
			<span class='item_status_valid item_status' v-if='data.exchange==1'><?php _e( 'выгружен', 'usam'); ?></span>
			<span class='item_status_attention item_status' v-else><?php _e( 'не выгружен', 'usam'); ?></span>						
		</div>	
	</div>			
</div>