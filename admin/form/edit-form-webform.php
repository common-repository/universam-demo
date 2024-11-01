<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
class USAM_Form_webform extends USAM_Edit_Form
{		
	//protected $vue = true;			
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить веб-форму &#171;%s&#187;','usam'), $this->data['title'] );
		else
			$title = __('Добавить веб-форму', 'usam');		
		return $title;
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_data_tab(  )
	{			
		$default = ['id' => 0, 'title' => '', 'start_date' => '', 'end_date' => '', 'active' => 0, 'code' => '', 'action' => '', 'actuation_time' => '', 'type' => '', 'template' => 'contact-form', 'language' => '', 'settings' => ['description' => '', 'modal_button_name' => __('Отправить', 'usam'), 'button_name' => __('Отправить', 'usam'), 'result_message' => '', 'buttonCSS' => ['text-align' => 'center', 'width' => '', 'height' => 'auto', 'background-color' => '', 'color' => '', 'font-size' => '', 'font-weight' => '', 'line-height' => '', 'border-color' => '', 'border-style' => '', 'border-width' => '', 'border-radius' => '', 'text-decoration' => '', 'padding' => '', 'text-transform' => ''], 'payment_gateway' => '', 'fields' => []]];
		if ( $this->id != null )
		{
			$this->data = usam_get_webform( $this->id );					
		}
		$this->data = usam_format_data( $default, $this->data );		
		if( !empty($this->data['start_date']) )
			$this->data['start_date'] = get_date_from_gmt( $this->data['start_date'], "Y-m-d H:i" );	
		if( !empty($this->data['end_date']) )
			$this->data['end_date'] = get_date_from_gmt( $this->data['end_date'], "Y-m-d H:i" );
		$properties = usam_get_properties(['type' => 'webform', 'active' => 1, 'orderby' => ['group', 'name']]);
		foreach( $properties as $k => $property )
		{
			$properties[$k]->value = empty($this->data['settings']['fields']) && $k < 4 ? '' : '-';
			$properties[$k]->require = 0;	
		}		
		$webform_properties = [];
		foreach( $this->data['settings']['fields'] as $code => $field )
		{			
			foreach( $properties as $k => $property )
			{				
				if( $property->code === $code )
				{					
					$property->value = $property->code;				
					$property->require = isset($this->data['settings']['fields'][$property->code])?(int)$this->data['settings']['fields'][$property->code]['require']:0;						
					$webform_properties[] = $property;
					break;
				}
			}			
		}		
		$this->js_args = ['webform_properties' => $webform_properties, 'properties' => $properties];
	}
	
	function display_left()
	{					
		$this->titlediv( $this->data['title'] );			
		usam_add_box( 'usam_webform_description', __('Описание веб-формы', 'usam'), array($this, 'print_editor') );	
		usam_add_box( 'usam_webform_settings', __('Настройка веб-формы', 'usam'), array($this, 'webform_settings') );		
		?>					
		<usam-box :id="'usam_payment_gateway'" :handle="false" :title="'<?php _e( 'Платежная система привязанная веб-форме', 'usam'); ?>'" v-if="data.action=='payment_gateway'">		
			<template v-slot:body>
				<div class="edit_form">
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Платежная система','usam'); ?>:</div>
						<div class ="edit_form__item_option">								
							<select name = "settings[payment_gateway]" v-model="data.settings.payment_gateway">
								<?php 									
								$gateways = usam_get_payment_gateways(['active' => 1]);									
								foreach ( $gateways as $gateway )
								{	
									?><option value='<?php echo $gateway->id; ?>'><?php echo $gateway->name; ?></option><?php 
								}
								?>	
							</select>
						</div>
					</label>
				</div>
			</template>
		</usam-box>	
		<usam-box :id="'usam_button_settings'" :handle="false" :title="'<?php _e( 'Настройка кнопки вызова модальной формы', 'usam'); ?>'">		
			<template v-slot:body>
				<div class="edit_form">				
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Название кнопки', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.settings.modal_button_name" name="settings[modal_button_name]"/>
						</div>
					</label>
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Цвет кнопки', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.settings.buttonCSS['background-color']" name="settings[buttonCSS][background-color]"/>
						</div>
					</label>
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Цвет текста кнопки', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" v-model="data.settings.buttonCSS.color" name="settings[buttonCSS][color]"/>
						</div>
					</label>			
				</div>		
			</template>
		</usam-box>	
		<usam-box :id="'usam_webform_conditions'" :handle="false" :title="'<?php _e( 'Условия показа веб-формы', 'usam'); ?>'">		
			<template v-slot:body>
				<div class="edit_form">			
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='usam_date_picker-start'><?php esc_html_e( 'Интервал', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option date_intervals">
							<datetime-picker v-model="data.start_date"></datetime-picker> - <datetime-picker v-model="data.end_date"></datetime-picker>
							<input type="hidden" name="start_date" v-model="data.start_date"/>
							<input type="hidden" name="end_date" v-model="data.end_date"/>
						</div>
					</div>
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e('Авто показ','usam'); ?>:</div>
						<div class ="edit_form__item_option">								
							<input type="checkbox" v-model="show" name="show_webform"/>
						</div>
					</label>
					<label class ="edit_form__item" v-if="show">
						<div class ="edit_form__item_name"><?php esc_html_e('Через сколько секунд показывать?', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" id ="webform_time" v-model="data.actuation_time" name="actuation_time"/>
						</div>
					</label>			
				</div>		
			</template>
		</usam-box>
		<usam-box :id="'usam_webform'" :handle="false" :title="'<?php _e( 'Веб-форма', 'usam'); ?>'">
			<template v-slot:body>		
				<level-table :lists='webform_properties'>				
					<template v-slot:thead="slotProps">
						<th class="column_type"><?php _e( 'Тип поля', 'usam'); ?></th>	
						<th class="column_require"><?php _e( 'Обязательное', 'usam'); ?></th>	
						<th class="column_action"></th>
					</template>			
					<template v-slot:tbody="slotProps">
						<td class="column-code" draggable="true">
							<select v-model="slotProps.row.value">
								<option value=''><?php _e("Не выбрано","usam"); ?></option>
								<option v-for="property in properties" :value='property.code' v-html="property.name+' ('+property.id+')'"></option>
							</select>				
						</td>						
						<td class="column_require">	
							<input type="hidden" :name="'settings[fields]['+slotProps.row.value+'][require]'" value="0" v-model="slotProps.row.require">	
							<input type="checkbox" :name="'settings[fields]['+slotProps.row.value+'][require]'" value="1" v-model="slotProps.row.require">
						</td>						
						<td class="column_actions">											
							<?php usam_system_svg_icon("plus", ["@click" => "slotProps.add(slotProps.k)"]); ?>
							<?php usam_system_svg_icon("minus", ["@click" => "slotProps.del(slotProps.k)"]); ?>						
						</td>
					</template>
				</level-table>
			</template>
		</usam-box>			
		<?php 				
		usam_add_box( 'usam_webform_result_message', __('Сообщение по результатам отправки веб-формы', 'usam'), [$this, 'result_message']);	
	}
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );	
    }
	
	public function webform_settings() 
	{
		$templates = usam_get_templates2('webforms');
		?>
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="webform_action"><?php _e( 'Действие после отправки','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="webform_action" name = "webform_action" v-model="data.action">
						<?php 									
						$actions = usam_get_webform_actions();									
						foreach ( $actions as $action => $name )
						{	
							?><option value='<?php echo $action; ?>'><?php echo $name; ?></option><?php 
						}
						?>	
					</select>
				</div>
			</div>
			<label class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Код формы', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="code" v-model="data.code"/>
				</div>
			</label>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='webform_button_name'><?php esc_html_e( 'Название кнопки в форме', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id ="webform_button_name" value="<?php echo stripslashes($this->data['settings']['button_name']); ?>" name="settings[button_name]"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="webform_template"><?php _e( 'Шаблон веб-формы','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">								
					<select id="webform_template" name = "template">
						<?php 						
						foreach( $templates as $key => $template )
						{	
							?><option value='<?php echo $key; ?>' <?php selected( $key, $this->data['template'] ) ?>><?php echo $template['name']; ?></option><?php 
						}
						?>	
					</select>
				</div>
			</div>			
			<?php 
			$languages = usam_get_languages();
			if ( !empty($languages) )	
			{
				?>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for="webform_language"><?php _e( 'Язык','usam'); ?>:</label></div>
					<div class ="edit_form__item_option">								
						<select id="webform_language" name = "language">
							<?php 											
							foreach ( $languages as $language )
							{	
								?>	
								<option value='<?php echo $language['code']; ?>' <?php selected( $language['code'], $this->data['language'] ) ?>><?php echo $language['name']; ?></option>	
								<?php 
							}
							?>	
						</select>
					</div>
				</div>
			<?php } ?>
		</div>		
		<?php 
	}		
	
	public function print_editor( ) 
	{
		?><p><?php esc_html_e( 'Шорт-коды которые могут быть использованы', 'usam');?>: <?php echo esc_html( '%shop_name%, %product_price%, %product_name%' ); ?></p><?php
		wp_editor(stripslashes($this->data['settings']['description']),'usam_description',array(
			'textarea_rows' => 10,
			'textarea_name' => 'settings[description]',
			'media_buttons' => false,
			'tinymce' => array( 'theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
			)	
		);		
	}
	
	public function result_message( ) 
	{		
		wp_editor(stripslashes($this->data['settings']['result_message']),'usam_result_message',array(
			'textarea_rows' => 10,
			'textarea_name' => 'settings[result_message]',
			'media_buttons' => false,
			'tinymce' => array( 'theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
			)	
		);		
	}	
}
?>