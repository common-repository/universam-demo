<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Property extends USAM_Edit_Form
{		
	protected $property_type = '';
	protected $groups = array();
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id>0">'.__('Изменить поле','usam').' &laquo;{{data.name}}&raquo;</span><span v-else>'.__('Добавить поле','usam').'</span>';
	}	
	
	protected function form_attributes( ) { ?>v-cloak<?php }
	
	protected function form_class( ) 
	{ 
		return 'edit_form_property';
	}
	
	protected function get_data_tab(  )
	{				
		$default = ['name' => '', 'description' => '', 'group' => '', 'active' => 1, 'profile' => 1, 'mandatory' => 0, 'code' => '', 'field_type' => 'text', 'mask' => '', 'sort' => '', 'show_staff' => 1, 'name_agreement' => '', 'agreement' => '', 'url' => '', 'button_name' => '', 'file_types' => '', 'options' => [], 'registration' => 0, 'type' => $this->property_type, 'roles' => []];
		if ( $this->id != null )
		{
			$this->data = usam_get_property( $this->id );
			if ( empty($this->data) )
				return;
			$metadatas = usam_get_property_metadata( $this->id );
			foreach($metadatas as $metadata )
				$this->data[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
			$this->data['roles'] = usam_get_array_metadata($this->id, 'property', 'role');	
		}			
		$this->data = usam_format_data( $default, $this->data );	
		$this->groups = usam_get_property_groups(['type' => $this->data['type']]);			
		if ( empty($this->data['options']) )
			$this->data['options'][] = ['name' => '', 'code' => '', 'group' => ''];
	}	
	
	protected function print_scripts_style() 
	{ 
		wp_enqueue_media();
	}
	
	public function display_settings()	
	{  				
		?>		
		<div class="edit_form">			
			<label class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Код','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type='text' name='code' v-model="data.code"/>
				</div>
			</label>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Группа','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name="group" v-model="data.group">				
						<?php 					
						foreach ( $this->groups as $group )			
						{									
							?><option value="<?php echo $group->code; ?>"><?php echo "$group->name ($group->code)"; ?></option><?php
						}									
						?>	
					</select>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='property_mandatory'><?php _e( 'Обязательное','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' v-model="data.mandatory" id='property_mandatory' name='mandatory' value="1"/>
				</div>
			</div>			
			<label class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Ограничить видимость поля','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type='checkbox' v-model="limit_visibility" name='limit_visibility' value="1">
				</div>
			</label>			
			<div class ="edit_form__item" v-show="limit_visibility">
				<div class ="edit_form__item_name"><?php _e( 'Видит это поле','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data.roles=$event.id" :multiple='1' :lists="roles" :selected="data.roles"></select-list>		
					<div><input type="hidden" name="roles[]" :value="id" v-for="id in data.roles"></div>					
				</div>
			</div>
			<label class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Показывать в админке','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type='checkbox' v-model="data.show_staff" name='show_staff' value="1"/>
				</div>
			</label>
			<label class ="edit_form__item" v-if="data.type=='contact' || data.type=='company'">
				<div class ="edit_form__item_name"><?php _e( 'Показать в личном кабинете','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type='checkbox' name='profile' value="1" v-model="data.profile"/>
				</div>
			</label>
			<div class ="edit_form__item" v-if="data.type=='contact' || data.type=='company'">
				<div class ="edit_form__item_name"><label for='property_registration'><?php _e( 'Показать при регистрации','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' v-model="data.registration" id='property_registration' name='registration' value="1">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='property_sort'><?php _e( 'Сортировка','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' v-model="data.sort" id='property_sort' name='sort'/>
				</div>
			</div>
		</div>
      <?php
	} 
		
	public function display_connection( )
	{
		$connection = usam_get_property_metadata($this->id, 'connection');
		if ( $this->data['field_type'] == 'company' )
			$field_type = 'text';
		else
			$field_type = $this->data['field_type'];
		?>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='field_property_connection'><?php esc_html_e( 'Заполнять реквизит', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<select name = "connection" id="field_connection">						
					<option value="" <?php selected( $connection, '' ) ?> ><?php _e('Не заполнять','usam'); ?></option>
				<?php				
				$properties = usam_get_properties( ['type' => ['contact', 'company'], 'orderby' => ['group', 'type','name'], 'field_type' => $field_type]);
				$groups = usam_get_property_groups( ['type' => ['contact', 'company'], 'fields' => 'code=>name', 'orderby' => 'sort', 'order' => 'ASC'] );
				$group = '';
				foreach( $properties as $property )
				{						
					$customer_type = $property->type == 'contact'? __('Реквизит контакта','usam'): __('Реквизит компании','usam');
					$key = $property->type == 'contact'?$property->code:$property->type.'-'.$property->code;
					if ( isset($groups[$property->group]) && $property->group != $group )
					{
						if ( $group != '' )
						{
							?></optgroup><?php
						}
						?><optgroup label="<?php echo $groups[$property->group]; ?>"><?php
					}
					$group = $property->group;
					?><option value="<?php echo $key; ?>" <?php selected($connection, $key) ?> ><?php echo "$customer_type - $property->name ( $property->code )"; ?></option><?php
				}
				?></optgroup><?php
				if ( $field_type == 'text' )
				{
					?><option value="full_name" <?php selected( $connection, 'full_name' ) ?> ><?php _e('ФИО клиента','usam'); ?></option><?php
				}
				?>
			</select>	
			</div>
		</div>
		<?php
	}
	
	public function display_data_type( )
	{			
		$field_types = usam_get_field_types();	
		$options = usam_get_property_metadata($this->id, 'options');	
		?>
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select v-model="data.field_type" name="field_type">				
						<?php 					
						foreach ( $field_types as $key => $name )			
						{									
							?><option value="<?php echo $key; ?>"><?php echo $name; ?></option><?php
						}									
						?>	
					</select>
				</div>
			</div>
			<div class ="edit_form__item" v-if="data.field_type=='text' || data.field_type=='mobile_phone' || data.field_type=='phone'"> 
				<div class ="edit_form__item_name"><?php _e( 'Маска заполнения','usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type='text' name='mask' v-model="data.mask">
				</div>
			</div>
			<div class ="edit_form__item" v-if="data.field_type=='button'">
				<div class ="edit_form__item_name"><label for='field_type'><?php esc_html_e( 'Ссылка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" name="metas[url]" v-model="data.url"/>
				</div>
			</div>
			<div class ="edit_form__item" v-if="data.field_type=='button'">
				<div class ="edit_form__item_name"><label for='field_type'><?php esc_html_e( 'Название кнопки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" name="metas[button_name]" v-model="data.button_name"/>
				</div>
			</div>
			<div class ="edit_form__item" v-if="data.field_type=='file' || data.field_type=='files'">
				<div class ="edit_form__item_name"><label for='property_file_types'><?php _e( 'Возможные расширения файлов','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='property_file_types' name='metas[file_types]' v-model="data.file_types"/>
					<div class="description"><?php _e( 'Укажите через запятую','usam'); ?></div>
				</div>
			</div>
			<div class ="edit_form__item" v-if="data.field_type=='agreement'">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Название окна соглашения', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="metas[name_agreement]" v-model="data.name_agreement"/>
				</div>
			</div>					
			<div class ="edit_form__item" v-if="data.field_type=='select' || data.field_type=='radio' || data.field_type=='checkbox'">	
				<div style="width: 100%;">
					<h3><?php esc_html_e( 'Возможные варианты выбора', 'usam'); ?></h3>						
					<usam-box :id="'usam_table_rate'" :handle="false" :title="'<?php _e( 'Таблица скидок', 'usam'); ?>'">
						<template v-slot:body>
							<level-table :lists='data.options'>				
								<template v-slot:thead="slotProps">
									<th><?php _e('Название', 'usam'); ?></th>
									<th><?php _e( 'Код', 'usam'); ?></th>
									<th><?php _e( 'Показать группу', 'usam'); ?></th>			
									<th></th>	
								</template>			
								<template v-slot:tbody="slotProps">
									<td class="column-name">									
										<input type="text" name="options[name][]" v-model="slotProps.row.name"/>
									</td>
									<td class="column-code">
										<input type="text" name="options[code][]" v-model="slotProps.row.code"/>
									</td>
									<td class="column-group">
										<select name="options[group][]" v-model="slotProps.row.group">				
											<option value="0"><?php _e( 'Не использовать', 'usam'); ?></option>
											<?php 					
											foreach ( $this->groups as $group )			
											{									
												if ( $this->data['group'] != $group->code )
													?><option value="<?php echo $group->code; ?>"><?php echo $group->name.' ['.$group->code.' - '.$group->id.']'; ?></option><?php
											}									
											?>	
										</select>
									</td>
									<td class="column_actions">											
										<?php usam_system_svg_icon("drag", ["draggable" => "true"]); ?>
										<?php usam_system_svg_icon("plus", ["@click" => "slotProps.add(slotProps.k)"]); ?>
										<?php usam_system_svg_icon("minus", ["@click" => "slotProps.del(slotProps.k)"]); ?>
									</td>
								</template>
							</level-table>
						</template>
					</usam-box>	
				</div>
			</div>
		</div>
		<div v-show="data.field_type=='agreement'">
			<h4><?php esc_html_e( 'Соглашения, которое нужно принять', 'usam'); ?></h4>
			<tinymce v-model="data.agreement"></tinymce>			
		</div>
      <?php
	}
	
	function display_left()
	{						
		$this->titlediv( $this->data['name'] );	
		$this->add_box_description( $this->data['description'] );
		usam_add_box( 'usam_document_setting', __('Параметры','usam'), [$this, 'display_settings'] );		
		usam_add_box( 'usam_data_type', __('Тип данных','usam'), [$this, 'display_data_type'] );	
    }	
	
	function display_right()
	{						
		$this->add_box_status_active( $this->data['active'] );
    }
}
?>