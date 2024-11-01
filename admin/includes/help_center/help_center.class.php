<?php
class USAM_Help_Center
{	
	private function search_html() 
	{	
		?>
		<div class="hc_search" v-show="active_tab=='search'">
			<div class="hc_search__search">
				<div class="hc_search__input">
					<input type='text' v-model='search' v-on:keyup.enter="start_search" placeholder='<?php _e('Напишите, что вы хотите узнать...','usam'); ?>'>
					<span v-if="message_search" class="loading_process"></span>
				</div>
				<button type='submit' class='button' @click='start_search'><?php _e('Поиск','usam'); ?></button>
			</div>
			<div class='hc_search_results hc_tab_scroll'>
				<div class = 'hc_search_results__item' v-for='(item, k) in search_results.items' v-if="!message_search">
					<div class='hc_search_results__item_name'><a :href='item.link' target='_blank' v-html='item.name'></a></div>
					<div class='hc_search_results__item_link'><a :href='item.link' target='_blank'>{{item.link}}</a></div>
				</div>			
			</div>
			<div class='hc_search_results__nothing_found' v-if="search_results.count===0"><?php _e('Ничего не найдено','usam'); ?></div>
		</div>
		<?php
	}
	
	/**
	 * Контакт центр.
	 */
	private function contact_support_html() 
	{
		$types = usam_get_subject_support_message();
		?>	
		<div class="hc_support_messages" v-show="active_tab=='contact-support'">
			<table class="hc_support_messages__send">
				<tr>
					<td>
						<select v-model="email_subject">
							<?php
							foreach( $types as $key => $title )
							{						
								echo "<option value='$key'>$title</option>";
							}
							?>
						</select>
					</td>
				</tr>				
					<td><textarea id="support_response" v-model="email_message" rows="6"/></textarea></td>
				</tr>
				<tr>
					<td><?php submit_button( __('Отправить','usam'), 'primary','action_send_message', false, ['@click' => 'send_email']); ?><span v-if="send_message" class="loading_process"></span></td>
				</tr>
			</table>
			<div class="hc_tab_scroll">
				<div class='message' v-for='(item, k) in messages' :class="[item.outbox?'outbox':'inbox']">
					<div class = "message_content">
						<div class='subject'>
							<span v-if='item.outbox'><?php _e('Ответ службы поддержки','usam') ?></span>
							<span v-else><?php _e('Вы','usam') ?></span>
						</div>
						<div class='message_text' v-html='item.message'></div>
						<div class='date'>{{item.date}}</div>
					</div>
				</div>
				<div class='js-load-support-messages more-messages' v-show='loadMore'></div>		
			</div>			
		</div>
		<?php
	}
	
	private function support_about_html() 
	{				
		?>
		<div class="hc_tab_about" v-show="active_tab=='about'">
			<table>
				<tr>
					<td><?php _e('Сайт','usam') ?>:</td>
					<td><a target="_blank" href="https://wp-universam.ru/">wp-universam.ru</a><td>
				</tr>
				<tr>
					<td><?php _e('Техническая поддержка','usam') ?>:</td>
					<td><a href="mailto:office@wp-universam.ru">support@wp-universam.ru</a><td>
				</tr>
				<tr>
					<td><?php _e('Отдел по работе с клиентами','usam') ?>:</td>
					<td><a href="mailto:office@wp-universam.ru">office@wp-universam.ru</a><td>
				</tr>
			</table>
		</div>
		<?php
	}
	
	private function video_html() 
	{				
		?>
		<div class="hc_tab_video" v-show="active_tab=='video'">
			<iframe width="560" height="315" frameborder="0" allowfullscreen></iframe>
		</div>
		<?php
	}	
	
	public function get_default_tabs() 
	{
		return [
			'search' => ['tab_name' => __('База знаний', 'usam'), 'title' => __('Поиск по базе знаний', 'usam'), 'dashicon' => 'search'],
		//	'video' => ['tab_name' => __('Видео урок', 'usam'), 'title' => __('Видео урок', 'usam'), 'dashicon' => 'video-alt3'],
			'contact-support' => ['tab_name' => __('Чат с разработчиком', 'usam'), 'title' => __('Чат с разработчиком', 'usam'), 'dashicon' => 'email-alt'],
			'about' => ['tab_name' => __('Разработчики', 'usam'), 'title' => __('Разработчики', 'usam'), 'dashicon' => 'location']		
		];
	}

	/**
	 * Вывод помощи
	 */
	public function output_help_center() 
	{
		?>
		<div id="help_center" class="usam_help_center" :class="{'usam_help_center_open':open}" v-cloak>
			<div class="usam_help_center__content">	
				<div class="usam-help-center-tabs">
					<ul>
						<li v-for='(tab, k) in tabs' :class="{'active':active_tab==k}" class="usam-help-center-item">
							<a :class="'dashicons-before dashicons-'+tab.dashicon" v-html="tab.tab_name" @click="active_tab=k"></a>
						</li>
					</ul>
				</div>
				<div class="hc_tab" :class="'hc_tab_'+active_tab">
					<h2 v-html="tabs[active_tab].title"></h2>
					<div class="hc_tab__content">						
						<?php 
							$this->search_html();
							$this->contact_support_html();
							$this->video_html();			
							$this->support_about_html();
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
?>