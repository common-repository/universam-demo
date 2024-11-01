<?php	
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_Event extends USAM_View_Form
{		
	protected function get_data_tab()
	{ 	
		$this->data = usam_get_event( $this->id );			
		if( !$this->data )
			return;
		if ( !current_user_can('view_'.$this->data['type']) || !usam_check_event_access( $this->data, 'view' ) )
		{
			$this->data = [];
			return;
		}
		if ( $this->data['end'] || $this->data['start'] )
		{
			$timestamp = strtotime( $this->data['start'] );	
			$this->data['time_diff'] = human_time_diff( $timestamp, strtotime( $this->data['end'] ) );	
		}	
		else
			$this->data['time_diff'] = 0;
		$this->data['status_name'] = usam_get_object_status_name( $this->data['status'], $this->data['type'] );
		$this->js_args['users'] = [];
		$event_users = usam_get_event_users( $this->id );				
		$this->data['responsible'] = !empty($event_users['responsible'])?$event_users['responsible'][0]:0;
		$user_ids = usam_get_event_users( $this->id, false );			
		if( $user_ids )
		{
			$contacts = usam_get_contacts(["user_id" => $user_ids, 'source' => 'all', 'number' => 100, 'cache_thumbnail' => true, 'cache_meta' => true]);
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
		$this->load_tabs();
	}	
	
	protected function load_tabs()
	{
		$this->tabs = [ 
			['slug' => 'comments', 'title' => __('Комментарии','usam')],	
			['slug' => 'change', 'title' => __('Изменения','usam')],					
		];
	}
	
	protected function get_title_tab()
	{ 		
		$title = usam_get_event_type_name( $this->data['type'] );		
		return $title." № $this->id ".__("от","usam").' '.usam_local_date( $this->data['date_insert'] );
	}	
	
	protected function form_attributes( )
    {		
		?>v-cloak<?php
	}
	
	protected function form_class( ) 
	{ 
		return 'form_event';
	}
		
	protected function toolbar_buttons( ) 
	{	
		?>	
		<div class="action_buttons__button" v-if="rights.edit_status && (data.status=='not_started' || data.status=='stopped' || data.status=='canceled')"><button type='submit' class='button button-primary' @click="data.status='started'"><?php _e('Начать выполнять', 'usam'); ?></button></div>
		<div class="action_buttons__button" v-if="rights.edit_status && (data.type=='task' && data.status=='started' && data.status!='not_started')"><button type='submit' class='button' @click="data.status='stopped'"><?php _e('Приостановить', 'usam'); ?></button></div>
		<div class="action_buttons__button" v-if="rights.edit_status && (data.status!='completed' && data.status!='canceled' && data.status!='stopped')"><button type='submit' class='button' @click="data.status='canceled'"><?php _e('Отменить', 'usam'); ?></button></div>
		<div class="action_buttons__button" v-if="rights.edit_status && (data.status!='completed' && data.status!='not_started')"><button type='submit' class='button button-primary' @click="data.status='completed'"><?php _e('Завершить', 'usam'); ?></button></div>
		<?php		
		if( usam_check_event_access( $this->data, 'edit' ) )
		{
			?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button" v-if="data.status!='completed'"><?php _e('Изменить','usam'); ?></a></div><?php
		}
	}
	
	protected function top_form( $title )
	{
		?>
		<div class = "top_lines border_<?php echo $this->data['color']; ?>">				
			<div class = "top_lines__row top_lines__title">
				<div class = "top_lines__text" v-html="data.title"></div>
				<div class = "top_lines__importance">
					<span class="dashicons dashicons-star-filled importance important" v-if="data.importance" @click="data.importance=!data.importance"></span>
					<span class="dashicons dashicons-star-empty importance" @click="data.importance=!data.importance" v-else></span>
					<span class="dashicons dashicons-bell" v-if="data.reminder_date" :title="localDate(data.reminder_date,'d.m.Y H:i')"></span>
				</div>	
			</div>
			<div class = "top_lines__description" v-if="data.description" v-html='data.description.replace(/\n/g,"<br>")'></div>
			<div class = "top_lines__row top_lines__title" v-if="data.request_solution">
				<div class = "top_lines__text"><?php echo $title; ?></div>
			</div>
			<div class = "main_content_footer" v-if="data.request_solution" v-html='data.request_solution.replace(/\n/g,"<br>")'></div>	
			<?php $this->display_attachments( 'v-if="files.length"' ); ?>		
		</div>			
		<?php	
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
		<?php
	}
}
?>