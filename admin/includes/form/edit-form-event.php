<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Type_Event extends USAM_Edit_Form
{		
	protected $users = [];	
	protected $event_type = 'task';	
	protected $vue = true;	
		
	protected function get_data_tab()
	{		
		$user_id = get_current_user_id();	
		$default = [
			'id'          => 0,				
			'importance'  => 0, 
			'calendar'    => '', 
			'status'      => 'not_started', 
			'title'       => '', 
			'description' => '', 		
			'user_id'     => $user_id,
			'color'       => '', 			
			'type'        => $this->event_type, 	
			'schedule'    => '',		
			'actions'     => [],	
			'start'       => date("Y-m-d H:i:s", mktime(date("H"), 0, 0, date("m"), date("d")+1, date("Y"))), 
			'end'         => date("Y-m-d H:i:s", mktime(date("H"), 0, 0, date("m"), date("d")+2, date("Y"))), 
			'reminder_date' => '',	
			'date_insert' => date("Y-m-d H:i:s" ),
			'groups'      => [],	
			'responsible' => 0,				
			'status_is_completed' => false,	
			'status_name' => '',	
			'links' => []
		];	
		$this->js_args['users'] = [];
		if ( $this->id != null )	
		{
			$this->data = usam_get_event( $this->id );							
			if ( empty($this->data) )
				return;
			if ( !current_user_can('edit_'.$this->data['type']) || !usam_check_event_access( $this->data, 'view' ) )
			{
				$this->data = [];
				return;
			} 			
			$event_users = usam_get_event_users( $this->id );				
			$this->data['responsible'] = !empty($event_users['responsible'])?$event_users['responsible'][0]:0;
			$this->users = !empty($event_users['participant']) ? $event_users['participant']:[];			
			$user_ids = usam_get_event_users( $this->id, false );			
			if( $user_ids )
			{
				$contacts = usam_get_contacts(["user_id" => $user_ids, 'source' => 'all']);
				foreach( $event_users as $type => $user_ids )				
				{
					foreach( $contacts as $contact )
						if( in_array($contact->user_id, $user_ids) )
						{
							$contact->foto = usam_get_contact_foto( $contact->id );
							$contact->post = (string)usam_get_contact_metadata($contact->id, 'post');
							$contact->online = strtotime($contact->online) >= USAM_CONTACT_ONLINE;
							$contact->url = usam_get_contact_url( $contact->id );	
							$this->js_args['users'][$type][] = $contact;						
						}
				}
			}
			$this->add_form_data();
		}			
		$this->data = array_merge( $default, $this->data );	
		$this->data = array_merge( $this->data_default(), $this->data );		
		
		if( $this->data['start'] )
			$this->data['start'] = get_date_from_gmt( $this->data['start'] );	
		if( $this->data['end'] )
			$this->data['end'] = get_date_from_gmt( $this->data['end'] );	
		$this->data['status_name'] = usam_get_object_status_name( $this->data['status'], $this->data['type'] );		
		
		$this->js_args['responsible'] = ['id' => 0, 'appeal' => '', 'foto' => '', 'url' => ''];
		$contact = usam_get_contact( $this->data['responsible'], 'user_id' );
		if( $contact )
		{
			$this->js_args['responsible'] = $contact;
			$this->js_args['responsible']['online'] = strtotime($contact['online']) >= USAM_CONTACT_ONLINE;
			$this->js_args['responsible']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['responsible']['post'] = (string)usam_get_contact_metadata($contact['id'], 'post');
			$this->js_args['responsible']['url'] = usam_get_contact_url( $contact['id'] );	
		}		
		$this->js_args['object_names'] = [];
		foreach( usam_get_crm_objects() as $type => $item )
			$this->js_args['object_names'][$type] = ['single_name' => $item['single_name']];		
		return true;		
	}
	
	protected function data_default()
	{
		return [];
	}
	
	protected function add_form_data(  )
	{	
	
	}	
	
	protected function toolbar_buttons( ) 
	{ 		
		$url = remove_query_arg(['id']);
		?>
		<div class="action_buttons__button" v-if="data.id>0"><a :href="'<?php echo add_query_arg(['form' => 'view'], $url); ?>&id='+data.id" class="button"><?php _e('Посмотреть','usam'); ?></a></div>		
		<button type="button" class="button button-primary action_buttons__button" @click="saveForm(false)"><?php echo $this->title_save_button(); ?></button>
		<button type="button" class="button action_buttons__button" v-if="data.type=='task'" @click="saveForm(true)"><span v-if="data.id>0"><?php _e('Сохранить и создать еще','usam'); ?></span><span v-else><?php _e('Добавить и создать еще','usam'); ?></span></button>
		<?php	
		$links[] = ['action' => 'deleteItem', 'title' => __('Удалить', 'usam'), 'capability' => 'delete_'.$this->data['type'], 'if' => 'data.id>0'];		
		$this->display_form_actions( $links );
	}

	protected function form_class( ) 
	{ 
		return 'form_event';
	}
	
	protected function get_title_tab()
	{ 		
		$types = usam_get_events_types( );
		if ( !isset($types[$this->data['type']]) )
			$label = $types['task'];
		else
			$label = $types[$this->data['type']];		
		return "<span v-if='data.id==0'>".$label['message_add']."</span><span v-else>".$label['message_edit']." № $this->id ".__("от","usam").' '.usam_local_date( $this->data['date_insert'] )."</span>";		
	}	
		
	function display_event_toolbar()
	{
		?>
		<div class='event_toolbar'>
			<div class='event_toolbar__buttons'>				
				<div class='section_tab' @click="toolbar_tab='main'" :class="{'active':toolbar_tab=='main'}"><?php _e('Основные','usam'); ?></div>
				<?php if ( current_user_can('view_my_files') || current_user_can('view_all_files') ) { ?>
					<div class='section_tab' @click="toolbar_tab='file'" :class="{'active':toolbar_tab=='file'}">
						<span class="dashicons dashicons-paperclip"></span><?php _e('Файл','usam'); ?> <span v-if="files.length" class="number_events">{{files.length}}</span>
					</div>
				<?php } ?>
				<div class='section_tab' @click="toolbar_tab='remind'" :class="{'allocated':remind, 'active':toolbar_tab=='remind'}"><span class="dashicons dashicons-bell"></span><?php _e('Напомнить','usam'); ?><span v-if="remind"> {{localDate(data.reminder_date,'d.m.Y H:i')}}</span></div>
				<div class='section_tab' @click="toolbar_tab='repetition'" :class="{'allocated':data.schedule, 'active':toolbar_tab=='repetition'}" v-if="data.type=='task'"><span class="dashicons dashicons-controls-repeat"></span><?php _e('Повторение','usam'); ?></div>
				<div class='section_tab' @click="toolbar_tab='crm'" :class="{'active':toolbar_tab=='crm'}"><span class="dashicons dashicons-media-document"></span><?php _e('CRM','usam'); ?> <span v-if="crm.length" class="number_events">{{crm.length}}</span></div>				
			</div>		
			<div class="event_toolbar__content_tab event_toolbar__main" v-show="toolbar_tab=='main'">	
				<?php $this->main_option(); ?>
			</div>
			<div class='event_toolbar__content_tab event_toolbar__files' v-show="toolbar_tab=='file'">
				<?php $this->display_attachments(); ?>
			</div>
			<div class="event_toolbar__content_tab" v-show="toolbar_tab=='remind'">	
				<label><input type="checkbox" v-model="remind" value="1"/><?php esc_html_e( 'Напомнить', 'usam'); ?></label>
				<span v-show="remind"><datetime-picker :mindate='new Date()' v-model="data.reminder_date"></datetime-picker></span>						
			</div>	
			<div class="event_toolbar__content_tab event_toolbar__repetition select_replay_settings_wrapper" v-show="toolbar_tab=='repetition'">	
				<div class="event_toolbar__select_period">
					<?php 
					$schedule = ['' => __('Однократно','usam'), 'daily' => __('Ежедневно','usam'),  'weekly' => __('Еженедельно','usam'),  'monthly' => __('Ежемесячно','usam')]; 
					foreach( $schedule as $key => $name )
					{	
						?><label><input type="radio" v-model="data.schedule" name="schedule" value="<?php echo $key; ?>"/> <?php echo $name; ?></label><?php 	
					}
					?>	
				</div>								
				<div class = "period_daily period_schedule_data" v-show="data.schedule=='daily'">							
					<label><?php _e('повторять через','usam'); ?><input type="text" style="width:40px" v-model="data.to_repeat"/><?php _e('дней','usam'); ?></label>
				</div>
				<div class = "period_weekly period_schedule_data" v-show="data.schedule=='weekly'">
					<label><?php _e('повторять через','usam'); ?><input type="text" style="width:40px" v-model="data.to_repeat"/><?php _e('недель','usam'); ?></label>
					<br><br>
					<?php
					$weekday = ['1' => __('ПН','usam'), '2' => __('ВТ','usam'), '3' => __('СР','usam'), '4' => __('ЧТ','usam'), '5' => __('ПТ','usam'), '6' => __('СБ','usam'), '0' => __('ВС','usam')];
					foreach( $weekday as $key => $name )
					{	
						?><label><?php echo $name; ?><input type="checkbox" v-model="data.weekly_interval" value="<?php echo $key; ?>"/></label><?php
					}
					?>
				</div>
				<div class = "period_monthly period_schedule_data" v-show="data.schedule=='monthly'">
					<label><?php _e('Каждого','usam'); ?><input type="text" style="width:40px" v-model="data.to_repeat"/><?php _e('числа','usam'); ?> </label>	
					<select v-model="data.monthly_interval">
						<?php
						$month_name = [1 => __("Январь",'usam'), 2 => __("Февраль",'usam'), 3 => __("Март",'usam'), 4 => __("Апрель",'usam'), 5 => __("Май",'usam'), 6 => __("Июнь",'usam'), 7 => __("Июль",'usam'), 8 => __("Август",'usam'), 9 => __("Сентябрь",'usam'), 10 => __("Октябрь",'usam'), 11 => __("Ноябрь",'usam'), 12 => __("Декабрь",'usam')]; 
						foreach( $month_name as $key => $name )
						{	
							?><option value="<?php echo $key; ?>"><?php echo $name; ?></option><?php 	
						}
						?>
					</select>															
				</div>		
			</div>	
			<div class="event_toolbar__content_tab event_toolbar__crm" v-show="toolbar_tab=='crm'">
				<table class ="objects_table">
					<tbody>
						<tr v-for="type in objectsCRM">
							<td class="object_type">{{object_names[type].single_name}}:</td>
							<td>
								<div class ="object_lists">
									<span class ="object_name" v-for="(item, i) in crm" v-if="item.object_type==type">
										<?php include( usam_get_filepath_admin('templates/template-parts/objects.php') ); ?>
									</span>		
								</div>								
							</td>
						<tr>
						<tr>
							<td></td>
							<td><a @click="sidebar('objects', 'links')"><?php _e('выбрать','usam'); ?></a></td>
						<tr>
					</tbody>
				</table>				
			</div>			
		</div>
		<?php	
		add_action('usam_after_form',function() {
			require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-objects.php' );
		});
		usam_vue_module('list-table');		
	}	
	
	function main_option()
	{
		?>	
		<div class='edit_form'>
			<div class ="edit_form__item" v-show="timing_planning">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Начать', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<datetime-picker :mindate='new Date()' v-model="data.start"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Крайний срок', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<datetime-picker :mindate='data.start' v-model="data.end"></datetime-picker>
					<a @click="timing_planning=!timing_planning" v-if="!timing_planning" class="click_open"><?php esc_html_e( 'Планирование сроков', 'usam'); ?></a>
				</div>					
			</div>
			<div class ="edit_form__item" v-if="calendars.length>1">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Календарь', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name="calendar" v-model="data.calendar">
						<option v-for="calendar in calendars" :value="calendar.id" v-html="calendar.name"></option>
					</select>
				</div>	
			</div>								
		</div>
		<?php
	}
			
	function event_title( $title = '' )
	{
		?>
		<div class='event_form_head__title'>
			<div id="titlediv">			
				<input type="text" name="name" v-model="data.title" placeholder="<?php _e('Введите название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>					
			<div class="color_button" @click="color_open=!color_open">
				<ul class="color_select" v-if="color_open">
					<li v-for="color in colors" :class="['background_'+color, data.color==color?'select':'']" @click="data.color=color"><span class='color_block'></span></li>
				</ul>
				<div class="color_view" :class="'background_'+data.color"></div>
			</div>				
			<span class="dashicons dashicons-star-filled important" v-if="data.importance" @click="data.importance=0" title="<?php _e( 'Важное', 'usam'); ?>"></span>
			<span class="dashicons dashicons-star-empty" v-if="!data.importance" @click="data.importance=1" title="<?php _e( 'Важное', 'usam'); ?>"></span>				
		</div>
		<?php	
	}
		
	function display_left()
	{					
		?>	
		<div class='event_form_head'>			
			<?php				
				$this->event_title();
				$this->add_tinymce_description( $this->data['description'], 'description' );
				$this->display_event_toolbar();
			?>				
		</div>
		<?php
		$this->add_action_lists();
    }
	
	function display_right()
	{	
		?>
		<usam-box :id="'usam_sidebar_event'" :handle="false" :title="data.status_name">	
			<template v-slot:body>
				<div class="rows_data">
					<?php include( usam_get_filepath_admin('templates/template-parts/crm/task-users.php') ); ?>		
				</div>
			</template>
		</usam-box>			
		<usam-box :id="'usam_groups'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-groups.php' ); ?>
		</usam-box>	
		<?php
	}
}
?>