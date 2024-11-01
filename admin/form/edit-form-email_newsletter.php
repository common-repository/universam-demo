<?php		
require_once( USAM_FILE_PATH . '/admin/includes/mail/usam_edit_mail.class.php' );
require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
class USAM_Form_email_newsletter extends USAM_Edit_Form
{	
	protected $vue = true;	
	protected $JSON = true;	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function form_class( ) { 
		return 'fullscreen_form constructor';
	}
		
	protected function get_data_tab(  )
	{		
		$default = ['id' => 0, 'lists' => [], 'type' => 'mail', 'status' => 0, 'class' => 'simple', 'subject' => '', 'repeat_days' => '', 'period_type' => '', 'start_date' => '', 'event_start' => '', 'pricelist' => [], 'settings' => ['fon' => '#000000', 'css' => ['border-width' => '0', 'border-color' => '', 'border-style' => 'none', 'border-radius' => '5px', 'background-color' => '#ffffff', 'color' => '#000000', 'width' => '640px', 'margin' => '20px', 'padding' => '0px'], 'margin' => '0', 'blocks' => []], 'conditions' => ['run_for_old_data' => 0, 'days_dont_buy' => 90, 'days' => 1, 'invoice' => 0, 'sum' => '500', 'time_start' => '13:00', 'days_basket_forgotten' => 5, 'status' => [], 'webform' => [], 'lists' => [], 'mailboxes' => []]];			
		$user_id = get_current_user_id();
		$mailboxes = usam_get_mailboxes(['fields' => ['id','name','email'], 'user_id' => $user_id, 'meta_query' => [['key' => 'newsletter', 'value' => 1, 'compare' => '=']]]);
		$default['mailbox_id'] = !empty($mailboxes)?$mailboxes[0]->id : 0;
		if( $this->id )
		{
			$this->data = usam_get_newsletter( $this->id );
			if ( empty($this->data) )
				return false;
			$metas = usam_get_newsletter_metadata( $this->id );
			foreach($metas as $metadata )
				if ( !isset($this->data[$metadata->meta_key]) )
					$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);	
								
			$repeat_days = explode(' ', $this->data['repeat_days']); 
			$this->data['repeat_days'] = $repeat_days[0]?$repeat_days[0]:''; 
			$this->data['period_type'] = isset($repeat_days[1])?$repeat_days[1]:''; 
			$this->data['pricelist'] = array_map('intval', usam_get_array_metadata($this->id, 'newsletter', 'pricelist'));
			$this->data['lists'] = usam_get_newsletter_list( $this->id );				
		}
		else
			$this->data['settings']['blocks'] = [['text' => "<h2><strong>Шаг 1:</strong> нажмите на этот текст!</h2><br/><p>Для редактирования, просто нажмите на эту часть текста.</p>", 'type' => 'content', 'css' => ['background-color' => '', 'border-radius' => '', 'border-color' => '', 'border-style' => '', 'border-width' => '0', 'margin' => '', 'padding' => '20px', 'height' => '', 'width' => '', 'text-align' => 'left'], 'contentCSS' => ['text-align' => 'center', 'color' => '#000000', 'font-family' => 'Verdana', 'font-size' => '18px', 'font-weight' => '400', 'line-height' => '1.3', 'text-decoration' => 'none'] ]];		

		$this->data = usam_format_data( $default, $this->data );			
		$this->js_args = [
			'tabSettings' => [
				'settings' => ['icons' => ['style' => __('Стиль', 'usam'), 'email' => __('Тип рассылки', 'usam'), 'attachments' => __('Вложения', 'usam')]],
				'display' => ['icons' => ['place' => __('Расположение', 'usam'), 'question' => __('Условия', 'usam')]],
				'editor' => ['icons' => ['style' => __('Стиль', 'usam'), 'content' => __('Редактор', 'usam')]],				
			],
			'mailboxes' => $mailboxes,
			'templates' => usam_get_templates( 'newsletter-templates', 'style.css' ),
			'text_media_upload' => __('Добавление картинки в рассылку','usam'),	
		];		
	}
	
	public function print_scripts_style()
	{  
		wp_enqueue_style( 'usam-admin-silder' );
		wp_enqueue_script( 'v-color' ); 
		require_once( USAM_FILE_PATH.'/admin/templates/vue-templates/block-newsletter.php' );		
		wp_enqueue_script('usam-mail-editor');			
		
		wp_enqueue_media();	
		add_thickbox();	
		require_once ABSPATH . '/wp-includes/class-wp-editor.php';		
		_WP_Editors::print_tinymce_scripts();	
	}
		
	protected function display_toolbar()
    {
		?>	
		<div class="tab_title form_toolbar">			
			<span class="form_title_go_back">
				<a href="<?php echo $this->get_url_go_back(); ?>" class="go_back"><span class="dashicons dashicons-arrow-left-alt2"></span></a>
				<span class="form_title edit_name_form">
					<span class='edit_name_form__dispaly' @click='openEditName' v-show='!editName'>{{data.subject}}</span>
					<input ref="formname" type='text' v-model='data.subject' v-show="editName">				
				</span>
			</span>					
			<div class="form_toolbar__send_preview" v-if="current_step==3">
				<input type="text" v-model="email" value="<?php echo usam_get_shop_mail(false); ?>" placeholder="<?php _e('Введите почту', 'usam') ?>"/>
				<button class="button" @click="send_preview"><?php esc_html_e( 'Отправить для просмотра', 'usam'); ?></button>
			</div>
			<?php $this->display_navigation(); ?>
			<div class="action_buttons" v-if="current_step==3">
				<?php $this->toolbar_buttons();	?>			
			</div>
		</div>
		<?php		
	}
	
	protected function get_toolbar_buttons( ) 
	{
		if ( $this->change )
		{
			$links = [		
				['vue' => ["@click='saveForm'"], 'primary' => true, 'name' => '<span v-if="data.id>0">'.__('Сохранить','usam').'</span><span v-else>'.__('Добавить','usam').'</span>'],
				['vue' => ["@click='send'", "v-if='data.status != 5 && data.id>0'"], 'primary' => false, 'name' => '<span v-if="data.class==`simple`">'.__('Отправить','usam').'</span><span v-if="data.class==`trigger` || data.class==`template`">'.__('Готово','usam').'</span>'],
				['vue' => ["@click='changeStatus(0)'", "v-if='data.status == 5'"], 'primary' => false, 'name' => __('В черновик','usam')],
			];
		}
		return $links;
	}
	
	protected function toolbar_buttons( ) 
	{						
		$this->display_toolbar_buttons();
		$this->main_actions_button();
	}
	
	public function display_form( ) 
	{
		?>	
		<div class = "fullscreen_form__name screen_start" v-if="current_step==1">			
			<h2 class="tab_title fullscreen_form__name_text"><?php esc_html_e( 'Введите название рассылки', 'usam'); ?></h2>
			<div class="edit_form">				
				<div class ="edit_form__item" :class="[validation===false&&data.subject==''?'validation-error':'']">
					<input type="text" v-model="data.subject" class="titlebox" placeholder="<?php esc_html_e( 'Название рассылки', 'usam'); ?>">
				</div>
			</div>					
			<button class="button button-primary" @click="next_step"><?php esc_html_e( 'Следующий шаг', 'usam'); ?></button>
		</div>			
		<div class = "fullscreen_form__name screen_template" v-if="current_step==2">			
			<h2 class="tab_title fullscreen_form__name_text"><?php esc_html_e( 'Готовые шаблоны', 'usam'); ?> <button class="button" @click="current_step=3"><?php esc_html_e( 'Продолжить без шаблона', 'usam'); ?></button></h2>		
			<div class="templates">				
				<div class="template" v-for="(template, i) in templates">
					<div class="template__screenshot"><img :src="template.screenshot" alt=""></div>
					<h3 class="theme-name" v-html="template.name"></h3>
					<div class="template__select"><button class="button button-primary" @click="selectTemplateAndNext(i)"><?php esc_html_e( 'Выбрать', 'usam'); ?></button></div>			
				</div>		
			</div>
		</div>
		<?php
		$content_blocks = usam_get_newsletter_metadata( $this->id, 'content_blocks' );
		if ( !empty($content_blocks) )
		{
			?><div class="usam_message message_error"><?php _e('Это письмо сделано в старом редакторе и его не возможно отредактировать. Вы можете начать создавать новое, как только вы сохраните, старое удалится', 'usam') ?></div><?php
		}
		?>		
		<div class="columns-2" v-show="current_step==3" :class="{'active_drag':dragBlock !== false}">
			<div class="type_editor">
				<div id="slider_editor" class="page_main_content email_editor" @mousemove="mousemove" @mouseup="handleUp">
					<div id="ruler_hor_marker" style="display: block; height: 15px;"></div>
					<div id="ruler_ver_marker" style="display: block; width: 15px;"></div>
					<div class="ruler_top"><canvas id="ruler_top_offset" width="3600" height="15" :style="'transform:translate('+rulerTop+'px, 0px)'"></canvas></div>
					<div class="ruler_left"><canvas id="ruler_left_offset" width="15" height="3600" style="top: -1200px; transform: translate(0px, 0px);"></canvas></div>			
						<div class="slides" ref="slides" :style="blockCSS({'background':data.settings.fon})">
						<div class="email_editor_content" ref="layers" :style="blockCSS(data.settings.css)">	
							<div class="block_placeholder" @drop="dropWidget(0)" @dragover="allowDrop"><?php _e('Вставьте блок здесь', 'usam') ?></div>
							<div class="container_block" v-for="(item, k) in data.settings.blocks" :class="{'active':block !== null && block.id===item.id}">
								<block-newsletter :block="item" :index="k" @delete="delBlock(k)" @move-up="moveUp(k)" @move-down="moveDown(k)"></block-newsletter>
								<div class="block_placeholder" @drop="dropWidget(k+1)" @dragover="allowDrop"><?php _e('Вставьте блок здесь', 'usam') ?></div>	
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="form_settings_sidebar">
				<div class="form_settings_sidebar__tabs">
					<a @click="tab='blocks'" :class="{'active':tab=='blocks'}"><?php _e('Блоки', 'usam'); ?></a>
					<a @click="tab='settings'" :class="{'active':tab=='settings'}"><?php _e('Настройка', 'usam'); ?></a>
					<a @click="tab='editor'" :class="{'active':tab=='editor'}"><?php _e('Редактор', 'usam'); ?></a>
				</div>
				<div class="form_settings" v-if="tab=='blocks'">
					<div class="form_settings__name"><?php _e('Блоки', 'usam'); ?></div>
					<div class="form_settings__blocks">					
						<?php
						$block_editor = ['content' => __('Текст', 'usam'), 'image' => __('Картинка', 'usam'), 'button' => __('Кнопка', 'usam'), 'divider' => __('Разделитель', 'usam'), 'indentation' => __('Отступ', 'usam'), 'columns' => __('Колонки', 'usam'), 'product' => __('Товар', 'usam'), 'basket' => __('Корзина', 'usam')];
						foreach ( $block_editor as $type => $title ) 
						{
							?>		
							<div class="add_block">
								<div class="add_block_icon" draggable='true' @dragstart="dragWidget($event, '<?php echo $type; ?>')" @dragend="dragendWidgetEnd($event, '<?php echo $type; ?>')"><?php echo usam_get_system_svg_icon( $type ); ?></div>
								<div class="add_block_title"><?php echo $title; ?></div>
							</div><?php
						}
						?>
					</div>					
				</div>			
				<div class="form_settings form_settings_scroll" v-if="tab=='settings'">
					<div class="form_settings" v-if="tab=='settings'">
						<div class="form_settings__name"><?php _e('Настройки', 'usam'); ?></div>
						<?php $this->display_icon(); ?>							
						<div class="form_settings__sections_options" v-if="section[tab]=='style'">
							<div class="form_settings__sections_options_name"><?php _e('Стиль макета', 'usam'); ?></div>
							<div class="options">
								<div class="options_row">
									<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
									<div class="options_item">
										<color-picker :type="'hex'" @input="data.settings.fon=$event" :value="data.settings.fon"/>
									</div>		
								</div>	
								<div class="options_row">
									<div class="options_name"><?php _e('Ширина', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="data.settings.css.width" class="option_input">
									</div>
								</div>	
								<div class="options_row">
									<div class="options_name"><?php _e('Внешние отступы', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="data.settings.css.margin" class="option_input">
									</div>
								</div>								
								<div class="options_row">
									<div class="options_name"><?php _e('Внутренние отступы', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="data.settings.css.padding" class="option_input">
									</div>
								</div>
								<div class="options_row">
									<div class="options_name"><?php _e('Радиус', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="data.settings.css['border-radius']" class="option_input">					
									</div>		
								</div>
								<div class="options_row">
									<div class="options_name"><?php _e('Цвет текста', 'usam'); ?></div>
									<div class="options_item">
										<color-picker :type="'hex'" @input="data.settings.css.color=$event" :value="data.settings.css.color"/>
									</div>		
								</div>
								<div class="options_row">
									<div class="options_name"><?php _e('Цвет макета', 'usam'); ?></div>
									<div class="options_item">
										<color-picker :type="'hex'" @input="data.settings.css['background-color']=$event" :value="data.settings.css['background-color']"/>
									</div>		
								</div>																
							</div>							
							<div class="form_settings__sections_options_name"><?php _e('Рамка', 'usam'); ?><selector v-model="data.settings.css['border-width']" :items="[{id:'0', name:'<?php _e('Нет', 'usam'); ?>'},{id:'1px', name:'<?php _e('Да', 'usam'); ?>'}]"></selector></div>
							<div class="options" v-if="data.settings.css['border-width'] !== '0'">	
								<div class="options_row">
									<div class="options_name"><?php _e('Цвет рамки', 'usam'); ?></div>
									<div class="options_item">
										<color-picker :type="'hex'" @input="data.settings.css['border-color']=$event" :value="data.settings.css['border-color']"/>
									</div>		
								</div>
								<div class="options_row">
									<div class="options_name"><?php _e('Стиль рамки', 'usam'); ?></div>
									<div class="options_item">
										<select-list @change="data.settings.css['border-style']=$event.id" :lists="[{id:'none', name:'None'},{id:'solid', name:'Solid'},{id:'dashed', name:'Dashed'},{id:'dotted', name:'Dotted'},{id:'double', name:'Double'}]" :selected="data.settings.css['border-style']"></select-list>
									</div>		
								</div>	
								<div class="options_row">
									<div class="options_name"><?php _e('Толщина', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="data.settings.css['border-width']" class="option_input">					
									</div>		
								</div>																	
							</div>					
						</div>	
						<div class="form_settings__sections_options" v-if="section[tab]=='attachments'">
							<div class="form_settings__sections_options_name"><?php _e('Прикрепить вложения', 'usam'); ?></div>
							<div class="options">
								<div class="options_row"><?php $this->display_attachments(); ?></div>	
								<div class="options_row">
									<div class="options_name"><?php _e('Прайс-лист', 'usam'); ?></div>
									<div class="options_item">
										<?php 
											require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
											$rules = usam_get_exchange_rules(['type' => 'pricelist']);	
											if ( $rules ) 
											{
												?>
												<select v-model="data.pricelist" multiple="multiple">
													<?php										
													foreach( $rules as $rule )	
													{
														?><option value="<?php echo $rule->id; ?>"><?php echo $rule->name; ?></option><?php
													}		
													?>	
												</select>
												<?php
											}
											else
											{							
												?><div><?php esc_html_e( 'Нет созданных прайс листов', 'usam'); ?>:</div><?php
											}
										?>
									</div>
								</div>
							</div>
						</div>
						<div class="form_settings__sections_options" v-if="section[tab]=='email'">
							<div class="form_settings__sections_options_name"><?php _e('Отправитель', 'usam'); ?></div>
							<div class="options">
								<div class="options_row">									
									<select v-model="data.mailbox_id">
										<option v-for="mailbox in mailboxes" :value="mailbox.id">{{mailbox.name}} ({{mailbox.email}})</option>
									</select>
								</div>						
								<div class="options_row"><?php _e('Название рассылки', 'usam'); ?></div>
								<div class="options_row"><input type="text" v-model="data.subject" class="option_input"></div>
								<div class="options_row">
									<div class="options_name"><?php _e('Тип рассылки', 'usam'); ?></div>								
									<div class="options_item options_item_radios">
										<label><input type="radio" v-model="data.class" value="simple"><?php _e('Стандартная', 'usam'); ?></label>
										<label><input type="radio" v-model="data.class" value="trigger"><?php _e('Триггерная', 'usam'); ?></label>
										<label><input type="radio" v-model="data.class" value="template"><?php _e('Шаблон для рассылки', 'usam'); ?></label>
									</div>
								</div>
							</div>
							<div class="form_settings__sections_options_name" v-if="data.class=='simple'"><?php _e('Списки подписчиков', 'usam'); ?></div>
							<div class="options" v-if="data.class=='simple'">								
								<div class="options_row">
									<select-list @change="data.lists=$event.id" :lists="mailingLists" :selected="data.lists" :multiple="1"></select-list>
								</div>														
							</div>	
							<div class="form_settings__sections_options_name" v-if="data.class!='template'"><?php _e('Условия запуска', 'usam'); ?></div>
							<div class="options" v-if="data.class=='simple'">
								<div class ="options_row">
									<div class ="options_name"><?php esc_html_e( 'Дата начала отправки', 'usam'); ?>:</div>
									<div class ="options_item">
										<v-date-picker v-model="data.start_date" mode="dateTime" is24hr :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
											<template v-slot="{ inputValue, inputEvents }"><input type="text" :value="inputValue" v-on="inputEvents"/></template>				  
										</v-date-picker>
									</div>	
								</div>		
								<div class ="options_row">
									<div class ="options_name"><?php esc_html_e( 'Повторять каждые', 'usam'); ?>:</div>
									<div class ="options_item options_item_line">				
										<input type="number" v-model="data.repeat_days" size="4" maxlength = "3" size = "3" style='width:50px'/>
										<select v-model="data.period_type" style='width:100px'>
											<option value="day"><?php _e( 'дней', 'usam'); ?></option>
											<option value="week"><?php _e( 'недель', 'usam'); ?></option>							
											<option value="month"><?php _e( 'месяцев', 'usam'); ?></option>	
											<option value="year"><?php _e( 'лет', 'usam'); ?></option>								
										</select>
									</div>	
								</div>							
							</div>
							<?php 		
								$statuses = usam_get_object_statuses( );		
								$mailboxes = usam_get_mailboxes( );				
								$webforms = usam_get_webforms(); 				
							?>							
							<div class="options" v-if="data.class=='trigger'">
								<div class="options_row">
									<div class="options_name"><?php _e('Выберите условие', 'usam'); ?></div>
									<div class="options_item">
										<select v-model="data.event_start">			
											<?php 		
												$trigger_types = usam_get_mailing_trigger_types();	
												foreach ( $trigger_types as $trigger_type )
												{
													?><optgroup label="<?php echo $trigger_type['title']; ?>"><?php 
														foreach ( $trigger_type['triggers'] as $key => $name )
														{
															?><option value="<?php echo $key; ?>"><?php echo $name; ?></option><?php 	
														}
													?></optgroup><?php 	
												}									
											?>				
										</select>
									</div>
								</div>		
								<div class ="options_row" v-if="data.event_start=='sale_dont_buy' || data.event_start=='basket_forgotten' || data.event_start=='subscription_end' || data.event_start=='order_status' || data.event_start=='available_bonuses'">
									<div class ="options_name"><?php _e('Время запуска', 'usam')  ?>:</div>
									<div class ="options_item">
										<?php $times = array( '00:00', '00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30', ); ?>
										<select v-model="data.conditions.time_start">
											<?php									
											foreach ( $times as $time )
											{ 
												?><option value="<?php echo $time; ?>"><?php echo $time; ?></option><?php									
											}
											?>
										</select>	
									</div>
								</div>		
								<div class ="options_row" v-if="data.event_start=='subscription_end'">
									<div class ="options_name"><?php _e('Предупредить за (дней)', 'usam')  ?>:</div>
									<div class ="options_item">
										<input type="text" name="trigger_condition[days]" v-model="data.conditions.days"/>
									</div>
								</div>
								<div class ="options_row" v-if="data.event_start=='available_bonuses'">
									<div class ="options_name"><?php _e('Повторять каждые', 'usam')  ?>:</div>
									<div class ="options_item">
										<input type="text" name="trigger_condition[days]" v-model="data.conditions.days"/>
									</div>
								</div>
								<div class ="options_row" v-if="data.event_start=='available_bonuses'">
									<div class ="options_name"><?php _e('Бонусов больше', 'usam')  ?>:</div>
									<div class ="options_item">
										<input type="text" name="trigger_condition[sum]" v-model="data.conditions.sum"/>
									</div>
								</div>									
								<div class ="options_row" v-if="data.event_start=='order_status' || data.event_start=='order_status_change'">
									<div class ="options_name"><?php _e('Статус заказа', 'usam') ?>:</div>
									<div class ="options_item">
										<select v-model="data.conditions.status" multiple>
											<?php
											foreach ( $statuses as $status ) 
											{
												if ( $status->type == 'order' )
												{
													?><option value='<?php echo $status->internalname; ?>'><?php echo $status->name; ?></option><?php
												}
											}
											?>
										</select>		
									</div>
								</div>
								<div class ="options_row" v-if="data.event_start=='order_status_change'">
									<div class ="options_name"><?php _e('Прикрепить счет', 'usam') ?>:</div>
									<div class ="options_item">
										<label>
											<input type="radio" v-model="data.conditions.invoice" value="0">
											<?php _e('Нет', 'usam') ?>
										</label>
										<label>
											<input type="radio" v-model="data.conditions.invoice" value="1">
											<?php _e('Да', 'usam') ?>
										</label>							
									</div>
								</div>
								<div class ="options_row" v-if="data.event_start=='appeal_change'">
									<div class ="options_name"><label for='appeal_change'><?php _e('Статус', 'usam') ?>:</label></div>
									<div class ="options_item">
										<select multiple v-model="data.conditions.status" multiple>
											<?php										
											foreach ( $statuses as $status ) 
											{
												if ( $status->type == 'contacting' )
												{
													?><option value='<?php echo $status->internalname; ?>'><?php echo $status->name; ?></option><?php
												}
											}
											?>
										</select>		
									</div>
								</div>	
								<div class ="options_row" v-if="data.event_start=='response_letter'">
									<div class ="options_name"><?php _e('Почта', 'usam') ?>:</div>
									<div class ="options_item">
										<select multiple v-model="data.conditions.mailboxes" multiple>
											<?php																	
											foreach ( $mailboxes as $mailbox ) 
											{
												?><option value='<?php echo $mailbox->email; ?>'><?php echo $mailbox->email; ?></option><?php
											}
											?>
										</select>		
									</div>
								</div>							
								<div class ="options_row" v-if="data.event_start=='webform'">
									<div class ="options_name"><?php _e('Веб-форма', 'usam')  ?>:</div>
									<div class ="options_item">							
										<select v-model="data.conditions.webform" multiple>
											<?php												
											foreach ( $webforms as $webform )
											{ 
												?><option value="<?php echo $webform->id; ?>"><?php echo $webform->title; ?></option><?php									
											}
											?>
										</select>		
									</div>
								</div>						
								<div class ="options_row" v-if="data.event_start=='sale_dont_buy' || data.event_start=='basket_forgotten'"> 
									<div class ="options_name"><label for='run_for_old_data2'><?php _e('Обработать старые данные', 'usam')  ?>:</label></div>
									<div class ="options_item">
										<label>
											<input type="radio" v-model="data.conditions.run_for_old_data" value="0">
											<?php _e('Нет', 'usam') ?>
										</label>
										<label>
											<input type="radio" v-model="data.conditions.run_for_old_data" value="1">
											<?php _e('Да', 'usam') ?>
										</label>
									</div>
								</div>
								<div class ="options_row" v-if="data.event_start=='basket_forgotten'">
									<div class ="options_name"><label for='days_basket_forgotten'><?php _e('Забыл более(дней)', 'usam')  ?>:</label></div>
									<div class ="options_item">							
										<input type="text" id="days_basket_forgotten" v-model="data.conditions.days_basket_forgotten"/>
									</div>
								</div>									
								<div class ="options_row" v-if="data.event_start=='sale_dont_buy'">
									<div class ="options_name"><label for='days_dont_buy'><?php _e('Сколько дней не покупал', 'usam')  ?>:</label></div>
									<div class ="options_item">
										<input type="text" id='days_dont_buy' v-model="data.conditions.days_dont_buy"/>
									</div>
								</div>
								<div class ="options_row" v-if="data.event_start=='adding_newsletter'">
									<div class ="options_name"><?php _e('Списки рассылок', 'usam')  ?>:</div>
									<div class ="options_item">
										<?php 
										$lists = usam_get_mailing_lists();
										foreach ( $lists as $list )
										{ 
											?>
											<label><input v-model="data.conditions.lists" type="checkbox" value="<?php echo $list->id; ?>"><?php echo $list->name; ?></label><br>
											<?php
										}	
										?>
									</div>
								</div>											
							</div>									
						</div>								
					</div>
				</div>
				<div class="form_settings form_settings_scroll" v-if="tab=='editor'">
					<div class="form_settings__name"><?php _e('Редактор блока', 'usam'); ?></div>		
					<div class="form_settings__info form_settings__sections" v-if="block === null">
						<?php usam_system_svg_icon("layer"); ?><?php _e('Выберете или добавьте блок', 'usam'); ?>
					</div>					
					<div class="form_settings__block" v-else>
						<?php $this->display_icon(); ?>
						<div class="form_settings__sections_options" v-if="section[tab]=='content'">
							<div class="form_settings__sections_options_name"><?php _e('Содержимое', 'usam'); ?></div>
							<div class="options">							
								<div class="options_row" v-if="block.type=='product'"><?php _e('Товар', 'usam'); ?></div>
								<div class="options_row" v-if="block.type=='product'">
									<autocomplete @change="block.product_id=$event.id; block.text=$event.name; block.image.url=$event.thumbnail; block.price_currency=$event.price_currency;" :selected="block.text" :query="{status:['publish','draft'], add_fields:['price_currency','thumbnail']}" :request="'products'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
								</div>													
								<div class="options_row" v-if="block.type=='image'">
									<div class="image_container image_preview" height="100"><img v-if="block.object_url" :src="block.object_url"></div>
								</div>	
								<div class="options_row" v-if="block.type=='divider'">
									<div class="image_container image_preview" height="100"><img v-if="block.src" :src="block.src"></div>
								</div>	
								<div class="options_row" v-if="block.type=='button'"><?php _e('Текст ссылки', 'usam'); ?></div>
								<div class="options_row" v-if="block.type=='button'">
									<input type="text" v-model="block.text" class="option_input">
								</div>	
								<div class="options_row" v-if="block.type=='button' || block.type=='image'"><?php _e('Ссылка', 'usam'); ?></div>
								<div class="options_row" v-if="block.type=='button' || block.type=='image'">
									<input type="text" v-model="block.url" class="option_input">
								</div>								
							</div>
						</div>
						<div class="form_settings__sections_options" v-if="section[tab]=='style'">							
							<div class="form_settings__sections_options_name"><?php _e('Стиль блока', 'usam'); ?></div>
							<div class="options">						
								<div class="options_row">
									<div class="options_name"><?php _e('Цвет фона', 'usam'); ?></div>
									<div class="options_item">
										<color-picker :type="'hex'" @input="block.css['background-color']=$event" :value="block.css['background-color']"/>
									</div>		
								</div>								
								<div class="options_row">
									<div class="options_name"><?php _e('Радиус', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="block.css['border-radius']" class="option_input">					
									</div>		
								</div>	
								<div class="options_row">
									<div class="options_name"><?php _e('Внешние отступы', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="block.css.margin" class="option_input">
									</div>
								</div>
								<div class="options_row">
									<div class="options_name"><?php _e('Внутренние отступы', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="block.css.padding" class="option_input">
									</div>
								</div>
								<div class="options_row">
									<div class="options_name"><?php _e('Выравнивание', 'usam'); ?></div>
									<div class="options_item">
										<select-list @change="block.css['text-align']=$event.id" :lists="[{id:'left', name:'<?php _e('Влево', 'usam'); ?>'},{id:'center', name:'<?php _e('Центр', 'usam'); ?>'},{id:'right', name:'<?php _e('Вправо', 'usam'); ?>'}]" :selected="block.css['text-align']"></select-list>
									</div>		
								</div>
							</div>
							<div class="form_settings__sections_options_name"><?php _e('Рамка', 'usam'); ?><selector v-model="block.css['border-width']" :items="[{id:'0', name:'<?php _e('Нет', 'usam'); ?>'},{id:'1px', name:'<?php _e('Да', 'usam'); ?>'}]"></selector></div>
							<div class="options">	
								<div class="options_row">
									<div class="options_name"><?php _e('Цвет рамки', 'usam'); ?></div>
									<div class="options_item">
										<color-picker :type="'hex'" @input="block.css['border-color']=$event" :value="block.css['border-color']"/>
									</div>		
								</div>
								<div class="options_row">
									<div class="options_name"><?php _e('Стиль рамки', 'usam'); ?></div>
									<div class="options_item">
										<select-list @change="block.css['border-style']=$event.id" :lists="[{id:'none', name:'None'},{id:'solid', name:'Solid'},{id:'dashed', name:'Dashed'},{id:'dotted', name:'Dotted'},{id:'double', name:'Double'}]" :selected="block.css['border-style']"></select-list>
									</div>		
								</div>	
								<div class="options_row">
									<div class="options_name"><?php _e('Толщина', 'usam'); ?></div>
									<div class="options_item">
										<input type="text" v-model="block.css['border-width']" class="option_input">					
									</div>		
								</div>															
							</div>	
							<div v-if="block.type=='image'">	
								<div class="form_settings__sections_options_name"><?php _e('Стиль блока', 'usam'); ?></div>
								<div class="options">
									<div class="options_row" v-for="(css in [		
										{name:'<?php _e('Ширина', 'usam'); ?>', type:'text', key: 'width'},			
										{name:'<?php _e('Высота', 'usam'); ?>', type:'text', key: 'height'},										
									]">
										<div class="options_name" v-html="css.name"></div>
										<div class="options_item">
											<input type="text" v-model="block.contentCSS[css.key]" class="option_input" v-if="css.type=='text'">
											<select-list v-if="css.type=='select'" @change="block.contentCSS[css.key]=$event.id" :lists="css.lists" :selected="block.contentCSS[css.key]"></select-list>
											<color-picker :type="'hex'" v-if="css.type=='color'" @input="block.contentCSS[css.key]=$event" :value="block.contentCSS[css.key]"/>
										</div>
									</div>
								</div>
							</div>			
							<div v-else-if="block.type!=='divider' && block.type!=='indentation' && block.type!=='columns'">					
								<div class="form_settings__sections_options_name" v-if="block.type=='button'"><?php _e('Стиль кнопки', 'usam'); ?></div>
								<div class="form_settings__sections_options_name" v-else-if="block.type=='product'"><?php _e('Стиль названия товара', 'usam'); ?></div>
								<div class="form_settings__sections_options_name" v-else><?php _e('Стиль текста', 'usam'); ?></div>
								<div class="options">
									<div class="options_row" v-if="block.type=='button'" v-for="(css in [
									{name:'<?php _e('Цвет фона', 'usam'); ?>', type:'color', key: 'background-color'},	
									{name:'<?php _e('Ширина', 'usam'); ?>', type:'text', key: 'width'},
									{name:'<?php _e('Высота', 'usam'); ?>', type:'text', key: 'height'},		
									]">
										<div class="options_name" v-html="css.name"></div>
										<div class="options_item">
											<input type="text" v-model="block.contentCSS[css.key]" class="option_input" v-if="css.type=='text'">
											<select-list v-if="css.type=='select'" @change="block.contentCSS[css.key]=$event.id" :lists="css.lists" :selected="block.contentCSS[css.key]"></select-list>
											<color-picker :type="'hex'" v-if="css.type=='color'" @input="block.contentCSS[css.key]=$event" :value="block.contentCSS[css.key]"/>
										</div>
									</div>
									<div class="options_row" v-for="(css in [						
									{name:'<?php _e('Цвет текста', 'usam'); ?>', type:'color', key: 'color'},									
									{name:'<?php _e('Выравнивание', 'usam'); ?>', type:'select', key: 'text-align', lists:[{id:'left', name:'<?php _e('Влево', 'usam'); ?>'},{id:'center', name:'<?php _e('Центр', 'usam'); ?>'},{id:'right', name:'<?php _e('Вправо', 'usam'); ?>'}]},
									{name:'<?php _e('Шрифт', 'usam'); ?>', type:'select', key: 'font-family', lists:[{id:'inherit', name:'<?php _e('Наследуется', 'usam'); ?>'},{id:'Arial', name:'Arial'},{id:'Comic Sans MS', name:'Comic Sans MS'},{id:'Courier New', name:'Courier New'},{id:'Georgia', name:'Georgia'},{id:'Lucida', name:'Lucida'},{id:'Tahoma', name:'Tahoma'},{id:'Times New Roman', name:'Times New Roman'},{id:'Trebuchet MS', name:'Trebuchet MS'},{id:'Verdana', name:'Verdana'}]},						
									{name:'<?php _e('Размер текста', 'usam'); ?>', type:'text', key: 'font-size'},
									{name:'<?php _e('Толщина текста', 'usam'); ?>', type:'select', key: 'font-weight', lists:[{id:'400', name:'400'},{id:'500', name:'500'},{id:'600', name:'600'},{id:'700', name:'700'}]},									
									{name:'<?php _e('Начертание', 'usam'); ?>', type:'select', key: 'font-style', lists:[{id:'normal', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'italic', name:'<?php _e('Курсивное', 'usam'); ?>'},{id:'oblique', name:'<?php _e('Наклонное', 'usam'); ?>'}]},
									{name:'<?php _e('Оформление', 'usam'); ?>', type:'select', key: 'text-decoration', lists:[{id:'none', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'line-through', name:'<?php _e('Перечеркнутый', 'usam'); ?>'},{id:'blink', name:'<?php _e('Мигающий текст', 'usam'); ?>'},{id:'overline', name:'<?php _e('Линия над текстом', 'usam'); ?>'},{id:'underline', name:'<?php _e('Подчеркнутый', 'usam'); ?>'}]},
									{name:'<?php _e('Междустрочный интервал', 'usam'); ?>', type:'text', key: 'line-height'},									
									{name:'<?php _e('Стиль текста', 'usam'); ?>', type:'select', key: 'text-transform', lists:[{id:'none', name:'None'},{id:'uppercase', name:'<?php _e('Верхний регистр', 'usam'); ?>'},{id:'lowercase', name:'<?php _e('Нижний регистр', 'usam'); ?>'},{id:'capitalize', name:'<?php _e('Первый символ заглавным', 'usam'); ?>'}]},									
									{name:'<?php _e('Отступы', 'usam'); ?>', type:'text', key: 'padding'},										
									]">
										<div class="options_name" v-html="css.name"></div>
										<div class="options_item">
											<input type="text" v-model="block.contentCSS[css.key]" class="option_input" v-if="css.type=='text'">
											<select-list v-if="css.type=='select'" @change="block.contentCSS[css.key]=$event.id" :lists="css.lists" :selected="block.contentCSS[css.key]"></select-list>
											<color-picker :type="'hex'" v-if="css.type=='color'" @input="block.contentCSS[css.key]=$event" :value="block.contentCSS[css.key]"/>
										</div>
									</div>
								</div>
								<div v-if="block.type=='product'">					
									<div class="form_settings__sections_options_name"><?php _e('Стиль цены', 'usam'); ?></div>
									<div class="options">																	
										<div class="options_row">
											<div class="options_name"><?php _e('Шрифт', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.priceCSS['font-family']=$event.id" :lists="[{id:'inherit', name:'<?php _e('Наследуется', 'usam'); ?>'}]" :selected="block.priceCSS['font-family']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Размер текста', 'usam'); ?></div>
											<div class="options_item">
												<input type="text" v-model="block.priceCSS['font-size']" class="option_input">
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Толщина текста', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.priceCSS['font-weight']=$event.id" :lists="[{id:'400', name:'400'},{id:'500', name:'500'},{id:'600', name:'600'},{id:'700', name:'700'}]" :selected="block.priceCSS['font-weight']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Начертание', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.priceCSS['font-style']=$event.id" :lists="[{id:'normal', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'italic', name:'<?php _e('Курсивное', 'usam'); ?>'},{id:'oblique', name:'<?php _e('Наклонное', 'usam'); ?>'}]" :selected="block.priceCSS['font-style']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Оформление', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.priceCSS['text-decoration']=$event.id" :lists="[{id:'none', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'line-through', name:'<?php _e('Перечеркнутый', 'usam'); ?>'},{id:'blink', name:'<?php _e('Мигающий текст', 'usam'); ?>'},{id:'overline', name:'<?php _e('Линия над текстом', 'usam'); ?>'},{id:'underline', name:'<?php _e('Подчеркнутый', 'usam'); ?>'}]" :selected="block.priceCSS['text-decoration']"></select-list>
											</div>		
										</div>										
										<div class="options_row">
											<div class="options_name"><?php _e('Стиль текста', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.priceCSS['text-transform']=$event.id" :lists="[{id:'none', name:'None'},{id:'uppercase', name:'<?php _e('Верхний регистр', 'usam'); ?>'},{id:'lowercase', name:'<?php _e('Нижний регистр', 'usam'); ?>'},{id:'capitalize', name:'<?php _e('Первый символ заглавным', 'usam'); ?>'}]" :selected="block.priceCSS['text-transform']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Выравнивание', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.priceCSS['text-align']=$event.id" :lists="[{id:'left', name:'<?php _e('Влево', 'usam'); ?>'},{id:'center', name:'<?php _e('Центр', 'usam'); ?>'},{id:'right', name:'<?php _e('Вправо', 'usam'); ?>'}]" :selected="block.priceCSS['text-align']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Цвет текста', 'usam'); ?></div>
											<div class="options_item">
												<color-picker :type="'hex'" @input="block.priceCSS.color=$event" :value="block.priceCSS.color"/>
											</div>		
										</div>									
									</div>	
									<div class="form_settings__sections_options_name"><?php _e('Стиль старой цены', 'usam'); ?></div>
									<div class="options">																	
										<div class="options_row">
											<div class="options_name"><?php _e('Шрифт', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.oldpriceCSS['font-family']=$event.id" :lists="[{id:'inherit', name:'<?php _e('Наследуется', 'usam'); ?>'}]" :selected="block.oldpriceCSS['font-family']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Размер текста', 'usam'); ?></div>
											<div class="options_item">
												<input type="text" v-model="block.oldpriceCSS['font-size']" class="option_input">
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Толщина текста', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.oldpriceCSS['font-weight']=$event.id" :lists="[{id:'400', name:'400'},{id:'500', name:'500'},{id:'600', name:'600'},{id:'700', name:'700'}]" :selected="block.oldpriceCSS['font-weight']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Начертание', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.oldpriceCSS['font-style']=$event.id" :lists="[{id:'normal', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'italic', name:'<?php _e('Курсивное', 'usam'); ?>'},{id:'oblique', name:'<?php _e('Наклонное', 'usam'); ?>'}]" :selected="block.oldpriceCSS['font-style']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Оформление', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.oldpriceCSS['text-decoration']=$event.id" :lists="[{id:'none', name:'<?php _e('Обычное', 'usam'); ?>'},{id:'line-through', name:'<?php _e('Перечеркнутый', 'usam'); ?>'},{id:'blink', name:'<?php _e('Мигающий текст', 'usam'); ?>'},{id:'overline', name:'<?php _e('Линия над текстом', 'usam'); ?>'},{id:'underline', name:'<?php _e('Подчеркнутый', 'usam'); ?>'}]" :selected="block.oldpriceCSS['text-decoration']"></select-list>
											</div>		
										</div>										
										<div class="options_row">
											<div class="options_name"><?php _e('Стиль текста', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.oldpriceCSS['text-transform']=$event.id" :lists="[{id:'none', name:'None'},{id:'uppercase', name:'<?php _e('Верхний регистр', 'usam'); ?>'},{id:'lowercase', name:'<?php _e('Нижний регистр', 'usam'); ?>'},{id:'capitalize', name:'<?php _e('Первый символ заглавным', 'usam'); ?>'}]" :selected="block.oldpriceCSS['text-transform']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Выравнивание', 'usam'); ?></div>
											<div class="options_item">
												<select-list @change="block.oldpriceCSS['text-align']=$event.id" :lists="[{id:'left', name:'<?php _e('Влево', 'usam'); ?>'},{id:'center', name:'<?php _e('Центр', 'usam'); ?>'},{id:'right', name:'<?php _e('Вправо', 'usam'); ?>'}]" :selected="block.priceCSS['text-align']"></select-list>
											</div>		
										</div>
										<div class="options_row">
											<div class="options_name"><?php _e('Цвет текста', 'usam'); ?></div>
											<div class="options_item">
												<color-picker :type="'hex'" @input="block.oldpriceCSS.color=$event" :value="block.oldpriceCSS.color"/>
											</div>		
										</div>									
									</div>									
								</div>

								
							</div>											
						</div>			
					</div>				
				</div>				
			</div>				
		</div>				
		<?php		
		require_once( USAM_FILE_PATH.'/admin/includes/modal/modal-divider.php' );
	} 		
}
?>