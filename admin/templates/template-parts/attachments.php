<div class="usam_attachments images"
	<?php
	if ( !empty($change) )
	{
		?> @drop="fDrop" @dragover="aDrop"<?php
	}
	echo isset($attr)?$attr:''; ?>		
>
	<div class='usam_attachments__file' v-for="(file, k) in files" :class="{'loading_error':file.error_message}" v-if="files.length">
		<input type="hidden" name="fileupload[]" v-model="file.id">
		<a class='usam_attachments__file_delete delete' @click="fDelete(k)"></a>							
		<div class='attachment_icon'>	
			<progress-circle v-if="file.load" :percent="file.percent"></progress-circle>
			<a v-else download="" :href="file.url" title="<?php _e('Сохранить этот файл себе на компьютер','usam'); ?>" target="_blank"><img :src='file.icon' alt="file.title"></a>	
		</div>
		<div class='attachment__file_data'>
			<div class='filename'><a download="" :href="file.url" title="<?php _e('Сохранить этот файл себе на компьютер','usam'); ?>" target="_blank">{{file.shortname}}</a></div>
			<div v-if="file.error_message" class='attachment__file_data__error'>{{file.error_message}}</div>
			<div v-else class='attachment__file_data__filesize'>{{file.size}}</div>			
		</div>
	</div>		
	<?php
	if ( !empty($change) )
	{
		?>
		<div class ='attachments_add' @click="fAttach">
			<div class ='attachments__placeholder' v-if="files.length==0">
				<div class="attachments__placeholder__text"><?php esc_html_e( 'Перетащите или нажмите, чтобы прикрепить файлы', 'usam'); ?></div>
				<div class="attachments__placeholder__select"><span class="dashicons dashicons-paperclip"></span><?php esc_html_e( 'Выбрать файлы', 'usam'); ?></div>
			</div>
		</div>
		<input type='file' @change="fChange" multiple>
		<?php
	}
	?>		
</div>