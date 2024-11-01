<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH .'/includes/seo/keywords_query.class.php' );	
require_once( USAM_FILE_PATH .'/includes/seo/keyword.class.php' );	
class USAM_Form_keyword extends USAM_Edit_Form
{	
	protected $vue = true;
	protected function get_title_tab()
	{ 
		return '<span v-if="data.id">'.__('Изменить ключевое слово', 'usam').'</span><span v-else>'.__('Добавить ключевое слово', 'usam').'</span> <button type="button" class="button" @click="saveForm(true)"><span v-if="data.id>0">'.__('Сохранить и создать еще','usam').'</span><span v-else>'.__('Добавить и создать еще','usam').'</span></button></span>';
	}
	
	public function get_data_tab() 
	{		
		if ( $this->id )
			$this->data = usam_get_keyword( $this->id );
		else
			$this->data = ['id' => 0, 'importance' => 0, 'keyword' => '', 'check' => 0, 'parent' => 0, 'source' => 'manager', 'link' => '', 'yandex_hits' => 0];	
	}
	
	public function display_left() 
	{					
		?>	
		<div id="titlediv">			
			<input type="text" name="name" v-model="data.keyword" placeholder="<?php _e('Введите ключевое слово', 'usam'); ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
		</div>
		<usam-box :id="'usam_main_settings_metabox'" :handle="false" :title="'<?php esc_html_e( 'Основные параметры', 'usam'); ?>'">
			<template v-slot:body>
				<div class="edit_form">	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Группа','usam'); ?>:</div>
						<div class ="edit_form__item_option">								
							<select v-model="data.parent">
								<option value='0' <?php selected( 0, $this->data['parent'] ) ?>><?php _e( 'Нет','usam'); ?></option>	
								<?php 
								$keywords = usam_get_keywords( );						
								foreach ( $keywords as $keyword )
								{	 ?>	
									<option value='<?php echo $keyword->id; ?>' <?php selected( $keyword->id, $this->data['parent'] ) ?>><?php echo $keyword->keyword; ?></option>	
								<?php 
								}
								?>	
							</select>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Источник','usam'); ?>:</div>
						<div class ="edit_form__item_option">								
							<select v-model="data.source">
								<?php 
								$sources = usam_get_keyword_sources();									
								foreach ( $sources as $key => $name )
								{	 ?>	
									<option value='<?php echo $key; ?>' <?php selected( $key, $this->data['source'] ) ?>><?php echo $name; ?></option>	
								<?php 
								}
								?>	
							</select>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e( 'Прикрепленная страница', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">		
							<input type="text" v-model="data.link">
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e( 'Показов в Яндексе', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">		
							<input type="text" v-model="data.yandex_hits">
						</div>
					</div>	
				</div>	
			</template>
		</usam-box>	
		<?php
	}	
	
	function display_right()
	{		
		?>	
		<usam-box :id="'usam_display_field'" :handle="false" :title="'<?php _e( 'Параметры ключевого слова', 'usam'); ?>'">
			<template v-slot:body>
				<div class="edit_form">
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Важность', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">				
							<select v-model="data.importance">								
								<option value='0' <?php selected( 0, $this->data['importance'] ); ?>><?php _e('Нет','usam'); ?></option>
								<option value='1' <?php selected( 1, $this->data['importance'] ); ?>><?php _e('Да','usam'); ?></option>
							</select>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e( 'Следить за позицией', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">									
							<select v-model="data.check">								
								<option value='0' <?php selected( 0, $this->data['check'] ); ?>><?php _e('Нет','usam'); ?></option>
								<option value='1' <?php selected( 1, $this->data['check'] ); ?>><?php _e('Да','usam'); ?></option>
							</select>
						</div>
					</div>
				</div>
			</template>
		</usam-box>	
		<?php
	}
}
?>