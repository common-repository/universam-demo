<?php
require_once( USAM_FILE_PATH . '/admin/includes/grid_view.class.php' );
class USAM_Affairs_Grid_View extends USAM_Grid_View
{		
	protected function prepare_items( ) 
	{
		$this->js_args['columns'] = [
			'overdue' => ['name' => __('Просроченное','usam')], 
			'day' => ['name' => __('Сегодня','usam'), 'start' => date("Y-m-d H:i:s", mktime(0, 0, 0, date_i18n('m'), date_i18n('d'), date_i18n('Y'))), 'end' => date("Y-m-d H:i:s", mktime(0, 0, 0, date_i18n('m'), date_i18n('d'), date_i18n('Y')))], 
			'tomorrow' => ['name' =>  __('Завтра','usam'), 'start' => date("Y-m-d H:i:s", mktime(0, 0, 0, date_i18n('m'), date_i18n('d')+1, date_i18n('Y'))), 'end' => date("Y-m-d H:i:s", mktime(0, 0, 0, date_i18n('m'), date_i18n('d')+1, date_i18n('Y')))], 
			'future' => ['name' => __('Ближайшее будущее','usam'),'start' => date("Y-m-d H:i:s", mktime(0, 0, 0, date_i18n('m'), date_i18n('d')+2, date_i18n('Y'))), 'end' => date("Y-m-d H:i:s", mktime(0, 0, 0, date_i18n('m'), date_i18n('d')+2, date_i18n('Y')))]
		];
	}
	
	public function display_grid_items( ) 
	{ 
		?>								
		<div class="grid_column" v-for="(column, k) in columns">
			<div class="grid_column_header">
				<div class="grid_column_title" :class="'grid_column_title_'+k">
					<div class="grid_column_title_text">
						<div class="status_column_title_text_inner">{{column.name}}</div>
					</div>
				</div>
				<div class="title_status_sum">
					<div class="sum_status_inner">{{column.number}}</div>			
				</div>
			</div>
			<div class="grid_items" @dragover="allowDrop($event, k)" @drop="drop($event, k)">		
				<div class="grid_view__item" v-if="item.column==k" v-for="(item, i) in items" draggable='true' @dragstart="drag($event, i, k)" @dragend="dragEnd($event, i)" :class="{'grid_view__item_checked':item.checked}">
					<div class="grid_view__item_wrapper" :class="item.color?'border_color border_'+item.color:''" @click="checked(i,$event)">
						<div class="grid_item__row grid_item__title">
							<a :href="item.url" v-html="item.title"></a>
							<div v-if="item.checked" class="grid_view_checkbox"></div>
						</div>
						<div class="grid_item__row event_date_icons">
							<div class="event_date" v-if="k =='day' && ( item.end == '' || item.end >= today_end) && item.start <= today_start"><?php _e('Сегодня весь день','usam'); ?></div>
							<div class="event_date" v-else-if="k =='day' && item.start <= current_date"><?php _e('Сегодня c','usam'); ?> {{item.start_hour}}</div>
							<div class="event_date" v-else-if="k =='day' && today_end <= item.end"><?php _e('Начиная с сегодня и по','usam'); ?> {{localDate(item.end,'d.m.Y H:i')}}</div>
							<div class="event_date" v-else-if="k =='tomorrow' && item.end >= tomorrow_end_day"><?php _e('Завтра весь день','usam'); ?></div>
							<div class="event_date" v-else-if="k =='tomorrow' && item.end < tomorrow_end_day"><?php _e('Завтра в','usam'); ?> {{item.start_hour}}</div>
							<div class="event_date" v-else-if="item.end">{{localDate(item.end,'d.m.Y H:i')}}</div>
							<div class="event_icons">								
								<span v-if="item.type=='task' && item.user_id=='<?php get_current_user_id(); ?>'" class="event_type_icon dashicons dashicons-flag" title="<?php _e('Задание', 'usam'); ?>"></span>
								<span v-else-if="item.type=='task'" class="event_type_icon dashicons dashicons-arrow-right-alt" title="<?php _e('Помогаете', 'usam'); ?>"></span>
								<span v-else-if="item.type=='affair'" class="event_type_icon dashicons dashicons-flag" title="<?php _e('Дело', 'usam'); ?>"></span>
								<span v-else-if="item.type=='meeting'" class="event_type_icon dashicons dashicons-groups" title="<?php _e('Встреча', 'usam'); ?>"></span>
								<span v-else-if="item.type=='call'" class="event_type_icon dashicons dashicons-phone" title="<?php _e('Звонок', 'usam'); ?>"></span>								
								<span v-else-if="item.type=='event'" class="event_type_icon dashicons dashicons-calendar-alt" title="<?php _e('Событие', 'usam'); ?>"></span>												
								<a v-if="item.importance" class="dashicons dashicons-star-filled" @click="updateEvent(i, {'importance':0})"></a>
								<a v-else class="dashicons dashicons-star-empty" @click="updateEvent(i, {'importance':1})"></a>
								<span v-if="item.reminder_date" class="dashicons dashicons-bell" :title="localDate(item.reminder_date,'d.m.Y H:i')"></span>
							</div>
						</div>						
						<div class="grid_item__row event_managers">
							<div class="event_manager" v-if="item.author"><div class="event_manager__title"><?php _e('Автор','usam'); ?>:</div><div class="event_manager__author" v-html="item.author.appeal"></div></div>
							<div class="event_manager" v-if="item.users.responsible.length">
								<div class="event_manager__title"><?php _e('Ответственный','usam'); ?>:</div>
								<div v-for="(user, i) in item.users.responsible" class="event_manager__author" v-html="user.appeal"></div>
							</div>
							<div class="event_participants" v-if="item.users.participant.length">
								<div class="event_manager__title"><?php _e('Исполнители','usam'); ?>:</div>
								<div v-for="(user, i) in item.users.participant" class="event_manager__icon dashicons dashicons-admin-users" :title="user.appeal"></div>
							</div>
						</div>							
					</div>	
					<div class='last_comment' v-if="item.last_comment">
						<div class='user_block user_comment'>								
							<div class='user_block__content'>
								<div class='user_comment__user'>
									<span class='user_block__user_name' v-html="item.last_comment_user_name"></span>
									<span class='user_comment__date' v-html="item.display_last_comment_date"></span>
								</div>
								<div class='user_comment__message' v-html="item.last_comment"></div>
							</div>
						</div>
					</div>					
				</div>
			</div>	
		</div>
		<div class="grid_status_close" :class="{'draggable':draggable}">	
			<div class="grid_status_close__statuses">
				<div @dragover="allowDrop" @drop="dropStatus" class="grid_status_close__status_title drop_area"><?php _e('Завершить', 'usam'); ?></div>
			</div>					
		</div>
		<?php 	
	}
}
?>