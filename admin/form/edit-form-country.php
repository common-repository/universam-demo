<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_country extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.id>0">'.__('Изменить страну','usam').' &laquo;{{data.name}}&raquo;</span><span v-else>'.__('Добавить страну','usam').'</span>';
	}
	
	protected function get_data_tab(  )
	{	
		$default = ['name' => '', 'continent' => '', 'currency' => '', 'code' => '', 'numerical' => '', 'language' => '', 'language_code' => '', 'phone_code' => '', 'location_id' => 0];
		if ( $this->id != null )
		{			
			$this->data = usam_get_country( $this->id );
		}
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
		<usam-box :id="'usam_settings'" :handle="false" :title="'<?php _e( 'Параметры', 'usam'); ?>'">
			<template v-slot:body>
				<div class="edit_form" >
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_isocode'><?php esc_html_e( 'Буквенный код ISO', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input id="option_isocode" type="text" maxlength='2' required name="code" v-model="data.code">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_numerical'><?php esc_html_e( 'Числовой код ISO', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input id="option_numerical" type="text" maxlength='3' name="numerical" v-model="data.numerical">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Буквенный код ISO валюты', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input id="option_code" type="text" maxlength='3' name="currency" v-model="data.currency">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_language'><?php esc_html_e( 'Язык', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input id="option_language" type="text" name="language" v-model="data.language">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_language_code'><?php esc_html_e( 'Код языка', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input id="option_language_code" type="text" name="language_code" v-model="data.language_code">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_phone_code'><?php esc_html_e( 'Телефонный код', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input id="option_phone_code" type="text" name="phone_code" v-model="data.phone_code">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_location'><?php esc_html_e( 'Привязать к местоположению', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<?php 
							$location = usam_get_full_locations_name( $this->data['location_id'] );
							?>
							<autocomplete :selected="'<?php echo $location; ?>'" @change="data.location_id=$event.id" :request="'locations'"></autocomplete>
							<input type="hidden" v-model="data.location_id" name="location_id">							
						</div>
					</div>
				</div>		
			</template>
		</usam-box>	
		<?php 	
    }	
}
?>