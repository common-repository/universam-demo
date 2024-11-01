<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_company extends USAM_Edit_Form
{		
	protected $vue = true;
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.parent_id"><span v-if="data.id" v-html="`'.sprintf( __('Изменить подразделение %s','usam'), '`+data.name' ).'"></span><span v-else>'.__('Добавить подразделение', 'usam').'</span></span><span v-else><span v-if="data.id" v-html="data.name"></span><span v-else>'.__('Добавить компанию', 'usam').'</span></span>';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_data_tab()
	{ 
		$user_id = get_current_user_id();
		$default = ['id' => 0, 'name' => '', 'parent_id' => 0, 'logo' => 0, 'image' => '', 'manager_id' => $user_id, 'open' => 1, 'type' => 'customer', 'industry' => 'other', 'employees' => '-50', 'status' => 'customer', 'currency' => get_option('usam_currency_type'), 'revenue' => '', 'type_price' => '', 'image' => '', 'groups' => []];
		if ( $this->id != null )
		{
			$this->data = usam_get_company( $this->id );
			$metas = usam_get_company_metadata( $this->id );
			if ( $metas )
				foreach($metas as $metadata )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);	
		}
		else
		{
			$parent_id = isset($_GET['parent_id'])?absint($_GET['parent_id']):0;		
			if ( $parent_id )
			{	
				$this->data = usam_get_company( $parent_id );			
				$this->data['parent_id'] = $parent_id;				
			}
		}		
		$this->data = usam_format_data( $default, $this->data );		
		if( !empty($this->data['date_insert']) )
			$this->data['date_insert'] = get_date_from_gmt( $this->data['date_insert'], "Y-m-d H:i" );	
		
		$this->js_args['image'] = ['url' => usam_get_company_logo( $this->id ), 'id' => (int)usam_get_company_metadata( $this->id, 'logo' )];		
		$this->js_args['company'] = usam_get_company( $this->data['parent_id'] );	
		$this->js_args['manager'] = ['id' => 0, 'appeal' => '', 'foto' => '', 'url' => ''];
		$contact = usam_get_contact( $this->data['manager_id'], 'user_id' );
		if( $contact )
		{
			$this->js_args['manager'] = $contact;
			$this->js_args['manager']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['manager']['url'] = usam_get_contact_url( $contact['id'] );	
		}
		if ( !$this->data['open'] && $this->data['manager_id'] != $user_id )
		{
			$this->not_exist = true;	
		}	
	}	
	
	protected function print_scripts_style() 
	{ 
		wp_enqueue_media();
	}
	
	protected function toolbar_buttons( ) 
	{ 		
		?>		
		<div class="action_buttons__button" v-if="data.id>0"><a :href="'<?php echo add_query_arg(['form' => 'view']); ?>&id='+data.id" class="button"><?php _e('Посмотреть','usam'); ?></a></div>
		<?php
		if ( current_user_can( 'edit_company' ) )
		{
			?>
			<button type="button" class="button button-primary action_buttons__button" @click="saveForm(false)"><?php echo $this->title_save_button(); ?></button>
			<button type="button" class="button button-primary action_buttons__button" @click="saveForm(true)"><span v-if="data.id>0"><?php _e('Сохранить и создать еще','usam'); ?></span><span v-else><?php _e('Добавить и создать еще','usam'); ?></span></button>
			<?php	
		}		
		$links[] = ['action' => 'getDataDirectory', 'title' => __('Заполнить', 'usam')];
		$links[] = ['action' => 'deleteItem', 'title' => __('Удалить', 'usam'), 'capability' => 'delete_company', 'if' => 'data.id>0'];		
		$this->display_form_actions( $links );
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
		
	function display_left()
	{	
		?>	
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>	
			<?php $this->add_tinymce_description( usam_get_company_metadata($this->id, 'description'), 'description' );	?>
			<div class="event_form_head__data_image">					
				<div class="edit_form event_form_head__data">					
					<div class="edit_form">					
						<div class ="edit_form__item" v-if="data.parent_id">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Основная компания', 'usam');  ?>:</div>
							<div class ="edit_form__item_option">
								<autocomplete :selected="company.name" @change="data.parent_id=$event.id" :request="'companies'"></autocomplete>		
							</div> 
						</div>				
						<div class ="edit_form__item" v-if="data.parent_id==0">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Тип компании', 'usam');  ?>:</div>
							<div class ="edit_form__item_option">
								<select v-model="data.type">
									<?php
									$types = usam_get_companies_types();
									foreach( $types as $id => $name )
									{
										?><option value="<?php echo $id; ?>"><?php echo $name; ?></option><?php 
									} 
									?>										
								</select>		
							</div> 
						</div>	
						<div class ="edit_form__item" v-if="data.type!=='own'">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
							<div class ="edit_form__item_option">
								<select v-model='data.status'>
									<option v-for="status in statuses" v-if="status.internalname == data.status || status.visibility" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
								</select>
							</div>			
						</div>
						<div class ="edit_form__item" v-if="data.parent_id==0 && data.type!=='own'">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Сфера деятельности', 'usam');  ?>:</div>
							<div class ="edit_form__item_option">
								<select v-model="data.industry">
									<?php
									$industry = usam_get_companies_industry();
									foreach( $industry as $id => $name )
									{
										?><option value="<?php echo $id; ?>"><?php echo $name; ?></option><?php 
									} 
									?>
								</select>		
							</div>
						</div>
						<div class ="edit_form__item" v-if="data.type!=='own'">
							<div class ="edit_form__item_name"><?php _e( 'Кол-во сотрудников', 'usam');  ?>:</div>
							<div class ="edit_form__item_option">
								<select v-model="data.employees">
									<option value="-50"><?php _e('менее 50','usam'); ?></option>
									<option value="250">50-250</option>
									<option value="500">250-500</option>
									<option value="+500"><?php _e('более 500','usam'); ?></option>
								</select>	
							</div>
						</div>
						<div class ="edit_form__item"  v-if="data.type!=='own'">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Годовой оборот', 'usam');  ?>:</div>
							<div class ="edit_form__item_option"><input type="text" v-model="data.revenue" autocomplete='off' size="20"></div>
						</div>
						<div class ="edit_form__item"  v-if="data.type!=='own'">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Доступна всем', 'usam');  ?>:</div>
							<div class ="edit_form__item_option">
								<input type="checkbox" v-model="data.open" value="1">
							</div>
						</div>		
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Тип цены', 'usam'); ?>:</div>
							<div class ="edit_form__item_option">							
								<select v-model="data.type_price">
									<option value=''><?php esc_html_e( 'Персональная цена не нужна', 'usam'); ?></option>
									<?php 
									$prices = usam_get_prices(['type' => 'R']);	
									foreach ( $prices as $price )
									{	
										?><option value='<?php echo $price['code']; ?>'><?php echo $price['title']; ?></option><?php 
									}
									?>	
								</select>
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
				</div>
				<div class="event_form_head__image">				 
					<wp-media v-model="data.logo" :file="image"></wp-media>
				</div>
			</div>
		</div>
		<?php $this->display_properties();
		?>
		<usam-box :id="'usam_company_accounts'" :handle="false" :title="'<?php _e('Банковские счета','usam'); ?>'">
			<template v-slot:body>
				<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/form-document.php' ); ?>
			</template>
		</usam-box>	
		<?php
    }	
}
?>