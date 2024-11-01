<?php	
require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_department  extends USAM_Edit_Form
{	
	protected $vue = true;
	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.sprintf(__('Изменить отдел &#8220;%s&#8221;','usam'), '{{data.name}}' ).'</span><span v-else>'.__('Добавить отдел','usam').'</span>';
	}
	
	protected function get_data_tab(  )
	{		
		if ( !current_user_can( 'edit_department' ) )			
			$this->not_exist = true;	
		
		$default = ['id' => 0, 'name' => '', 'company' => 0, 'chief' => ''];
		if ( $this->id != null )
		{
			$this->data = usam_get_department( $this->id );
			if( !$this->data )
				return false;
		}
		$this->data = usam_format_data( $default, $this->data );
		$this->js_args['manager'] = ['id' => 0, 'appeal' => '', 'foto' => '', 'url' => ''];
		$contact = usam_get_contact( $this->data['chief'] );
		if( $contact )
		{
			$this->js_args['manager'] = $contact;
			$this->js_args['manager']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['manager']['url'] = usam_get_contact_url( $contact['id'] );	
		}		
	}
	
	function display_left()
	{		
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<div class="edit_form">
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php _e( 'Фирма','usam'); ?>:</div>
					<div class ="edit_form__item_option">							
						<select-list @change="data.company=$event.id" :lists="companies" :selected="data.company"></select-list>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name">
						<?php _e( 'Руководитель отдела','usam'); ?>:
						<div><a @click="sidebar('contacts')"><?php _e('изменить', 'usam'); ?></a>
					</div>
					</div>
					<div class ="edit_form__item_option" v-if="data.chief>0">
						<div class='user_block'>	
							<div class='user_foto'><a :href="manager.url" class='image_container usam_foto'><img :src='manager.foto'></a></div>	
							<a class='user_name':href="manager.url" v-html="manager.appeal"></a>	
						</div>
					</div>
					<div class ="edit_form__item_option" v-else>							
						<a @click="sidebar('contacts')"><?php _e('Не выбран руководитель', 'usam'); ?></a>
					</div>
				</div>
			</div>	
		<?php		
		usam_vue_module('list-table');
		add_action('usam_after_edit_form',function() {
			?>
			<modal-panel ref="modalcontacts">
				<template v-slot:title><?php _e('Выбор контактов', 'usam'); ?></template>
				<template v-slot:body="modalProps">
					<list-table :load="modalProps.show" :query="'contacts'" :args="args_contacts">
						<template v-slot:thead>
							<th class="column_title"><?php _e( 'Имя', 'usam'); ?></th>	
							<th></th>	
						</template>
						<template v-slot:tbody="slotProps">
							<tr v-for="(item, k) in slotProps.items" @click="selectContact(item);">
								<td class="column_title">
									<a class='user_block'>	
										<div class='image_container usam_foto'><img :src='item.foto'></div>
										<div class='user_name' v-html="item.appeal"></div>							
									</a>	
								</td>
								<td class="column_action"><button class="button"><?php _e( 'Выбрать', 'usam'); ?></button></td>
							</tr>
						</template>
					</list-table>
				</template>
			</modal-panel>
			<?php
		});
    }	
}
?>