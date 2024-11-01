<?php		
require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_employee extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.sprintf( __('Изменить сотрудника %s','usam'), '{{data.appeal}}' ).'</span><span v-else>'.__('Добавить нового сотрудника','usam').'</span>';;
	}
	
	protected function get_data_tab()
	{ 	
		$default = ['id' => 0, 'contact_source' => '', 'status' => 'customer', 'lastname' => '', 'firstname' => '', 'patronymic' => '', 'start_work_date' => '', 'manager_id' => '', 'user_id' => '', 'appeal' => 'lastname_firstname_patronymic', 'birthday' => '', 'company_id' => 0, 'date_insert' => date("Y-m-d H:i:s"), 'favorite_shop' => ['id' => 0, 'title' => ''], 'department' => 0, 'start_work_date' => ''];
		if ( isset($_GET['company_id']) )
			$default['company_id'] = absint($_GET['company_id']);		
		if ( $this->id != null )
		{
			$this->data = usam_get_contact( $this->id );
			if( $this->data['contact_source'] !== 'employee' )
			{
				$this->data = [];
				return false;
			}
			$metas = usam_get_contact_metadata( $this->id );
			if ( $metas )
				foreach($metas as $metadata )
				{
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
					if ( $metadata->meta_key == 'foto' )
						$this->data[$metadata->meta_key] = absint($this->data[$metadata->meta_key]);
				}
			$favorite_shop = usam_get_contact_metadata( $this->id, 'favorite_shop');
			if ( $favorite_shop )
				$this->data['favorite_shop'] = usam_get_storage( $favorite_shop );
		}
		$this->js_args['image'] = ['url' => usam_get_contact_foto( $this->id ), 'id' => usam_get_contact_metadata( $this->id, 'foto' )];
		$this->data = array_merge( $default, $this->data );	
		$this->js_args['company'] = usam_get_company( $this->data['company_id'] );
		$this->js_args['user'] = ['user_login' => ''];				
		if( $this->data['user_id'] )
		{
			$userdata = get_userdata( $this->data['user_id'] );
			if ( $userdata )
				$this->js_args['user'] = ['user_login' => $userdata->data->user_login];
		}
	}	
	
	protected function print_scripts_style() 
	{ 
		wp_enqueue_media();
	}
			
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
		
	protected function toolbar_buttons( ) 
	{ 		
		$url = remove_query_arg(['id'])
		?><div class="action_buttons__button" v-if="data.id>0"><a :href="'<?php echo add_query_arg(['form' => 'view'], $url); ?>&id='+data.id" class="button"><?php _e('Посмотреть','usam'); ?></a></div><?php
		if ( current_user_can( 'edit_employee' ) )
		{
			?>
			<button type="button" class="button button-primary action_buttons__button" @click="saveForm(false)"><?php echo $this->title_save_button(); ?></button>
			<button type="button" class="button action_buttons__button" @click="saveForm(true)"><span v-if="data.id>0"><?php _e('Сохранить и создать еще','usam'); ?></span><span v-else><?php _e('Добавить и создать еще','usam'); ?></span></button>
			<button type="button" class="button action_buttons__button" @click="data.contact_source=='employee'?data.contact_source='formeremployee':data.contact_source='employee'"><span v-if="data.contact_source=='employee'"><?php _e('Уволить','usam'); ?></span><span v-else><?php _e('Уволен','usam'); ?></span></button>
			<?php	
		}
		$links[] = ['action' => 'deleteItem', 'title' => __('Удалить', 'usam'), 'capability' => 'delete_employee', 'if' => 'data.id>0'];		
		$this->display_form_actions( $links );
	}
		
	function display_left()
	{				
		?>		
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="lfp" v-model="lfp" placeholder="<?php _e('ФИО', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<?php  
			$about = usam_get_contact_metadata($this->id, 'about');	
			$this->add_tinymce_description( $about, 'about' );			
			?>
			<div class="event_form_head__data_image">					
				<div class="edit_form event_form_head__data">	
					<div class="edit_form">		
						<?php include( usam_get_filepath_admin('templates/template-parts/form-contact-main-data.php') ); ?>	
						<?php
						if ( usam_check_current_user_role('administrator' ) )
						{
							?>
							<div class ="edit_form__item">
								<div class ="edit_form__item_name"><?php esc_html_e( 'Код', 'usam');  ?>:</div>
								<div class ="edit_form__item_option"><input type="text" v-model="data.code"></div>
							</div>	
							<?php
						}
						?>				
					</div>
				</div>
				<div class="event_form_head__image">
					<wp-media v-model="data.foto" :file="image"></wp-media>
				</div>
			</div>
		</div>
		<usam-box :id="'usam_place_of_work'" :handle="false" :title="'<?php _e( 'Место работы', 'usam'); ?>'">
			<template v-slot:body>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Название компании','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<autocomplete :selected="company.name" @change="companyChange" :request="'companies'"></autocomplete>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Отдел','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select-list @change="data.department=$event.id" :lists="departments" :selected="data.department"></select-list>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Должность','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.post">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Работает с','usam'); ?>:</div>
						<div class ="edit_form__item_option"><date-picker v-model="data.start_work_date"/></div>
					</div>
				</div>	
			</template>
		</usam-box>	
		<?php
    }	
}
?>