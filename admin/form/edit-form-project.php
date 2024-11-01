<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-event.php' );	
class USAM_Form_project extends USAM_Form_Type_Event
{	
	protected $event_type = 'project';		
	protected function data_default()
	{
		return ['type' => 'project', 'budget' => ''];
	}
	
	protected function add_form_data(  )
	{	
		$this->data['budget'] = (string)usam_get_event_metadata( $this->id, 'budget');
	}
	
	function main_option()
	{
		?>	
		<div class='edit_form'>	
			<div class ="edit_form__item" v-show="timing_planning">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Начать', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<datetime-picker v-model="data.start"></datetime-picker>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Крайний срок', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<datetime-picker v-model="data.end"></datetime-picker>
					<a @click="timing_planning=!timing_planning" v-if="!timing_planning" class="click_open"><?php esc_html_e( 'Планирование сроков', 'usam'); ?></a>
				</div>					
			</div>				
			<label class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Бюджет', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<input type='text' v-model="data.budget"/>
				</div>
			</label>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип проекта', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select id="type_project" v-model="data.type">						
						<option value="project"><?php esc_html_e( 'Открытый проект', 'usam'); ?></option>
						<option value="closed_project"><?php esc_html_e( 'Закрытый проект', 'usam'); ?></option>
					</select>					
				</div>
			</div>				
		</div>
		<?php		
	}
}
?>