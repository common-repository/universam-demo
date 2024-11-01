<?php	
require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_contacting extends USAM_Edit_Form
{			
	protected $vue = true;
	protected function get_data_tab()
	{			
		$default = [
			'id'          => 0,				
			'importance'  => 0, 
			'status'      => 'not_started', 		
			'manager_id'     => get_current_user_id(),
			'color'       => '', 			
			'actions'     => [],				
			'date_insert' => date("Y-m-d H:i:s" ),
			'groups'      => [],	
			'responsible' => 0,				
			'status_is_completed' => true,	
			'status_name' => '',	
		];	
		if ( $this->id != null )	
		{
			$this->data = usam_get_contacting( $this->id );							
			if ( empty($this->data) )
				return;
			if ( !current_user_can('edit_contacting') )
			{
				$this->data = [];
				return;
			} 			
			$this->data['status_name'] = usam_get_object_status_name( $this->data['status'], 'contacting' );			
			$this->change = $this->data['status'] == 'completed' || $this->data['status'] == 'canceled' || $this->data['status'] == 'controlled' ? false : $this->change;	
		}		
		$this->data = array_merge( $default, $this->data );	
		usam_vue_module('list-table');
		add_action('usam_after_edit_form',function() {
			include( usam_get_filepath_admin('templates/template-parts/modal-panel/modal-panel-employees.php') );
		});
		return true;		
	}
	
	protected function get_title_tab()
	{ 		
		$title = usam_get_event_type_name( 'contacting' );		
		return $title." № $this->id ".__("от","usam").' '.usam_local_date( $this->data['date_insert'] );
	}	
	
	function display_left()
	{					
		?>
		<div class='event_form_head'>			
			<div class='event_form_head__title'>
				<div id="titlediv"><div class="titlebox"><?php _e('Веб-форма','usam'); ?></div></div>							
				<span class="dashicons dashicons-star-filled important" v-if="data.importance" @click="data.importance=0" title="<?php _e( 'Важное', 'usam'); ?>"></span>
				<span class="dashicons dashicons-star-empty" v-if="!data.importance" @click="data.importance=1" title="<?php _e( 'Важное', 'usam'); ?>"></span>				
			</div>
			<?php	
				include( usam_get_filepath_admin('templates/template-parts/crm/webform.php') );
			?>			
		</div>
		<usam-box :id="'request_solution'" :handle="false" :title="'<?php _e( 'Решение вопроса', 'usam'); ?>'">
			<template v-slot:body>
				<div class ="form_description">			
					<textarea v-model="data.request_solution"></textarea>
				</div>
			</template>
		</usam-box>				
		<?php
    }	
		
	function display_right()
	{					
		?>
		<usam-box :id="'managers'" :handle="false" :title="'<?php _e( 'Ответственный', 'usam'); ?>'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-manager.php' ); ?>
		</usam-box>	
		<usam-box :id="'usam_groups'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-groups.php' ); ?>
		</usam-box>			
		<?php	
	}
	
	protected function toolbar_buttons( ) 
	{ 		
		$url = remove_query_arg(['id']);
		?>
		<div class="action_buttons__button" v-if="data.id>0"><a :href="'<?php echo add_query_arg(['form' => 'view'], $url); ?>&id='+data.id" class="button"><?php _e('Посмотреть','usam'); ?></a></div>		
		<button type="button" class="button button-primary action_buttons__button" @click="saveForm(false)"><?php echo $this->title_save_button(); ?></button>
		<?php	
		$links[] = ['action' => 'deleteItem', 'title' => __('Удалить', 'usam'), 'capability' => 'delete_contacting', 'if' => 'data.id>0'];		
		$this->display_form_actions( $links );
	}
}
?>