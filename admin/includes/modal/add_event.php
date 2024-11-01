<div id="add_event" class="modal fade modal-medium">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Добавить событие','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class="edit_form usam_event_edit modal-scroll" ref="event">			
			<input type="hidden" v-model="event.object_id"/>
			<input type="hidden" v-model="event.object_type"/>
			<input type="hidden" v-model="event.type"/>				
			<div class="edit_form__item usam_name_customer" v-show="customer.title">
				<div class="edit_form__item_name" v-html="customer.title"></div>
				<div class="edit_form__item_option" v-html="customer.name"></div>
			</div>
			<div class="edit_form__item">
				<div class="edit_form__item_name"><?php _e('Начать', 'usam'); ?>:</div>
				<div class="edit_form__item_option">
					<datetime-picker v-model="event.start"/>	
				</div>
			</div>	
			<div class="edit_form__item">
				<div class="edit_form__item_name"><?php _e('Срок', 'usam'); ?>:</div>
				<div class="edit_form__item_option">
					<datetime-picker v-model="event.end"/>	
				</div>
			</div>			
			<div class="edit_form__item">
				<div class="edit_form__item_name"><label for="add_ed_name"><?php _e('Название', 'usam'); ?>:</label></div>
				<div class="edit_form__item_option"><input type="text" class ="event_name" v-model="event.title"></div>
			</div>	
			<div class="edit_form__item">
				<div class="edit_form__item_name"><label for="add_ed_description"><?php _e('Описание', 'usam'); ?>:</label></div>
				<div class="edit_form__item_option"><textarea class ="event_description" v-model="event.description"></textarea></div>
			</div>
			<div class="edit_form__item" v-if="event.start">
				<div class="edit_form__item_name"><label for="add_ed_importance"><?php _e('Напомнить', 'usam'); ?>:</label></div>
				<div class="edit_form__item_option edit_form__date_reminder">
					<input type="checkbox" v-model="remind" value="1"/>
					<datetime-picker v-if="remind" v-model="event.reminder_date"/>	
				</div>
			</div>	
			<div class="edit_form__item">
				<div class="edit_form__item_name"><label for="add_ed_importance"><?php _e('Важность', 'usam'); ?>:</label></div>
				<div class="edit_form__item_option"><input type="checkbox" v-model="event.importance"></div>
			</div>	
			<div class="edit_form__item">
				<div class="edit_form__item_name"><label for="add_ed_description"><?php _e('Календарь', 'usam'); ?>:</label></div>
				<div class="edit_form__item_option">
					<?php 	
					$calendars = usam_get_calendars( );
					if ( !empty($calendars) )
					{
						?>
						<select v-model="event.calendar">
							<?php 		
							foreach( $calendars as $key => $item )
							{	
								?><option value="<?php echo $item['id']; ?>"><?php echo $item['name']; ?></option><?php 	
							}
							?>
						</select><?php 							
					}
					?>
				</div>
			</div>			
		</div>	
		<div class="modal__buttons">
			<button type="button" class="button-primary button" @click="save" :disabled="event.title===''" v-if="event.id"><?php _e( 'Изменить', 'usam'); ?></button>
			<button type="button" class="button" @click="del" :disabled="event.title===''" v-if="event.id"><?php _e( 'Удалить', 'usam'); ?></button>
			<button type="button" class="button-primary button" @click="save" :disabled="event.title===''" v-else><?php _e( 'Добавить', 'usam'); ?></button>
			<button type="button" class="button" data-dismiss="modal" aria-hidden="true"><?php _e( 'Отменить', 'usam'); ?></button>
		</div>
	</div>
</div>