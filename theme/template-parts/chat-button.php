<?php 
// Описание: Шаблон кнопки чат
?>
<?php
$chat_option = get_option( "usam_chat" );
if ( !empty($chat_option['show_button']) )
{ 
	?>
	<div id="chat_clients" class="chat_button" v-cloak>			
		<div class = "usam_chat" v-if="display_chat">
			<div class="usam_chat__header">
				<div class="usam_chat__name">{{emptySender?'<?php _e('Задайте нам вопрос','usam'); ?>':'<?php _e('Ваш личный консультант','usam'); ?>'}}</div>				
				<div class="usam_chat__icons">
					<div class="usam_chat__icon usam_chat__icon_close" @click="display_chat=false">Х</div>
				</div>
			</div>
			<div class = "usam_chat_contact_form" v-show="emptySender && loaded">					
				<div class="usam_chat_contact_form__row">
					<textarea placeholder="<?php _e('Введите Ваш вопрос*','usam'); ?>" :class="[send_contactform && contact_form.message==''?'highlight':'']" v-model="contact_form.message"></textarea>
				</div>
				<div class="usam_chat_contact_form__row">
					<input type="text" class="usam_chat_contact_form__input" placeholder="<?php _e('Как к вам обращаться?*','usam'); ?>" :class="[send_contactform && contact_form.name==''?'highlight':'']" v-model="contact_form.name">
				</div>						
				<div class="usam_chat_contact_form__row">
					<input class="usam_chat_contact_form__input" type="text" placeholder="<?php _e('Ваш телефон','usam'); ?>" v-model="contact_form.phone" v-mask="'#(###)###-##-##'">
				</div>		
				<div class="usam_chat_contact_form__row">			
					<input class="usam_chat_contact_form__input" type="text" placeholder="<?php _e('Ваш Email','usam'); ?>" v-model="contact_form.email">
				</div>	
				<?php if ( usam_has_consent_processing_personal_data() ) { ?>
					<div class="usam_chat_contact_form__row">
						<input type="checkbox" value="1" v-model="confirm" :class="[!confirm && send_contactform?'highlight':'']"/><label for="confirm" class="usam_chat_contact_form__confirm"><?php _e("Я согласен c","usam"); ?> <a @click="modal('view_personal_data')"><?php _e("обработкой персональных данных", "usam"); ?></a> <span class="asterix">*</span></label>		
					</div>
					<teleport to="body">
						<modal-window :ref="'modalview_personal_data'" :backdrop="true">
							<template v-slot:title><div class ="property_name_agreement"><?php _e('Согласие на обработку персональных данных','usam'); ?></div></template>
							<template v-slot:body>
								<div class ="property_agreement modal-scroll"><?php echo wpautop( wp_kses_post( get_option( 'usam_consent_processing_personal_data' ) ) ) ?></div>
							</template>
							<template v-slot:buttons>
								<button class="button main-button" @click="confirm=1; modal('view_personal_data')"><?php _e('Согласен', 'usam'); ?></button>			
							</template>
						</modal-window>
					</teleport>
				<?php } ?>
				<div class="usam_chat_contact_form__info">
					<div class="usam_chat_contact_form__info"><?php _e("Мы можем отправить ответ на вопрос на email или позвонить вам.","usam"); ?></div>		
				</div>
				<div class="usam_chat_contact_form__buttons">
					<button class="button" @click="sendContactForm"><?php _e("Отправить","usam"); ?></button>
					<button class="usam_chat_contact_form__button_close" @click="display_chat=false"><?php _e("Отмена","usam"); ?></button>
				</div>
			</div>
			<div class="usam_chat__body" v-show="!emptySender && id && loaded">							
				<div class="usam_chat__manager">
					<div class="usam_chat__manager_photo" v-if="manager.appeal"><img :src='manager.foto'></div>
					<div class="usam_chat__manager_info" v-if="manager.appeal">
						<div class="usam_chat__manager_name">{{manager.appeal}}</div>
						<div class="usam_chat__manager_title"><?php _e('консультант','usam'); ?></div>						
					</div> 
					<div class="usam_chat__no_employees" v-if="!manager.appeal">													
						<div class="usam_chat__message_text"><?php _e('Менеджер скоро подключится...','usam'); ?></div>
					</div>
				</div>						
				<div id="chat_messages" class = "usam_chat__messages">
					<div class="js-load-more-chat-messages more-messages" v-if="loadMore"></div>				
					<div class="usam_chat__message" v-for="item in messages" :class="{'message_not_sent':item.status==0,'message_not_read':item.status==1}" :message_id="item.id">	
						<div class="usam_chat__message_header">
							<div class="usam_chat__message_user">{{item.author.appeal}}</div>
							<div class="usam_chat__message_date">{{localDate(item.date_insert)}}</div>
						</div>									
						<div class = "usam_chat__message_text" v-html="item.message"></div>					
					</div>				
				</div>		
				<div class = "usam_chat__new_message_arrived" v-show='new_message'><?php _e("Пришло новое сообщение","usam"); ?></div>
				<div class="usam_chat__controls">
					<div class="usam_chat__controls_message"><textarea v-model="message" id="textarea-message" @input="autoTextarea($event)" @keydown.enter.prevent.exact="sentMessage" @keyup.ctrl.enter.prevent="newLine" placeholder="<?php _e('Введите Ваше сообщение!','usam'); ?>"></textarea></div>
					<div class="usam_chat__controls_button"><span @click="sentMessage" class="usam_chat__controls_sent_message"></span></div>
				</div>						
			</div>			
		</div>
		<div class="chat_button__link">
			<div class="chat_button__text">
				<?php usam_svg_icon('chat')?><?php echo __("Помощь","usam"); ?> 
				<?php
				if ( !empty($chat_option['show_chat']) ){ ?>
					<span v-show="unreadMessages" class='numbers' ref="number_messages">{{unreadMessages}}</span>
				<?php } ?>
			</div>
		</div>		
		<div class = "chat__box_buttons" v-if="!display_chat">
			<div class = "chat__wrapper_buttons">
				<div class = "chat__buttons">
					<?php if ( !empty($chat_option['show_chat']) ){ ?>
						<a href="" class = "chat__button" @click="openChat"><?php usam_svg_icon('chat'); ?><span class="chat__button_name"><?php _e('Написать в чат','usam'); ?></span></a>
					<?php				
					}					
					$icons = ['backcall' => 'backcall', 'webform' => 'question', 'review' => 'star'];
					$svg = [];
					$webform_codes = [];
					if ( !empty($chat_option['backcall']) )
						$webform_codes[$chat_option['backcall']] = $icons['backcall'];
					if ( !empty($chat_option['webform']) )
						$webform_codes[$chat_option['webform']] = $icons['webform'];
					if ( !empty($chat_option['review']) )
						$webform_codes[$chat_option['review']] = $icons['review'];					
					if ( $webform_codes )
					{	
						foreach( $webform_codes as $code => $icon )
							echo usam_get_webform_link($code, 'chat__button', $icon);	
					}
					foreach( usam_get_phones( ) as $phone )
					{	
						if ( $chat_option['phone'] == $phone['phone'] )
						{
							?><a href="tel:+<?php echo $phone['phone']; ?>" class="chat__button chat__phone"><?php usam_svg_icon('phone'); ?><span class="chat__button_name"><?php echo usam_phone_format($phone['phone'], $phone['format']); ?></span></a><?php
						}
						if ( isset($chat_option['whatsapp']) &&  $chat_option['whatsapp'] == $phone['phone'] )
						{
							?><a href="https://api.whatsapp.com/send?phone=<?php echo $chat_option['whatsapp']; ?>" target="_blank" rel="noopener" class="chat__button chat__messenger"><?php usam_svg_icon('whatsapp'); ?><span class="chat__button_name"><?php _e("Написать в Whatsapp","usam"); ?></span></a><?php
						}
						if ( isset($chat_option['viber']) && $chat_option['viber'] == $phone['phone'] )
						{
							?><a href="viber://chat?number==<?php echo $chat_option['viber']; ?>" target="_blank" rel="noopener" class="chat__button chat__messenger"><?php usam_svg_icon('viber'); ?><span class="chat__button_name"><?php _e("Написать в Viber","usam"); ?></span></a><?php
						}
					}								
					?>
				</div>
			</div>
		</div>
	</div>
<?php } ?>