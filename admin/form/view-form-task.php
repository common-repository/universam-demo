<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-event.php' );
class USAM_Form_task extends USAM_Form_Event
{	
	protected function header_view()
	{		
		$this->top_form( __('Результат','usam') );		
		$this->add_action_lists();
	}
	
	function display_right()
	{			
		?>	
		<usam-box :id="'usam_sidebar_event'" :handle="false" :title="data.status_name">
			<template v-slot:body>
				<div class="rows_data">
					<div class="rows_data__title" v-if="data.start"><?php _e( 'Начать', 'usam'); ?></div>	
					<div class="rows_data__content" v-if="data.start">{{localDate(data.start,'d.m.Y H:i')}}</div>			
					<div class="rows_data__title" v-if="data.end"><?php _e( 'Срок', 'usam'); ?></div>	
					<div class="rows_data__content" v-if="data.end">{{localDate(data.end,'d.m.Y H:i')}}</div>						
					<?php include( usam_get_filepath_admin('templates/template-parts/crm/task-users.php') ); ?>	
				</div>	
				<div class="rows_data" v-for="type in objectsCRM">
					<div class="rows_data__title">{{object_names[type].single_name}}</div>	
					<div class="rows_data__content">
						<span class ="object_name" v-for="(item, i) in crm" v-if="item.object_type==type">
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