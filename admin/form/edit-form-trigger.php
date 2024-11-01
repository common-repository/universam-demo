<?php		
require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_trigger extends USAM_Edit_Form
{
	protected $vue = true;	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить триггер &#171;%s&#187;','usam'), '<span v-html="data.title"></span>' );
		else
			$title = __('Выберете триггер', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{					
		
		$default = ['id' => 0,  'title' => '', 'group_id' => '', 'description' => '', 'active' => 0, 'event' => '', 'sort' => 99, 'conditions' => '', 'actions' => []];
		if ( $this->id !== null )
		{
			$this->data = usam_get_trigger( $this->id );
			$this->data['actions'] = usam_get_array_metadata( $this->id, 'trigger', 'actions' );
		}
		$this->data = array_merge( $default, $this->data );	
		$this->js_args = ['triggers' => usam_get_list_triggers(), 'actions_triggers' => usam_get_list_actions_triggers()];
	/*
	$string = '<p class="checkcheck"><img border="0" id="_x005f_x0000_i1025_mr_css_attr" src="https://wp-universam.ru?mail_id=18201&amp;usam_action=email_open" alt="check"><span style="font-size:1.0pt">18201</span><o:p></o:p></p>';
	
	$string = '<span class="sendemailobject" style="display:none;font-size:1px;">order-567676</span><span class="sendemailid" style="display:none;font-size:1px;">567676</span><span 900 class="checkcheck"><img border="0" id="_x005f_x0000_i1025_mr_css_attr" src="https://wp-universam.ru?mail_id=18201&amp;usam_action=email_open" alt="check"></span> [p[[p <span 900 class="checkcheck"><img border="0" id="_x005f_x0000_i1025_mr_css_attr" src="https://wp-universam.ru?mail_id=18201&amp;usam_action=email_open" alt="check"></span>';
//	<[^>]+>
		$pattern = '/<[(p|span)](.*?[(class="checkcheck")].*?(usam_action=email_open).*?)<\/(p|span)>/si';
	//	$pattern = '/<(.*?)<\/(.*?)>/si';
		
		preg_match_all('/<span[^>]*?>(.*?)<\/span>/si', $string, $matches);
		
		
		$pattern = '/<(p|span) [(class="checkcheck")](.*?(usam_action=email_open).*?)<\/(p|span)>/si';
		
		$pattern = '~<span class="checkcheck">(.+?)<\/span>~s';
		$pattern = '/<(p|span).+?class="checkcheck"(.*?)<\/(p|span)>/si';
		
	//	print_r($matches);
		
		if( preg_match_all('/<(p|span).+?class="checkcheck"(.*?)<\/(p|span)>/si', $string, $matches ) )
		{
			print_r($matches);
		}
		
		if( preg_match('/<(p|span).+?class="sendemailid".*?>([0-9]+)<\/(p|span)>/si', $string, $matches) )
		{								
			print_r($matches);
		}		
		echo "<br><br>-------------------------------<br><br>";
		if( preg_match('/<(p|span).+?class="sendemailobject".*?>([a-z]+-[0-9]+)<\/(p|span)>/si', $string, $matches) )
		{				
			print_r($matches);
		}
		
		echo "<br><br><br><br>";
		
			/*
		require_once( USAM_FILE_PATH . '/includes/expression-parser.class.php' );
		$t = new USAM_Expression_Parser();
		//$t->parser('order.status.groupCode == "approval" AND order.id > 5 OR order.status.name < 88');
	//	$t->parser('order.status.groupCode == "approval" and order.id > 5');
		
	//	$result = $t->parser('order.title == "Оказание услуг по технической поддержке веб-сайта по договору №7028" && order.sort > 5', ['order' => $this->data]);
	$this->data['company_ids'] = [43,5676,477];
	$this->data['compads'] = [43,5676];
	//	$result = $t->parser('count(order.company_ids) > 1 && order.sort > 5', ['order' => $this->data]);
		$result = $t->parser('count(order.company_ids) > 16', ['order' => $this->data]);
		if ( $result )
		{
			print_r ('Ура');
		}
		*/
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}	
		
	public function display_rules( ) 
	{
		?>	
		<div class='edit_form'>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Событие', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<span class="button" @click="sidebarOpen('event')"><span v-if="!data.event"><?php _e( 'Выбрать событие', 'usam'); ?></span><span v-else="" v-html="triggers[data.event].title"></span></span>
					<modal-panel ref="modalevent">
						<template v-slot:title><?php _e('Событие', 'usam'); ?></template>
						<template v-slot:body>
							<div class ="lists_select">
								<a class="list" v-for="(item, k) in triggers" v-html="item.title" @click="selectEvent(k)"></a>
							</div>
						</template>
					</modal-panel>
				</div>
			</div>
			<div class ="edit_form__item" v-if="data.event">
				<div class ="edit_form__item_name"><?php _e( 'Условие', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<textarea v-model="data.conditions"></textarea>
				</div>
			</div>
			<div class ="edit_form__item" v-if="data.event">
				<div class ="edit_form__item_name"><?php _e( 'Действие', 'usam'); ?>:</div>
				<div class ="edit_form__item_option triger_actions">					
					<div class ="list" v-for="(item, k) in data.actions">
						<span class="button" @click="editAction(k)"><span v-html="actions_triggers[item.id].title"></span><span class="dashicons dashicons-no-alt" @click="data.actions.splice(k, 1);"></span></span>
					</div>
					<span v-if="data.actions.length" class="button" @click="addAction">+</span>
					<span v-else class="button" @click="sidebarOpen('action')"><?php _e( 'Выбрать действие', 'usam'); ?></span>
				</div>
				<modal-panel ref="modalaction">
					<template v-slot:title><?php _e('Действия', 'usam'); ?></template>
					<template v-slot:body>
						<div class ="lists_select">				
							<div class ="setting" v-if="action!==null">
								<div class="setting__title" v-html="actions_triggers[data.actions[action].id].title"></div>
								<div class="edit_form" v-if="data.actions[action].id=='change_price'">
									<div class ="edit_form__item">
										<div class ="edit_form__item_name"><?php esc_html_e( 'Событие', 'usam'); ?>:</div>
										<div class ="edit_form__item_option">
											<select v-model="data.actions[action].settings.type_price">
												<option v-for="(type_price, i) in type_prices" :value='type_price.code' v-html="type_price.title"></option>
											</select>
										</div>
									</div>
									<div class ="edit_form__item">
										<div class ="edit_form__item_name"><?php esc_html_e( 'Процент изменения', 'usam'); ?>:</div>
										<div class ="edit_form__item_option">
											<input type="text" v-model="data.actions[action].settings.percent" autocomplete="off">
										</div>
									</div>
								</div>
								<div class="edit_form" v-else-if="data.actions[action].id=='send_letter'">
									<div class ="edit_form__item">
										<div class ="edit_form__item_name"><?php esc_html_e( 'Письмо', 'usam'); ?>:</div>
										<div class ="edit_form__item_option">
											<select v-model="data.actions[action].settings.newsletter_id">
												<option v-for="(v, i) in newsletters" v-if="v.type == 'mail'" :value='v.id' v-html="v.subject"></option>
											</select>
										</div>
									</div>								
								</div>
								<div class="edit_form" v-else-if="data.actions[action].id=='send_sms'">
									<div class ="edit_form__item">
										<div class ="edit_form__item_name"><?php esc_html_e( 'Шаблоны SMS', 'usam'); ?>:</div>
										<div class ="edit_form__item_option">
											<select v-model="data.actions[action].settings.newsletter_id">
												<option v-for="(v, i) in newsletters" v-if="v.type == 'sms'" :value='v.id' v-html="v.subject"></option>
											</select>
										</div>
									</div>								
								</div>
								<span class="button" @click="saveAction"><?php _e( 'Выбрать', 'usam'); ?></span>
							</div>
							<a class="list" v-for="(item, k) in actions_triggers" v-show="action===null && item.events.includes(data.event)" v-html="item.title" @click="selectAction(k)"></a>
						</div>
					</template>
				</modal-panel>
			</div>			
		</div>
		<?php
	}
		
	function display_right()
	{			
		$this->vue_box_status();		
    }
	
	public function display_left( ) 
	{		
		?>	
		<div class='event_form_head'>			
			<div class='event_form_head__title'>
				<div id="titlediv">			
					<input type="text" name="name" v-model="data.title" placeholder="<?php _e('Введите название', 'usam'); ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
				</div>
			</div>
			<div class ="form_description">			
				<textarea placeholder="<?php _e( 'Примечание...', 'usam'); ?>" v-model="data.description"></textarea>
			</div>
			<div class='edit_form'>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Сортировка', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type="text" v-model="data.sort" name="sort" autocomplete="off">
					</div>
				</div>												
			</div>
		</div>		
		<?php		
		usam_add_box( 'usam_condition', __('Условия срабатывания','usam'), [$this, 'display_rules'] );			
	}
}
?>