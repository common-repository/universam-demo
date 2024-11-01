<div id="events_reminder" class ='events_reminder' :class="{'events_reminder_ready':load}" v-cloak>
	<div v-for="(event, k) in events" class="usam_notifi usam_notifi_info events_reminder__items" :class="{'importance':event.importance, 'usam_notifi_animate':load}">		
		<div class="usam_notifi_content">
			<div class="usam_notifi_message">
				<a class='events_reminder__title' :href="event.url" v-html="event.title"></a>	
				<div class='events_reminder__content_times'>
					<span class="events_reminder__content_time" v-if="event.start"><?php _e('Начать', 'usam'); ?>: <strong>{{localDate(event.start,'d.m.Y H:i')}}</strong></span>
					<span class="events_reminder__content_time"v-if="event.end"><?php _e('Завершить', 'usam'); ?>: <strong>{{localDate(event.end,'d.m.Y H:i')}}</strong></span>
				</div>
				<div class='events_reminder__tools'>
					<span class='events_reminder__remind'>
						<?php _e('Напомнить через','usam'); ?> 
						<select v-model='event.minute'>
							<option value='5'><?php _e('5 минут', 'usam'); ?></option>
							<option value='5'><?php _e('5 минут', 'usam'); ?></option>
							<option value='30'><?php _e('30 минут', 'usam'); ?></option>	
							<option value='60'><?php _e('1 час', 'usam'); ?></option>	
							<option value='120'><?php _e('2 часа', 'usam'); ?></option>	
							<option value='240'><?php _e('4 часа', 'usam'); ?></option>		
							<option value='480'><?php _e('8 часа', 'usam'); ?></option>	
							<option value='1440'><?php _e('1 день', 'usam'); ?></option>		
							<option value='7200'><?php _e('5 дней', 'usam'); ?></option>	
							<option value='10080'><?php _e('1 неделю', 'usam'); ?></option>			
						</select>
					</span>
					<a class='button' @click="remind(k)"><?php _e('Отложить', 'usam'); ?></a>
				</div>
			</div>
			<div class="usam_notifi_close" @click="close(k)"></div>
		</div>
	</div>
</div>