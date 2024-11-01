<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_phone extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id">'.__('Изменить телефон','usam').'</span><span v-else>'.__('Добавить телефон','usam').'</span>';
	}
	
	protected function get_data_tab(  )
	{		
		$default = ['id' => 0, 'name' => '', 'location_id' => 0, 'phone' => '', 'format' => '+9(999)999-99-99', 'sort' => 100, 'viber' => 0, 'whatsapp' => 0, 'telegram' => 0, 'skype' => 0];		
		if ( $this->id != null )
			$this->data = usam_get_data( $this->id, 'usam_phones' );	
		$this->data = array_merge( $default, $this->data );			
	}

	protected function form_attributes( )
    {
		?>v-cloak<?php
	}	

	function display_left()
	{			
		?>
		<div id="titlediv">			
			<input type="text" name="name" v-model="data.name" placeholder="<?php _e('Введите название', 'usam'); ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
		</div>
		<usam-box :id="'usam_settings'" :handle="false" :title="'<?php _e( 'Настройки телефона', 'usam'); ?>'">
			<template v-slot:body>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Телефон', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.phone" name="phone" size="15">
						</div>
					</div>			
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Формат', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.format" name="format" id="option_format" size="15">
						</div>
					</div>		
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Сортировка', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.sort" name="sort" id="option_sort" size="3">
						</div>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Местоположение', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<?php $location = usam_get_full_locations_name( $this->data['location_id'] ); ?>
							<span class="pointer" v-if="!select_location && data.location_id==0" @click="select_location=true"><?php esc_html_e( 'Указать местоположение', 'usam'); ?></span>	
							<span v-else>
								<autocomplete :selected="'<?php echo $location; ?>'" @change="data.location_id=$event.id" :request="'locations'"></autocomplete>
								<div class="pointer" @click="data.location_id=0"><?php esc_html_e( 'Для любого местоположение', 'usam'); ?></div>
							</span>
							<input type="hidden" v-model="data.location_id" name="location_id">													
						</div>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name">Whatsapp:</div>
						<div class ="edit_form__item_option">
							<selector v-model="data.whatsapp"></selector>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name">Viber:</div>
						<div class ="edit_form__item_option">
							<selector v-model="data.viber"></selector>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name">Telegram:</div>
						<div class ="edit_form__item_option">
							<selector v-model="data.telegram"></selector>
						</div>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name">Skype:</div>
						<div class ="edit_form__item_option">
							<selector v-model="data.skype"></selector>
						</div>
					</div>			
				</div>	
			</template>
		</usam-box>	
		
		<input type="hidden" v-model="data.whatsapp" name="whatsapp">		
		<input type="hidden" v-model="data.viber" name="viber">		
		<input type="hidden" v-model="data.telegram" name="telegram">		
		<input type="hidden" v-model="data.skype" name="skype">		
		<?php
    }
}
?>