<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Storage extends USAM_Edit_Form
{		
	protected $vue = true;
	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id>0" v-html="`'.__('Изменить склад','usam').'`"></span><span v-else>'.__('Добавить пункт хранения товара', 'usam').'</span>';
	}
		
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function toolbar_buttons( ) 
	{ 
		if ( current_user_can( 'edit_contact' ) )
		{
			?><button type="button" class="button button-primary action_buttons__button" @click="saveForm(false)"><?php echo $this->title_save_button(); ?></button><?php	
		}
		$links[] = ['action' => 'deleteItem', 'title' => __('Удалить', 'usam'), 'if' => 'data.id>0'];		
		$this->display_form_actions( $links );
	}
	
	protected function get_data_tab()
	{			
		$default = ['id' => 0, 'active' => 1, 'issuing' => 1, 'type' => 'warehouse', 'location_id' => '', 'shipping' => 1, 'title' =>'', 'code' => '', 'sort' => 100, 'type_price' => usam_get_manager_type_price(), 'period_from' => '', 'period_to' => '', 'period_type' => '', 'image' => 0, 'images' => [], 'email' => '', 'phone' => '', 'schedule' => '', 'address' => '', 'index' => '', 'latitude' => '', 'longitude' => ''];
		$sales_area = usam_get_sales_areas();
		foreach ( $sales_area as $sale_area )
			$default['sale_area_'.$sale_area['id']] = '';	
		if ( $this->id != null )
		{
			$this->data = usam_get_storage( $this->id );
			$metas = usam_get_storage_metadata( $this->id );
			if ( $metas )
				foreach($metas as $metadata )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);	//htmlspecialchars		
		}
		$this->data = usam_format_data( $default, $this->data );			
		$this->js_args['location'] = usam_get_location( $this->data['location_id'] );
		$this->js_args['thumbnail'] = ['url' => (string)wp_get_attachment_image_url( $this->data['image'], 'full' )];	
		$this->js_args['images'] = usam_get_storage_images( $this->id );
	}
	
	protected function print_scripts_style() 
	{ 
		wp_enqueue_media();
	}
		
	function display_right()
	{
		?>
		<usam-box :id="'usam_status_active'" :title="'<?php _e( 'Статус активности', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<usam-checked v-model="data.active" :text="'<?php _e('Активно', 'usam'); ?>'"></usam-checked>
			</template>
		</usam-box>
		<usam-box :id="'usam_storage_type_settings'" :title="'<?php _e( 'Возвожности пункта', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<usam-checked v-model="data.shipping" :text="'<?php _e('Отгрузка - списание товара', 'usam'); ?>'"></usam-checked>
				<usam-checked v-model="data.issuing" :text="'<?php _e('Выдача товара', 'usam'); ?>'"></usam-checked>
			</template>
		</usam-box>
		<usam-box :id="'usam_imagediv'" :title="'<?php _e( 'Миниатюра для склада', 'usam'); ?>'" :handle="false">
			<template v-slot:body>	
				<wp-media v-model="data.image" :file="thumbnail"></wp-media>
			</template>
		</usam-box>
		<?php
	}
	
	function display_left()
	{
		?>
		<div class='event_form_head'>	
			<div class='event_form_head__title'>
				<input type="text" name="name" v-model="data.title" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>
			<?php  
			$description = htmlspecialchars(usam_get_storage_metadata( $this->id, 'description'));
			$this->add_tinymce_description( $description );
			?>
		</div>		
		<usam-box :id="'usam_storage_general_settings'" :title="'<?php _e( 'Общие настройки', 'usam'); ?>'" :handle="false">
			<template v-slot:body>		
				<div class="edit_form">	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Тип пункта', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select-list @change="data.type=$event.id" :lists="[{id:'shop', name:'<?php _e('Магазин', 'usam'); ?>'}, {id:'warehouse', name:'<?php _e('Склад', 'usam'); ?>'}, {id:'postmart', name:'<?php _e('Постамат', 'usam'); ?>'}]" :selected="data.type" :none="'<?php _e( 'Выберете','usam'); ?>'"></select-list>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='type_price'><?php esc_html_e( 'Тип цены', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<select-list @change="data.type_price=$event.id" :lists="prices" :selected="data.type_price" :none="'<?php _e( 'Выберете','usam'); ?>'"></select-list>
						</div>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_email'><?php esc_html_e( 'Email', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input type="text" id='option_email' name="email" v-model="data.email">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_phone'><?php esc_html_e( 'Телефон', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input type="text" id='option_phone' name="phone" size="45" v-model="data.phone">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_schedule'><?php esc_html_e( 'График работы', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<textarea rows="3" id="option_schedule" name="schedule" v-model="data.schedule"></textarea>
						</div>
					</div>			
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='usam_date_picker-start'><?php esc_html_e( 'Сроки доставки', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<?php _e( 'от', 'usam'); ?> <input type="text" maxlength = "3" size = "3" style='width:100px' name="period_from" v-model="data.period_from"> <?php _e( 'до', 'usam'); ?> <input type="text" maxlength = "3" size = "3" style='width:100px' name="period_to" v-model="data.period_to">
							<select name="period_type" style='width:100px' v-model="data.period_type">						
								<option value="day"><?php _e( 'день', 'usam'); ?></option>					
								<option value="month"><?php _e( 'месяц', 'usam'); ?></option>
								<option value="hour"><?php _e( 'час', 'usam'); ?></option>
							</select>		
						</div>
					</div>	
					<?php if ( usam_check_current_user_role( 'administrator' ) ) { ?>
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Внешний код', 'usam'); ?>:</label></div>
							<div class ="edit_form__item_option">
								<input type="text" id='option_code' name="code" v-model="data.code" size="45" />
							</div>
						</div>
					<?php } ?>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input type="text" id='option_sort' name="sort" v-model="data.sort" size="5">
						</div>
					</div>			
				</div>
			</template>
		</usam-box>
		<usam-box :id="'usam_storage_address_settings'" :title="'<?php _e( 'Адрес', 'usam'); ?>'" :handle="false">
			<template v-slot:body>
				<div class="edit_form">					
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='search_location_1'><?php esc_html_e( 'Город', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<autocomplete :selected="location.name" @change="data.location_id=$event.id" :request="'locations'"></autocomplete>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_address'><?php esc_html_e( 'Адрес', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<textarea rows="3" id="option_address" name="address" v-model="data.address"></textarea>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_phone'><?php esc_html_e( 'Индекс', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input type="text" id='option_phone' name="index" size="45" v-model="data.index">
						</div>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_gps_n'><?php esc_html_e( 'GPS широта', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input type="text" id='option_gps_n' name="latitude" size="15"  v-model="data.latitude">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_gps_s'><?php esc_html_e( 'GPS долгота', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input type="text" id='option_gps_s' name="longitude" size="15"  v-model="data.longitude">
						</div>
					</div>		
				</div>
			</template>
		</usam-box>	
		<usam-box :id="'usam_limitations'" :title="'<?php _e( 'Ограничения для этого склада', 'usam'); ?>'" :handle="false" v-show="sales_area.length">
			<template v-slot:body>
				<check-block :lists='sales_area' v-model="regions"/>
					<template v-slot:title><?php _e( 'Мультирегиональность', 'usam'); ?></template>
				</check-block>					
			</template>
		</usam-box>
		<usam-box :id="'usam_photo_gallery'" :title="'<?php _e( 'Фотографии', 'usam'); ?>'" :handle="false">
			<template v-slot:body>	
				<div class="photos">
					<div class="photo_gallery">
						<div class="image" v-for="(image, i) in images" draggable="true" @dragover="allowDrop($event, i)" @dragstart="drag($event, i)" @dragend="dragEnd($event, i)">
							<div class="image_container"><img loading='lazy' :src="image.full"></div>
							<a class="delete dashicons" @click="deleteMedia(i)"><?php _e('Удалить', 'usam'); ?></a>
							<input type="hidden" v-model="image.ID" name="image_gallery[]">
						</div>
					</div>
					<wp-media @change="addMedia" :file="{}" :multiple="1"></wp-media>
				</div>
			</template>
		</usam-box>
		<?php
	}		
}
?>