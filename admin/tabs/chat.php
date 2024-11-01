<?php
class USAM_Tab_Chat extends USAM_Tab
{	
	public function __construct()
	{		
		$this->views = ['table', 'report', 'settings'];
	}	
		
	public function get_title_tab()
	{			
		if ( $this->view == 'settings' )
		{			
			if ( $this->table == 'messengers' )		
				return __('Подключение мессенджеров', 'usam');
			else				
				return __('Настройки чата', 'usam');	
		}
		else
			return __('Общение с клиентами', 'usam');
	}	
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'messengers' )	
			return [
				['form' => 'edit', 'form_name' => 'viber', 'title' => __('Добавить Viber', 'usam')],
				['form' => 'edit', 'form_name' => 'telegram', 'title' => __('Добавить Telegram', 'usam')],				
				['form' => 'edit', 'form_name' => 'skype', 'title' => __('Добавить Skype', 'usam')],	
				['form' => 'edit', 'form_name' => 'facebook', 'title' => __('Добавить Facebook', 'usam')],					
		];				
		return [];
	}
	
	protected function action_processing()
	{				
		switch( $this->current_action )
		{	
			case 'open_dialog':
				if ( !empty($_REQUEST['contact_id']) )
				{
					$contact_id = absint($_REQUEST['contact_id']);			
					if ( !empty($_REQUEST['channel']) && !empty($_REQUEST['channel_id']) )
					{
						$channel = sanitize_title($_REQUEST['channel']);	
						$channel_id = absint($_REQUEST['channel_id']);	
					}
					else
					{
						$channel = 'chat';		
						$channel_id = 0;								
					}
					require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
					$dialog_id = (int)usam_get_chat_dialogs(['contact_id' => $contact_id, 'fields' => 'id', 'orderby' => 'id', 'order' => 'DESC', 'number' => 1, 'channel' => $channel, 'channel_id' => $channel_id]);
					if ( empty($dialog_id) )
					{
						$manager_id  = usam_get_contact_id();
						$dialog_id = usam_insert_chat_dialog(['manager_id' => $manager_id, 'channel' => $channel, 'channel_id' => $channel_id], [$contact_id, $manager_id] );
					}
					$this->sendback = remove_query_arg(['contact_id', 'channel', 'channel_id'], $this->sendback );	
					$this->sendback = add_query_arg( array( 'sel' => $dialog_id ), $this->sendback );				
					$this->redirect = true;
				}
			break;
		}		
	}		
	
	public function table_view() 
	{
		?>			
		<div class="chat_view">				
			<div id="manager_chat"  class="current_dialog" :class="{'dialog_open': id}" v-show="id>0" v-cloak>											
				<div id="chat_clients" class="chat__content">
					<div class="dialog_recipient">
						<div class="dialog_recipient_content" v-if="recipient.id && loaded">
							<a href="" class="go_back"><span class="dashicons dashicons-arrow-left-alt2"></span></a>
							<div class="dialog_recipient__name">
								<div class="dialog_recipient__name_foto"><img :src='recipient.foto'></div>
								<a class="dialog_recipient__name_user" :href='recipient.url'>{{recipient.appeal}}</a>
							</div>
							<span class='customer_online' v-if='recipient.online'></span>
							<span class='date_visit' v-else><?php _e('был', 'usam'); ?> {{localDate(recipient.date_online)}}</span>
						</div>
					</div>
					<div id="chat_messages" class="chat__messages">
						<div class="js-load-more-chat-messages more-messages" v-if="loadMore"></div>
						<div class="chat__message" v-for="item in messages" :class="{'message_not_sent':item.status==0,'message_not_read':item.status==1}" :message_id="item.id">
							<div class="chat__message_header">
								<div class="chat__message_user">{{item.author.appeal}}</div>
								<div class="chat__message_date">{{localDate(item.date_insert)}}</div>
							</div>									
							<div class = "chat__message_attachment" v-if="item.attachments" v-for="attachment in item.attachments"><img :src='attachment.url'/></div>		
							<div class = "chat__message_text" v-html="item.message"></div>
						</div>				
					</div>		
					<div class = "chat__new_message_arrived" v-show='new_message'><?php _e("Пришло новое сообщение","usam"); ?></div>					
					<div class="chat__controls" v-if="manager.id">
						<div class="chat__controls_message"><textarea id="textarea-message" v-model="message" @input="autoTextarea" placeholder="<?php _e('Введите Ваше сообщение!','usam'); ?>"></textarea></div>
						<div class="chat__controls_button"><span @click="sentMessage" class="chat__controls_sent_message"></span></div>
					</div>
					<div class="current_dialog__manager" v-else>
						<span class='current_dialog__no_manager'><?php _e("Менеджер не назначен","usam"); ?></span><a class="button" @click="setManager"><?php _e("Подключиться","usam"); ?></a>
					</div>
				</div>	
			</div>
			<div id = "dialogs" class = "chat_view__table">
				<?php $this->list_table->display_table(); ?>
			</div>
		</div>
		<?php	
	}
		
	public function get_tab_sections() 
	{ 
		if ( $this->view == 'settings' )
		{ 
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );		
		}	
		else 
			$tables = [];
		return $tables;
	}
	
	public function get_settings_tabs() 
	{ 
		return ['messengers' => ['title' => __('Мессенджеры','usam'), 'type' => 'table'], 'application' => ['title' => __('Общие настройки','usam'), 'type' => 'section']];
	}
	
	public function display_section_application( ) 
	{			
		usam_add_box( 'usam_settings', __('Настройка чата', 'usam'), array( $this, 'settings_meta_box' ) );
	//	usam_add_box( 'usam_decor', __('Шаблоны сообщений', 'usam'), array( $this, 'decor_meta_box' ) );	
	}
	
	public function settings_meta_box()
	{	
		require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
		$webforms = array( '' => __('Нет','usam') );
		$webforms_review = array( '' => __('Нет','usam') );
		foreach ( usam_get_webforms( ) as $webform )
		{	
			if ( $webform->action == 'contacting' )
				$webforms[$webform->code] = $webform->title;			
			elseif ( $webform->action == 'review' )
				$webforms_review[$webform->code] = $webform->title;
		}
		$whatsapp = $viber = $phones = [ '' => __('Нет','usam')];
		foreach ( usam_get_phones( ) as $phone )
		{	
			$phones[$phone['phone']] = $phone['name'];
			if ( $phone['viber'] )
				$viber[$phone['phone']] = $phone['name'];
			if ( $phone['whatsapp'] )
				$whatsapp[$phone['phone']] = $phone['name'];
		}
		$options = array(    	
			array( 'key' => 'show_button', 'type' => 'checkbox', 'title' => __('Показать кнопку на сайте', 'usam'), 'option' => 'chat'),
			array( 'key' => 'show_chat', 'type' => 'checkbox', 'title' => __('Показать чат', 'usam'), 'option' => 'chat'),
			array( 'key' => 'webform', 'type' => 'select', 'title' => __('Веб-форма', 'usam'), 'option' => 'chat', 'options' => $webforms),
			array( 'key' => 'backcall', 'type' => 'select', 'title' => __('Веб-форма обратного звонка', 'usam'), 'option' => 'chat', 'options' => $webforms),
			array( 'key' => 'review', 'type' => 'select', 'title' => __('Оставить отзыв', 'usam'), 'option' => 'chat', 'options' => $webforms_review),
			array( 'key' => 'phone','type' => 'select', 'title' => __('Показать телефон', 'usam'), 'option' => 'chat', 'options' => $phones),
			array( 'key' => 'whatsapp','type' => 'select', 'title' => __('Написать в Whatsapp', 'usam'), 'option' => 'chat', 'options' => $whatsapp),
			array( 'key' => 'viber','type' => 'select', 'title' => __('Написать в Viber', 'usam'), 'option' => 'chat', 'options' => $viber)
		); 		  
		$this->display_table_row_option( $options ); 
	}	
	
	public function decor_meta_box()
	{			
		$options = array( 			
			array( 'key' => 'welcome_message', 'type' => 'textarea', 'title' => __('Приветственное сообщение', 'usam'), 'option' => 'chat', 'description' => __('Сообщение после ','usam') ),
			array( 'key' => 'product_day_message', 'type' => 'textarea', 'title' => __('Сообщение для товара дня', 'usam'), 'option' => 'chat', 'description' => __('Маска сообщения для стены ВКонтакте: %title% - заголовок статьи, %price% - цена, %excerpt% - анонс статьи, %link% - ссылка на статью, %name% - название сайта.' ) ),
			array( 'key' => 'reviews_message', 'type' => 'textarea', 'title' => __('Сообщение для отзывов', 'usam'), 'option' => 'chat', 'description' => __('Маска сообщения для стены ВКонтакте: %header% - заголовок, %review_title% - заголовок отзыва, %review_rating% - рейтинг, %review_author% - имя покупателя,%review_response% - ответ, %review_excerpt% - отзыв, %link% - ссылка на товар, %link_catalog% - ссылка на каталог.', 'usam') ),			
			array( 'key' => 'product_review_message', 'type' => 'textarea', 'title' => __('Сообщение для отзывов товара', 'usam'), 'option' => 'chat', 'description' => __('Маска сообщения для стены ВКонтакте: %review_title% - заголовок отзыва, %review_rating% - рейтинг, %review_author% - имя покупателя,%review_response% - ответ, %review_excerpt% - отзыв, %link% - ссылка на товар, %link_catalog% - ссылка на каталог.', 'usam') ),			
			array( 'key' => 'birthday', 'type' => 'textarea', 'title' => __('Сообщение с поздравлением ДР', 'usam'), 'option' => 'chat', 'description' => __('Маска сообщения для стены ВКонтакте: %user_link% - ссылка на страницу, %first_name% - имя, %last_name% - фамилия, %sex% - пол, %city% - город, %country% - страна, %photo_50% - фото, %photo_100% - фото. Все метки должны быть в {}, например {%user_link% %photo_100%}', 'usam') ),	
		 );	 
		$this->display_table_row_option( $options ); 
	}	
}