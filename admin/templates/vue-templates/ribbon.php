<?php 
//<span class="comments__item_icon svg_icon svg_icon_sms"><svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?v=1693770595#'+item.event_type"></use></svg></span>
?>
<ribbon v-if="crm_type" ref="ribbon" :object_type="crm_type" :object_id="data.id" :contact="contact" inline-template>
	<div class="ribbon">
		<div class='ribbon_header'>
			<div class='ribbon_header__panel'>
				<div class='ribbon_buttons'>
					<div class='ribbon_button' :class="{'active':tab=='comment'}" @click="tab='comment'"><?php _e('Комментарий','usam'); ?></div>
					<div class='ribbon_button' :class="{'active':tab=='task'}" @click="tab='task'"><?php _e('Задача','usam'); ?></div>
					<div class='ribbon_button' :class="{'active':tab=='meeting'}" @click="tab='meeting'"><?php _e('Встреча','usam'); ?></div>				
					<div class='ribbon_button' @click="sidebar('sendemail')"><?php _e('Письмо','usam'); ?></div>
					<div class='ribbon_button' :class="{'active':tab=='sms'}" @click="tab='sms'">SMS</div>
					<div class='ribbon_button' :class="{'active':tab=='messenger'}" @click="tab='messenger'"><?php _e('Мессенджер','usam'); ?></div>				
				</div>
			</div>
			<div class='new_comment' v-if="tab=='comment'">
				<div class='post_comments'>
					<textarea class="event_comment_input event_input_text" @focus="showAddElement=true" placeholder="<?php _e('Напишите комментарий…','usam'); ?>" tabindex="1" dir="auto" v-model="message"></textarea>
				</div>
				<div class='new_element__buttons' v-show="showAddElement">
					<button type="button" class="button" @click="addComment" ref="add_comment_button"><?php _e( 'Добавить', 'usam'); ?></button>
					<a @click="showAddElement=!showAddElement"><?php _e( 'Отменить', 'usam'); ?></a>
				</div>	
			</div>
			<div class='new_comment' v-if="tab=='sms'">
				<?php if ( !usam_is_license_type('FREE') && !usam_is_license_type('LITE') ) { ?>
					<div class='post_comments'>
						<textarea class="event_comment_input event_input_text" @focus="showAddElement=true" placeholder="<?php _e('Напишите сообщение…','usam'); ?>" tabindex="1" dir="auto" v-model="sms.message"></textarea>
					</div>
					<div class="new_comment__property" v-show="showAddElement">
						<span class="new_comment__property_label"><?php _e( 'Телефон', 'usam'); ?>:</span> 
						<select v-model="sms.phone" v-if="Object.keys(contact.phones).length">			
							<option v-for="(display, value) in contact.phones" :value='value'>{{display}}</option>
						</select>
					</div>
					<div class='new_element__buttons' v-show="showAddElement">
						<button type="button" class="button" @click="addSMS" ref="add_comment_button"><?php _e( 'Отправить', 'usam'); ?></button>
						<a @click="showAddElement=!showAddElement"><?php _e( 'Отменить', 'usam'); ?></a>
					</div>					
				<?php } else { ?>
					<a href="https://wp-universam.ru/buy/" target="_blank"><?php _e( 'Нужно купить лицензию', 'usam'); ?></a>
				<?php } ?>
			</div>
			<div class='new_comment' v-if="tab=='messenger'">
				<?php if ( usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE') ) { ?>
					<div class='post_comments'>
						<textarea class="event_comment_input event_input_text" @focus="showAddElement=true" placeholder="<?php _e('Напишите сообщение…','usam'); ?>" tabindex="1" dir="auto" v-model="messenger.message"></textarea>
					</div>					
					<div class="new_comment__property" v-show="showAddElement">
						
					</div>
					<div class='new_element__buttons' v-show="showAddElement">
						<button type="button" class="button" @click="addMessenger" ref="add_comment_button"><?php _e( 'Отправить', 'usam'); ?></button>
						<a @click="showAddElement=!showAddElement"><?php _e( 'Отменить', 'usam'); ?></a>
					</div>					
				<?php } else { ?>
					<a href="https://wp-universam.ru/buy/" target="_blank"><?php _e( 'Нужно купить лицензию', 'usam'); ?></a>
				<?php } ?>
			</div>
			<div class='new_comment' v-else-if="tab=='task'">
				<div class='post_comments'>
					<textarea class="event_comment_input event_input_text" @focus="showAddElement=true" placeholder="<?php _e('Что нужно сделать…','usam'); ?>" tabindex="1" dir="auto" v-model="task.title" maxlength="255"></textarea>
				</div>
				<div class="new_comment__property" v-show="showAddElement">				
					<span class='new_comment__property_label'><?php _e( 'Срок', 'usam'); ?>:</span>
					<datetime-picker v-model="task.end" :placeholder="'<?php _e('дд.мм.гггг','usam'); ?>'"/>							
				</div>
				<div class="new_comment__property" v-show="showAddElement">
					<span class='new_comment__property_label'><?php _e( 'Напомнить', 'usam'); ?>:</span>
					<input v-if="!task.remind" type="checkbox" v-model="task.remind" value="1">
					<datetime-picker v-else v-model="task.reminder_date" :placeholder="'<?php _e('дд.мм.гггг','usam'); ?>'"/>	
				</div>		
				<div class='new_element__buttons' v-show="showAddElement">
					<button type="button" class="button" @click="addTask" ref="add_comment_button"><?php _e( 'Добавить', 'usam'); ?></button>
					<a @click="showAddElement=!showAddElement"><?php _e( 'Отменить', 'usam'); ?></a>
				</div>
			</div>
			<div class='new_comment' v-else-if="tab=='meeting'">
				<div class='post_comments'>
					<textarea class="event_comment_input event_input_text" @focus="showAddElement=true" placeholder="<?php _e('Тема встречи…','usam'); ?>" tabindex="1" dir="auto" v-model="meeting.title" maxlength="255"></textarea>
				</div>
				<div class="new_comment__property" v-show="showAddElement">
					<span class='new_comment__property_label'><?php _e( 'Когда', 'usam'); ?>:</span>
					<datetime-picker v-model="meeting.start" :placeholder="'<?php _e('дд.мм.гггг','usam'); ?>'"/>							
				</div>
				<div class="new_comment__property" v-show="showAddElement">					
					<span class='new_comment__property_label'><?php _e( 'Напомнить', 'usam'); ?>:</span>
					<input v-if="!meeting.remind" type="checkbox" v-model="meeting.remind" value="1">
					<datetime-picker v-else v-model="meeting.reminder_date" :placeholder="'<?php _e('дд.мм.гггг','usam'); ?>'"/>							
				</div>	
				<div class='new_element__buttons' v-show="showAddElement">					
					<button type="button" class="button" @click="addMeeting" ref="add_comment_button"><?php _e( 'Добавить', 'usam'); ?></button>
					<a @click="showAddElement=!showAddElement"><?php _e( 'Отменить', 'usam'); ?></a>
				</div>	
			</div>
		</div>
		<div class="ribbon_block">
			<div class='comments__item' v-for="(item, k) in items" :class="['ribbon_item_'+item.event_type, item.event_type=='comment'?'user_comment':'']">						
				<div class='comments__item_content' v-if="item.event_type=='comment'">
					<div class='ribbon__item_header'>
						<div class='ribbon__item_header_right'>
							<span class='comments__item_name'><?php _e( 'Комментарий', 'usam'); ?></span>
						</div>
						<div class='ribbon__item_header_left'>
							<span class='comments__item_author' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
							<span class='comments__item_date'>{{localDate(item.date_insert,'d.m.Y')}}</span>
							<a class="delete_item" v-if="item.author.mine" @click="deleteComment(k)"></a>
						</div>
					</div>
					<div class='comments__item_message' v-if="!item.editor" @dblclick="openEdit(k)" v-html="displayMessage(item.message)"></div>
					<div :id="'comment_item_edit_'+item.id" class='comments__item_edit' v-show="item.editor">
						<div class='post_comments'>
							<textarea class="event_comment_input event_input_text" ref="messages" tabindex="1" dir="auto" v-model="item.message"></textarea>
						</div>
						<button type="button" class="button" @click="clickCommentUpdate(k)"><?php _e( 'Добавить', 'usam'); ?></button>	
					</div>	
				</div>
				<div class='comments__item_content' v-else-if="item.event_type=='sms'">
					<div class='ribbon__item_header'>
						<div class='ribbon__item_header_right'>
							<span class='comments__item_name'>SMS</span>
							<span class='ribbon__item_status' v-if="item.folder==='sent'"><?php _e( 'Отправлено', 'usam'); ?></span>
							<span class='ribbon__item_status not_sent' v-else><?php _e( 'Не отправлено', 'usam'); ?></span>
						</div>
						<div class='ribbon__item_header_left'>
							<span class='comments__item_author' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
							<span class='comments__item_date'>{{localDate(item.date_insert,'d.m.Y')}}</span>									
						</div>
					</div>
					<div class='comments__item_message' v-html="item.message"></div>								
				</div>	
				<div class='comments__item_content' v-else-if="item.event_type=='email'" @click="sidebar('email', k)">
					<div class='ribbon__item_header'>
						<div class='ribbon__item_header_right'>	
							<span class="dashicons dashicons-undo" v-if="item.type==='sent_letter'" title="<?php _e('Исходящее письмо', 'usam'); ?>"></span>
							<span class="dashicons dashicons-redo" v-else title="<?php _e('Входящие', 'usam'); ?>"></span>
							<span class='comments__item_name'><?php _e( 'Письмо', 'usam'); ?></span>
							<span class="dashicons dashicons-paperclip" v-if="item.size>0"></span>
							<span class="dashicons dashicons-star-filled importance important" v-if="item.importance"></span>
							<span class="dashicons dashicons-star-empty importance" v-else></span>
							<span class='ribbon__item_status' v-if="item.type==='sent_letter' && item.folder==='sent'"><?php _e( 'Отправлено', 'usam'); ?></span>
							<span class='ribbon__item_status not_sent' v-if="item.type==='sent_letter' && item.folder!=='sent'"><?php _e( 'Не отправлено', 'usam'); ?></span>							
						</div>
						<div class='ribbon__item_header_left'>
							<span class='comments__item_author' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
							<span class='comments__item_date'>{{localDate(item.date_insert,'d.m.Y H:i')}}</span>									
						</div>
					</div>
					<div class='comments__item_row'>
						<span class='comments__item_row_label'><?php _e( 'Кому', 'usam'); ?>:</span>
						<span class='comments__item_row_data' v-html="item.to_name+' '+item.to_email" v-if="item.type==='sent_letter'"></span>
						<span class='comments__item_row_data' v-html="item.from_name+' '+item.from_email" v-else></span>
					</div>	
					<div class='comments__item_row' v-if="item.type==='sent_letter' && item.opened_at">
						<span class='comments__item_row_label'><?php _e( 'Прочитанно', 'usam'); ?>:</span>
						<span class='comments__item_row_data'>{{localDate(item.opened_at,'d.m.Y H:i')}}</span>
					</div>		
					<div class='comments__item_message' v-html="item.title"></div>								
				</div>	
				<div class='comments__item_content' v-else-if="item.event_type=='task'">
					<div class='ribbon__item_header'>
						<div class='ribbon__item_header_right'>
							<span class='comments__item_name'><?php _e( 'Задание', 'usam'); ?></span>
							<span class='ribbon__item_status'>{{item.status_name}}</span>
							<span class="dashicons dashicons-bell" v-if="item.reminder_date" :title="localDate(item.reminder_date,'d.m.Y H:i')"></span>
						</div>
						<div class='ribbon__item_header_left'>
							<span class='comments__item_author' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
							<span class='comments__item_date'>{{localDate(item.date_insert,'d.m.Y')}}</span>
						</div>
					</div>
					<div class='comments__item_date_completion'><?php _e( 'Выполнить до', 'usam'); ?> <span class="status_highlight item_status">{{localDate(item.end,'d.m.Y')}}</span></div>
					<div class='comments__item_message'>
						<a v-html="item.title" :href="'<?php echo admin_url("admin.php?page=personnel&tab=tasks&form=view&form_name=task&id="); ?>'+item.id"></a>
					</div>
					<div class='ribbon__item_actions' v-if="!item.status_is_completed">
						<button type="button" class="button button-primary" @click="updateTask(k,{status:'completed'})"><?php _e( 'Завершить', 'usam'); ?></button>
						<button type="button" class="button" @click="updateTask(k,{status:'canceled'})"><?php _e( 'Отменить', 'usam'); ?></button>
					</div>					
				</div>
				<div class='comments__item_content' v-else-if="item.event_type=='contacting'">
					<div class='ribbon__item_header'>
						<div class='ribbon__item_header_right'>
							<span class='comments__item_name'><?php _e( 'Обращение', 'usam'); ?></span>
							<span class='ribbon__item_status'>{{item.status_name}}</span>
							<span class="dashicons dashicons-bell" v-if="item.reminder_date" :title="localDate(item.reminder_date,'d.m.Y H:i')"></span>
						</div>
						<div class='ribbon__item_header_left'>
							<span class='comments__item_author' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
							<span class='comments__item_date'>{{localDate(item.date_insert,'d.m.Y')}}</span>
						</div>
					</div>
					<div class='comments__item_date_completion'><?php _e( 'Выполнить до', 'usam'); ?> <span class="status_highlight item_status">{{localDate(item.end,'d.m.Y')}}</span></div>
					<div class='comments__item_message'>
						<a v-html="item.title" :href="'<?php echo admin_url("admin.php?page=personnel&tab=tasks&form=view&form_name=task&id="); ?>'+item.id"></a>
					</div>
					<div class='ribbon__item_actions' v-if="!item.status_is_completed">
						<button type="button" class="button button-primary" @click="updateTask(k,{status:'completed'})"><?php _e( 'Завершить', 'usam'); ?></button>
						<button type="button" class="button" @click="updateTask(k,{status:'canceled'})"><?php _e( 'Отменить', 'usam'); ?></button>
					</div>					
				</div>
				<div class='comments__item_content' v-else-if="item.event_type=='meeting'">
					<div class='ribbon__item_header'>
						<div class='ribbon__item_header_right'>
							<span class='comments__item_name'><?php _e( 'Встреча', 'usam'); ?></span>
							<span class='ribbon__item_status'>{{item.status_name}}</span>
						</div>
						<div class='ribbon__item_header_left'>
							<span class='comments__item_author' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
							<span class='comments__item_date'>{{localDate(item.date_insert,'d.m.Y')}}</span>
						</div>
					</div>
					<div class='comments__item_row'>
						<span class='comments__item_row_label'><?php _e( 'Когда', 'usam'); ?>:</span>
						<span class='comments__item_row_data'>{{localDate(item.start,'d.m.Y')}}</span>
					</div>
					<div class='comments__item_message'>
						<a v-html="item.title" :href="'<?php echo admin_url("admin.php?page=personnel&tab=tasks&form=view&form_name=meeting&id="); ?>'+item.id"></a>
					</div>
					<div class='ribbon__item_actions' v-if="!item.status_is_completed">
						<button type="button" class="button button-primary" @click="updateTask(k,{status:'completed'})"><?php _e( 'Завершить', 'usam'); ?></button>
						<button type="button" class="button" @click="updateTask(k,{status:'canceled'})"><?php _e( 'Отменить', 'usam'); ?></button>
					</div>					
				</div>				
				<div class='comments__item_content' v-else-if="item.event_type=='chat'" :class="{'message_not_sent':item.status==0,'message_not_read':item.status==1}">
					<div class='ribbon__item_header'>
						<div class='ribbon__item_header_right'>
							<span class='comments__item_name'><?php _e( 'Сообщение в чат', 'usam'); ?></span>
						</div>
						<div class='ribbon__item_header_left'>
							<span class='comments__item_author' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
							<span class='comments__item_date'>{{localDate(item.date_insert,'d.m.Y')}}</span>
							<a class="delete_item" v-if="item.author.mine" @click="deleteComment(k)"></a>
						</div>
					</div>
					<div class='comments__item_message' v-if="!item.editor" @dblclick="openEdit(k)" v-html="displayMessage(item.message)"></div>
					<div :id="'comment_item_edit_'+item.id" class='comments__item_edit' v-show="item.editor">
						<div class='post_comments'>
							<textarea class="event_comment_input event_input_text" ref="messages" tabindex="1" dir="auto" v-model="item.message"></textarea>
						</div>
						<button type="button" class="button" @click="clickChatUpdate(k)"><?php _e( 'Добавить', 'usam'); ?></button>	
					</div>
				</div>							
			</div>
			<div class='js-show-more'></div>
		</div>
		<teleport to="body">
			<modal-panel ref="modalemail" :size="'85%'" :backdrop="true">
				<template v-slot:title><?php _e('Письмо', 'usam'); ?></template>
				<template v-slot:body>
					<form-email v-if="elKey!==null && items[elKey] !== undefined" :element="items[elKey]" @edit="items[elKey]=$event" @delete="deleteEmail" inline-template>
						<?php include( usam_get_filepath_admin('templates/template-parts/email.php') ); ?>
					</form-email>
				</template>
			</modal-panel>	
			<?php
			if( apply_filters( 'usam_possibility_to_call', false ) )
			{
				?><phone-call :phone="phone" :object_id="object_id" :object_type="object_type"></phone-call><?php			
			}
			include( usam_get_filepath_admin('templates/template-parts/modal-panel/modal-panel-send_email.php') ); 
			?>	
		</teleport>
	</div>	
</ribbon>