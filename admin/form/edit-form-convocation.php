<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-event.php' );	
class USAM_Form_convocation extends USAM_Form_Type_Event
{	
	protected $event_type = 'convocation';		
		
	protected function data_default()
	{
		return ['type' => 'convocation', 'venue' => ''];
	}
	
	protected function add_form_data(  )
	{	
		$this->data['venue'] = (string)usam_get_event_metadata( $this->id, 'venue');
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
			<div class='edit_form'>						
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Дата начала', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<datetime-picker v-model="data.start"></datetime-picker>
						<input type="hidden" name="start" v-model="data.start"/>
						<a @click="timing_planning=!timing_planning" class="click_open"><?php esc_html_e( 'Планирование срока окончания', 'usam'); ?></a>
					</div>					
				</div>
				<div class ="edit_form__item" v-show="timing_planning">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Окончание собрания', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<datetime-picker v-model="data.end"></datetime-picker>
						<input type="hidden" name="end" v-model="data.end"/>
					</div>
				</div>						
				<label class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Место', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type='text' v-model='data.venue' />
					</div>
				</label>
				<div class ="edit_form__item" v-if="calendars.length>1">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Календарь', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<select name="calendar" v-model="data.calendar">
							<option v-for="calendar in calendars" :value="calendar.id" v-html="calendar.name"></option>
						</select>
					</div>	
				</div>				
			</div>
		</div>
		<?php
		$request_solution = usam_get_event_metadata( $this->id, 'request_solution');	
		$this->add_box_description( $request_solution, 'request_solution', __('Результат встречи','usam') );
		$this->add_action_lists( __('Список вопросов','usam') );
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