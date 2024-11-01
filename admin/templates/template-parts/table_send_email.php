<table class ='table_send_email'>
	<tr>
		<td class ='table_send_email__name'><?php _e('От кого', 'usam'); ?>:</td>
		<td>
			<select v-model="to">			
				<option v-for="(item, i) in mailboxes" :value='item.id'>{{item.name}} ({{item.email}})</option>
			</select>
		</td>
	</tr>			
	<tr>
		<td class ='table_send_email__name'><?php _e('Кому', 'usam'); ?>:</td>
		<td>
			<select v-model="from" v-if="Object.keys(emails).length">			
				<option v-for="(display, email) in emails" :value='email'>{{display}}</option>
			</select>
			<input v-else type='text' v-model="from">
		</td>
	</tr>		
	<tr>
		<td class ='table_send_email__name'><?php _e('Тема', 'usam'); ?>:</td>
		<td><input type='text' v-model="subject" placeholder="Введите тему сообщения"></td>
	</tr>			
	<tr>
		<td colspan='2'>					
			<tinymce v-model="message"></tinymce>
			<?php 						
			add_action('admin_footer', function() { 
				if ( empty($_REQUEST['tinymce_scripts_loaded']) )
				{
					add_editor_style( USAM_URL . '/admin/assets/css/email-editor-style.css' );	
					require_once ABSPATH . '/wp-includes/class-wp-editor.php';		
					_WP_Editors::print_tinymce_scripts();	
				}
			});						
			?>
		</td>
	</tr>			
	<tr>
		<td colspan='2' class="table_send_email__tabs_row">	
			<div class ="table_send_email__tabs">
				<div class ="table_send_email__tab" v-show="!files.length" @click="tab=(tab=='file'?'':'file')"><span class="dashicons dashicons-paperclip"></span><?php _e('Файлы', 'usam'); ?></div>
				<div class ="table_send_email__tab" @click="tab=(tab=='signature'?'':'signature')" v-if="signatures.length"><?php _e('Подписи', 'usam'); ?></div>
			</div>
			<div class="table_send_email__tab_content" :class="{'active':tab=='file' || files.length}">
				<?php 
				$change = true;
				require( USAM_FILE_PATH ."/admin/templates/template-parts/attachments.php"); ?>
			</div>
			<div class="table_send_email__tab_content signatures" :class="{'active':tab=='signature'}">
				<div class="pointer" v-for="(signature, i) in signatures" @click="addSignature(i)">{{signature.name}}</div>
			</div>
		</td>
	</tr>
	<tr>
		<td colspan='2' class="table_send_email__buttons">
			<button class="button button-primary" @click="send"><?php _e( 'Отправить', 'usam'); ?></button>	
			<a @click="$parent.show=false"><?php _e( 'Отменить', 'usam'); ?></a>
		</td>
	</tr>
</table>