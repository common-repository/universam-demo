<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-event.php' );	
class USAM_Form_call extends USAM_Form_Type_Event
{			
	protected $event_type = 'call';		
	
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
		$this->add_box_description( $request_solution, 'request_solution', __('Результат звонка','usam') );					
		$this->add_action_lists();
    }	
	
	function display_right()
	{	
		?>	
		<usam-box :id="'usam_sidebar_event'" :handle="false" :title="data.status_name">		
			<template v-slot:body>
				<div class="rows_data">
					<?php include( usam_get_filepath_admin('templates/template-parts/crm/task-users.php') ); ?>				
					<div class="rows_data__title"><?php _e( 'Когда', 'usam'); ?></div>	
					<div class="rows_data__content">
						<datetime-picker v-model="data.start"></datetime-picker>
					</div>	
					<div class="rows_data__title"><?php _e( 'С кем', 'usam'); ?></div>	
					<div class="rows_data__content">
						<span class ="object_name" v-for="(item, i) in crm" v-if="item.object_type=='contact' || item.object_type=='company'">
							<?php include( usam_get_filepath_admin('templates/template-parts/objects.php') ); ?>
						</span>	
					</div>
				</div>
			</template>
		</usam-box>	
		<?php
	}	
}
?>