<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-event.php' );
class USAM_Form_project extends USAM_Form_Event
{	
	protected $ribbon = true;
	protected function header_view()
	{			
		$this->top_form( __('Результат','usam') );
		$this->add_action_lists();
	}
	
	protected function load_tabs()
	{
		$this->tabs = [
			['slug' => 'main', 'title'   => __('Основное','usam')],
		];
	}
		
	protected function toolbar_buttons( ) 
	{				
		?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php	
	}
	
	
	public function display_tab_main( )
	{
		?>
		<usam-box :id="'usam_sidebar_event'" :handle="false" :title="'<?php _e( 'Основное', 'usam'); ?>'">
			<template v-slot:body>
				<div class="edit_form">
					<div class ="edit_form__item" v-if="data.start">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Дата начала', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">{{localDate(data.start,'d.m.Y H:i')}}</div>
					</div>
					<div class ="edit_form__item" v-if="data.end">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Дата окончания', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">{{localDate(data.end,'d.m.Y H:i')}}</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">{{data.status_name}}</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Бюджет', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">{{data.budget}}</div>
					</div>
					<div class ="edit_form__item" v-for="type in objectsCRM">
						<div class ="edit_form__item_name">{{object_names[type].single_name}}</div>
						<div class ="edit_form__item_option" v-for="(item, i) in crm" v-if="item.object_type==type">
							<?php include( usam_get_filepath_admin('templates/template-parts/objects.php') ); ?>
						</div>
					</div>
				</div>					
			</template>
		</usam-box>	
		<usam-box :id="'usam_responsible'" :handle="false" :title="'<?php _e( 'Ответственные', 'usam'); ?>'">
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