<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-event.php' );
require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
class USAM_Form_convocation extends USAM_Form_Event
{	
	protected function header_view()
	{		
		$this->top_form( __('Результат встречи','usam') );
		$this->add_action_lists( __('Список вопросов','usam') );
	}		
	
	function display_right()
	{			
		?>	
		<usam-box :id="'usam_sidebar_event'" :handle="false" :title="data.status_name">
			<template v-slot:body>
				<div class="rows_data">
					<div class="rows_data__title"><?php _e( 'Дата встречи', 'usam'); ?></div>	
					<div class="rows_data__content">{{localDate(data.start,'d.m.Y H:i')}}</div>				
					<div class="rows_data__title"><?php _e( 'Место встречи', 'usam'); ?></div>	
					<div class="rows_data__content" v-html="data.venue"></div>
					<div class="rows_data__title"><?php _e( 'Окончание встречи', 'usam'); ?></div>	
					<div class="rows_data__content">{{localDate(data.end,'d.m.Y H:i')}}</div>
					<?php include( usam_get_filepath_admin('templates/template-parts/crm/task-users.php') ); ?>						
				</div>
			</template>
		</usam-box>	
		<?php
	}
}
?>