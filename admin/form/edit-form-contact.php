<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_contact extends USAM_Edit_Form
{
	protected $vue = true;
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id>0" v-html="data.appeal"></span><span v-else>'.__('Добавить новый контакт', 'usam').'</span>';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function toolbar_buttons( ) 
	{ 		
		$url = remove_query_arg(['id'])
		?><div class="action_buttons__button" v-if="data.id>0"><a :href="'<?php echo add_query_arg(['form' => 'view'], $url); ?>&id='+data.id" class="button"><?php _e('Посмотреть','usam'); ?></a></div><?php
		if ( current_user_can( 'edit_contact' ) )
		{
			?>
			<button type="button" class="button button-primary action_buttons__button" @click="saveForm(false)"><?php echo $this->title_save_button(); ?></button>
			<button type="button" class="button button-primary action_buttons__button" @click="saveForm(true)"><span v-if="data.id>0"><?php _e('Сохранить и создать еще','usam'); ?></span><span v-else><?php _e('Добавить и создать еще','usam'); ?></span></button>
			<?php	
		}
		$links[] = ['action' => 'data.contact_source=`employee`', 'title' => __('Перевести в сотрудники', 'usam'), 'capability' => 'view_employees', 'if' => 'data.id>0 && data.user_id>0'];
		$links[] = ['action' => 'deleteItem', 'title' => __('Удалить', 'usam'), 'capability' => 'delete_contact', 'if' => 'data.id>0'];		
		$this->display_form_actions( $links );
	}
	
	protected function print_scripts_style() 
	{ 
		wp_enqueue_media();
	}
		
	protected function get_data_tab()
	{ 					
		$user_id = get_current_user_id();
		$default = ['id' => 0, 'status' => 'customer', 'lastname' => '', 'firstname' => '', 'patronymic' => '', 'manager_id' => $user_id, 'user_id' => 0, 'open' => 1, 'appeal' => '', 'birthday' => '', 'contact_source' => 'self', 'company_id' => 0, 'foto' => 0, 'post' => '', 'type_price' => '', 'groups' => [], 'code' => '', 'favorite_shop' => 0, 'sex' => ''];	
		if ( $this->id != null )
		{
			$this->data = usam_get_contact( $this->id );
			$metas = usam_get_contact_metadata( $this->id );
			if ( $metas )
				foreach($metas as $metadata )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
		}
		$this->data = usam_format_data( $default, $this->data );
		if( !empty($this->data['date_insert']) )
			$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i" );			
		if( !empty($this->data['birthday']) )
			$this->data['birthday'] = get_date_from_gmt( $this->data['birthday'], "Y-m-d H:i" );		
		if ( !$this->data['open'] && $this->data['manager_id'] != $user_id && $this->data['manager_id'] || !current_user_can('edit_contact') )
			$this->not_exist = true;					
		$this->js_args['image'] = ['url' => usam_get_contact_foto( $this->id ), 'id' => usam_get_contact_metadata( $this->id, 'foto' )];		
		$this->js_args['manager'] = ['id' => 0, 'appeal' => '', 'foto' => '', 'url' => ''];
		$contact = usam_get_contact( $this->data['manager_id'], 'user_id' );
		if( $contact )
		{
			$this->js_args['manager'] = $contact;
			$this->js_args['manager']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['manager']['url'] = usam_get_contact_url( $contact['id'] );	
		}
		$this->js_args['company'] = usam_get_company( $this->data['company_id'] );
		$this->js_args['user'] = ['user_login' => ''];				
		if( $this->data['user_id'] )
		{
			$userdata = get_userdata( $this->data['user_id'] );
			if ( $userdata )
				$this->js_args['user'] = ['user_login' => $userdata->data->user_login];
		}
		if( $this->data['favorite_shop'] )
			$this->js_args['favorite_shop'] = usam_get_storage( $this->data['favorite_shop'] );
		else
			$this->js_args['favorite_shop'] = ['id' => 0, 'title' => ''];
	}		
		
	public function display_main_data( )
	{       	
		$prices = usam_get_prices(['type' => 'R']);			
		?>	
		<div class="edit_form">						
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип цены', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select v-model = "data.type_price">
						<option value=''><?php esc_html_e( 'Персональная цена не нужна', 'usam'); ?></option>
						<?php 
						foreach ( $prices as $price )
						{	
							?><option value='<?php echo $price['code']; ?>'><?php echo $price['title']; ?></option><?php 
						}
						?>	
					</select>
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e('Любимый магазин', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<autocomplete :selected="favorite_shop.title" @change="data.favorite_shop = $event.id" :request="'storages'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Не выбран','usam'); ?>'"></autocomplete>
					<a @click="data.favorite_shop=0, favorite_shop.title=''" v-if="data.favorite_shop>0"><?php esc_html_e('Удалить любимый магазин', 'usam'); ?></a>
				</div>
			</div>				
		</div>
      <?php
	}     
				
	function display_right()
	{		
		usam_add_box(['id' => 'usam_main_data_document', 'title' => __('Цены и остатки','usam'), 'function' => [$this, 'display_main_data'], 'close' => false]);
		?>
		<usam-box :id="'managers'" :handle="false" :title="'<?php _e( 'Ответственный', 'usam'); ?>'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-manager.php' ); ?>
		</usam-box>
		<usam-box :id="'usam_groups'">
			<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/select-groups.php' ); ?>			
		</usam-box>		
		<?php
	}  
	
	function display_left()
	{		
		?>		
		<div class='event_form_head'>			
			<div class='event_form_head__title'>
				<input type="text" v-model="lfp" placeholder="<?php _e('ФИО', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<?php  
			$about = usam_get_contact_metadata($this->id, 'about');	
			$this->add_tinymce_description( $about, 'about' );
			?>
			<div class="event_form_head__data_image">					
				<div class="edit_form event_form_head__data">	
					<?php include( usam_get_filepath_admin('templates/template-parts/form-contact-main-data.php') ); ?>					
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Источник','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select v-model="data.contact_source">
								<?php 
								$option = get_option('usam_crm_contact_source', array() );
								$contact_sources = maybe_unserialize( $option );
								foreach ( $contact_sources as $contact_source )
								{	 
									?><option value='<?php echo $contact_source['id']; ?>'><?php echo $contact_source['name']; ?></option><?php 
								}
								?>	
							</select>
						</div>
					</div>									
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Доступен всем','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="checkbox" v-model="data.open" value="1">
						</div>
					</div>	
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
						<div class ="edit_form__item_name"><?php _e( 'Должность','usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.post">
						</div>
					</div>
				</div>	
			</template>
		</usam-box>	
		<?php			
		$this->display_properties();
    }	
}
?>