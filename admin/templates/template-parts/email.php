<div class = "letter">
	<div class = "letter_header">
	<?php	
		/*if ( $email['type'] == 'inbox_letter' )
			$date =  $email['date_insert'];
		elseif ( !empty($email['sent_at']) ) 	
			$date =  $email['sent_at'];
		else
			$date =  $email['date_insert'];		*/
		?>
		<div class = "letter_header__row letter_header__title">
			<div class = "letter_header__subject" v-html="data.title"></div>
			<div class = "letter_header__importance">
				<span class="dashicons" @click="update({importance:!data.importance})" :class="[data.importance?'dashicons-star-filled important':'dashicons-star-empty']"></span>
			</div>
		</div>
		<div class = "letter_header__row letter_header__from">
			<div class = "letter_header__contact letter_header__from_contact">
				<div class = "letter_header__label"><?php _e('От', 'usam') ?>:</div>
				<div class = "letter_header__email">
					<div class='crm_customer'>
						<a><span v-show="data.from_name">{{data.from_name}} - <span>{{data.from_email}}</a>
						<div class='crm_customer__info' v-if="false">
							<div class='crm_customer__info_rows' v-if="data.from.object_type=='contact'">
								<div class='crm_customer__info_row'>
									<div class="crm_customer__info_row_name"><?php _e("В базе","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{localDate(data.from.date_insert, '<?php echo get_option('date_format', 'Y/m/j'); ?> H:i' )}}</div>
								</div>
								<div class='crm_customer__info_row'>
									<div class="crm_customer__info_row_name"><?php _e("Статус","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.status}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.mobilephone">
									<div class="crm_customer__info_row_name"><?php _e("Телефон","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.mobilephone}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.site">
									<div class="crm_customer__info_row_name"><?php _e("Сайт","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.site}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.manager_id">
									<div class="crm_customer__info_row_name"><?php _e("Ответственный менеджер","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.manager.appeal}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.full_location_name!==undefined">
									<div class="crm_customer__info_row_name"><?php _e("Город","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.full_location_name}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.location_time!==undefined">
									<div class="crm_customer__info_row_name"><?php _e("Текущее время","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.location_time}}</div>									
								</div>		
								<div class='crm_customer__info_row' v-if="data.from.groups.length">
									<div class="crm_customer__info_row_name"><?php _e("Группа","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.groups.join(',')}}</div>
								</div>		
								<div class='crm_customer__info_row' v-if="Object.keys(data.from.company).length">
									<div class="crm_customer__info_row_name"><?php _e("Работает в компании","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.company.name}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="Object.keys(data.from.company).length && data.from.company.post">
									<div class="crm_customer__info_row_name"><?php _e("Должность","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.company.post}}</div>
								</div>															
								<div class='crm_customer__info_row' v-if="Object.keys(data.from.company).length && data.from.company.site">
									<div class="crm_customer__info_row_name"><?php _e("Сайт компании","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.company.site}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="Object.keys(data.from.company).length && data.from.company.groups.length">
									<div class="crm_customer__info_row_name"><?php _e("Группа компании","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.company.groups.join(',')}}</div>
								</div>								
							</div>
							<div class='crm_customer__info_rows' v-else>
								<div class='crm_customer__info_row'>
									<div class="crm_customer__info_row_name"><?php _e("В базе","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{localDate(data.from.date_insert, '<?php echo get_option('date_format', 'Y/m/j'); ?> H:i' )}}</div>
								</div>
								<div class='crm_customer__info_row'>
									<div class="crm_customer__info_row_name"><?php _e("Статус","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.status}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.site">
									<div class="crm_customer__info_row_name"><?php _e("Сайт","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.site}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.full_location_name!==undefined">
									<div class="crm_customer__info_row_name"><?php _e("Город","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.full_location_name}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.location_time!==undefined">
									<div class="crm_customer__info_row_name"><?php _e("Текущее время","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.location_time}}</div>									
								</div>	
								<div class='crm_customer__info_row' v-if="data.from.manager_id">
									<div class="crm_customer__info_row_name"><?php _e("Ответственный менеджер","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.manager.appeal}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.site">
									<div class="crm_customer__info_row_name"><?php _e("Сайт компании","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.site}}</div>
								</div>
								<div class='crm_customer__info_row' v-if="data.from.groups.length">
									<div class="crm_customer__info_row_name"><?php _e("Группа компании","usam"); ?>:</div>
									<div class="crm_customer__info_row_option">{{data.from.groups.join(',')}}</div>
								</div>		
								
							</div>
						</div>
					</div>		
				</div>
			</div>
			<div class = "letter_header__date" v-if="data.type=='inbox_letter'">{{localDate(data.date_insert, '<?php echo get_option('date_format', 'Y/m/j'); ?> H:i' )}}</div>
		</div>		
		<div class = "letter_header__row letter_header__to">		
			<div class = "letter_header__contact letter_header__to_contact">
				<div class = "letter_header__label"><?php _e('Кому', 'usam') ?>:</div>
				<div class = "letter_header__email"><span v-show="data.from_name">{{data.to_name}} - <span>{{data.to_email}}</div>	
			</div>			
		</div>
		<div v-if="Object.keys(data.author).length && data.type=='sent_letter'" class = "letter_header__row letter_header__from">
			<div class = "letter_header__contact letter_header__from_contact">
				<div class = "letter_header__label"><?php _e('Отправитель', 'usam') ?>:</div>
				<div class = "letter_header__text">
					<a :href="'<?php echo admin_url("admin.php?page=crm&tab=contacts&form=view&form_name=contact"); ?>&id='+data.author.id" v-html="data.author.appeal"></a>
				</div>
			</div>		
		</div>
		<div v-if="Object.keys(data.author).length && data.type=='inbox_letter'" class = "letter_header__row letter_header__from">
			<div class = "letter_header__contact">
				<div class = "letter_header__label"><?php _e('Ответственный менеджер', 'usam') ?>:</div>
				<div class = "letter_header__text">
					<a :href="'<?php echo admin_url("admin.php?page=crm&tab=contacts&form=view&form_name=contact"); ?>&id='+data.author.id" v-html="data.author.appeal"></a>
				</div>
			</div>		
		</div>	
		<div class = "letter_header__row letter_header__to" v-if="data.copy_email">	
			<div class = "letter_header__contact letter_header__to_contact">
				<div class = "letter_header__label"><?php _e('Копия', 'usam') ?>:</div>
				<div class = "letter_header__text">{{data.copy_email}}</div>		
			</div>			
		</div>			
		<div v-if="data.objects.length">	
			<table class ="objects_table">
				<tbody>
					<tr v-for="type in data.objects"> //$object->object_type != 'email'
						<td class="object_type">{{object_names[type].single_name}}:</td>
						<td>
							<span class ="object_name" v-for="(item, i) in crm" v-if="item.object_type==type">
								<?php include( usam_get_filepath_admin('templates/template-parts/objects.php') ); ?>
							</span>								
						</td>
					<tr>
					<tr>
						<td></td>
						<td><a @click="sidebar('objects', 'links')"><?php _e('выбрать','usam'); ?></a></td>
					<tr>
				</tbody>
			</table>
		</div>
		<div v-if="data.attachments.length" class='usam_attachments images'>	
			<div class='usam_attachments__file' v-for="(file, i) in data.attachments">
				<div v-if="file.mime_type.startsWith('image/')" class='attachment_icon' :title="file.title"><img :src="file.icon"></div>
				<a v-else :href="'<?php echo get_bloginfo('url'); ?>/file/'+file.code" :title="file.title" target='_blank' rel='noopener'><div class='attachment_icon'><img :src="file.icon"></div></a>
				<div class='attachment__file_data'>
					<div class='filename'>{{get_attachment_title( file.title )}}</div>
					<div class='attachment__file_data__filesize'>
						<a download :href="'<?php echo get_bloginfo('url') ?>/file/'+file.code" title="<?php _e('Сохранить этот файл себе на компьютер','usam') ?>" target="_blank" rel="noopener"><?php _e('Скачать','usam'); ?></a>{{file.size}}
					</div>
				</div>
				</div>
		</div>
		<div class = "attachments_head" v-show="data.attachments.length">
			<span class="dashicons dashicons-paperclip"></span>
			<span class="attachments_head_count"><?php _e('файлов', 'usam'); ?> {{data.attachments.length}}</span>
			<span class="attachments_head_size">{{data.totalsize}}</span>
			<span class="attachments_head_download_all">
				<a class="usam-download_all-link" @click="download_all"><span class="dashicons dashicons-arrow-down-alt"></span><?php _e('Скачать все', 'usam') ?></a>
			</span>
		</div>
		<div v-if="data.folder!='attached' && data.related.length" class = "related_messages letter_header__row">
			<div class = "letter_header__allocated">
				<h4><?php _e('Уже есть ответ на это письмо', 'usam') ?>:</h4>
				<div class="related_messages__items">					
					<div class="related_messages__item" v-for="(item, i) in data.related">
						<a href='' class='attached_message'><span class='dashicons dashicons-undo'></span>&laquo;<strong>{{item.title}}&raquo;</strong></a><br><?php _e('Кому', 'usam'); ?>: <span v-if="item.to_name">{{item.to_name}} - </span><span class='js-copy-clipboard'>{{item.to_email}}</span>
					</div>
				</div>
			</div>
		</div>			
		<div v-if="data.folder!='attached'" class = "letter_buttons letter_header__row">
			<ul>			
				<li v-if="data.folder!='drafts' && data.type=='inbox_letter'" @click="reply"><span class="dashicons dashicons-undo"></span><?php _e('Ответить', 'usam') ?></li>
				<li v-if="data.folder!='drafts' && data.type!='inbox_letter'" @click="forward"><span class="dashicons dashicons-undo"></span><?php _e('Переслать', 'usam') ?></li>
				<li v-if="data.folder=='drafts'" @click="send"><span class="dashicons dashicons-arrow-left-alt"></span><?php _e('Отправить', 'usam') ?></li>
				<li v-if="data.folder=='drafts'" @click="edit"><span class="dashicons dashicons-edit"></span><?php _e('Изменить', 'usam') ?></li>
				<li v-if="data.type=='inbox_letter' && data.read" @click="update({read:0})"><span class="dashicons dashicons-email-alt"></span><?php _e('Не прочитано', 'usam') ?></li>
				<li v-else-if="data.type=='inbox_letter'" @click="update({read:1})"><span class="dashicons dashicons-email-alt"></span><?php _e('Прочитано', 'usam') ?></li>
				<li><a class="usam-email_print-link" target="_blank" :href="'<?php echo wp_nonce_url(add_query_arg(['action' => 'email_print', 'page' => 'feedback', 'tab' => 'email'], admin_url('admin.php') )); ?>&id='+data.id"><span class="dashicons dashicons-media-text"></span><?php _e('Распечатать', 'usam') ?></a></li>						
				<li @click="del"><span class="dashicons dashicons-no-alt"></span><?php _e('Удалить', 'usam') ?></li>
				<li v-if="false"><span class="dashicons dashicons-admin-links"></span><?php _e('Объект', 'usam') ?></li>
				<li><a class="usam-add_contact-link" v-if="false && data.contact!==undefined" href="" @click="addContact"><span class="dashicons dashicons-admin-links"></span><?php _e('Добавить в контакты', 'usam') ?></a></li>
			</ul>
		</div>
		<div v-if="data.attacheds.length" class = "related_messages letter_header__row">
			<div class = "letter_header__allocated">
				<h4><?php _e('Вложенные письма', 'usam') ?>:</h4>
				<div class="related_messages__items">					
					<div class="related_messages__item" v-for="(item, i) in data.attacheds">
						<a href='' class='attached_message'><span class='dashicons dashicons-paperclip'></span>&laquo;<strong>{{item.title}}&raquo;</strong></a><br><?php _e('Кому', 'usam'); ?>: <span v-if="item.to_name">{{item.to_name}} - </span><span class='js-copy-clipboard'>{{item.to_email}}</span>
					</div>
				</div>
			</div>
		</div>
	</div>	
	<div class="letter_message">
		<iframe ref="iframe" :srcdoc="data.body"></iframe>
	</div>
</div>