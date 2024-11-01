<?php
// Класс форм объектов
abstract class USAM_Form
{	
	protected $id = null;	
	protected $data = [];
	protected $not_exist = false;
	protected $change = true;
	protected $js_args = [];
	protected $tabs = [];
	
	public function __construct( $args = [] ) 
	{ 		
		add_action( 'admin_footer', [$this, 'js_footer'], 1);
	}
	
	public function js_footer() 
	{
		global $js;
		wp_enqueue_script( 'postbox' );
		$this->print_scripts_style();
		$this->js_args['form_tabs'] = $this->tabs;
		if ( empty($js) )
		{
			$js = true;
			printf( "<script>form_data = %s;</script>\n", wp_json_encode( $this->data ) );	
			printf( "<script>form_args = %s;</script>\n", wp_json_encode( $this->js_args ) );
		}		
	}
	
	protected function register_modules_products() 
	{ 
		static $register = false;
		if( !$register )
		{ 
			$register = true;
			usam_vue_module('paginated-list');
			usam_vue_module('table-products');
			add_action( 'admin_footer', function(){
				require_once( USAM_FILE_PATH.'/admin/templates/template-parts/product-viewer.php' );
			});
		}
	}
	
	protected function print_scripts_style() { }
	protected function get_title_tab( ) { }	
	protected function get_data_tab() { }		
	
	protected function title_tab_none( ) 
	{ 
		_e('Запись не найдена','usam');
	}	
			
	protected function get_toolbar_buttons( ) 
	{
		return [];
	}
	
	protected function display_toolbar_buttons( ) 
	{
		$buttons = $this->get_toolbar_buttons();
		foreach ( $buttons as $button )
		{
			if ( isset($button['capability']) && !current_user_can($button['capability']) )
				continue;		
			if ( empty($button['display']) || $button['display'] == 'all' || ($this->id != null && $button['display'] == 'not_null' || $button['display'] == 'null') )
			{
				if ( !empty($button['action_url']) )
				{
					?><div class="action_buttons__button"><a href="<?php echo $button['action_url']; ?>" <?php echo !empty($button['target'])?"target='".$button['target']."'":""; ?> class="button"><?php echo $button['name']; ?></a></div><?php 
				}				
				elseif ( isset($button['group']) )
				{
					?><div class="action_buttons__button"><a href="#" data-action="<?php echo $button['action']; ?>" class="button js-action-item" data-group="<?php echo $button['group']; ?>"><?php echo $button['name']; ?></a></div><?php 
				}	
				elseif ( isset($button['submit']) )
				{					
					?><div class="action_buttons__button"><input type="submit" name="<?php echo $button['submit']; ?>" class="button <?php echo $button['submit']=='save'?'button-primary':''; ?>" value="<?php echo $button['name']; ?>" class="button"></div><?php 
				}
				elseif ( isset($button['vue']) )
				{
					?><a <?php echo implode(' ', $button['vue']); ?> class="button action_buttons__button <?php echo !empty($button['primary'])?'button-primary':''; ?>"><?php echo $button['name']; ?></a><?php 
				}				
				else
				{
					?><div class="action_buttons__button"><a href="#" id="<?php echo $button['id']; ?>" class="button"><?php echo $button['name']; ?></a></div><?php
				}				
			}
		}
	}	

	protected function newsletter_templates( $type, $contact_id = null ) 
	{
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'template']);
		if ( !$newsletters )
			return;
		?>
		<div class="action_buttons__button">
			<div class = "usam_menu">
				<div class="menu_name button"><?php _e('Шаблоны писем','usam'); ?></div>
				<div class="menu_content menu_content_form">
					<div class="menu_items">
					<?php 		
					foreach ( $newsletters as $newsletter )
					{		
						?><div class="menu_items__item js-action-item" data-action="template-<?php echo $newsletter->id; ?>-<?php echo $type; ?>" <?php echo $contact_id?"data-id='$contact_id'":""; ?> data-group="email_newsletters"><?php echo $newsletter->subject; ?></div><?php
					}		
					?>	
					</div>
				</div>
			</div>
		</div>	
		<?php
	}
	
	protected function ability_to_delete( )
	{
		return true;
	}
	
	protected function delete_button( )
	{
		if ( $this->ability_to_delete() )
		{			
			?><div class="action_buttons__button"><a href="<?php echo remove_query_arg(['form','form_name','id', 'subtab']); ?>" class="delete usam-delete-link"><span class="dashicons dashicons-no-alt"></span></a></div><?php	
		}
	}
	
	protected function main_actions_button( )
	{
		$this->display_form_actions( $this->get_main_actions() );
	}
	
	protected function get_main_actions()
	{
		$actions = [];
		if ( $this->JSON )
		{
			$actions[] = ['action' => 'uploadToJSON', 'title' => esc_html__('Выгрузить в json', 'usam'), 'class' => 'upload_json', 'if' => 'data.id>0'];
			$actions[] = ['action' => 'openloadFromJSON', 'title' => esc_html__('Загрузить из json', 'usam'), 'class' => 'load_json'];
		}
		$actions[] = ['action' => 'deleteItem', 'title' => esc_html__('Удалить', 'usam'), 'class' => 'delete', 'if' => 'data.id>0'];
		return $actions;
	}
	
	protected function display_form_actions( $links )
	{
		if( $links )
		{	
			foreach ( $links as $key => $link )
			{		
				if ( isset($link['capability']) && !current_user_can( $link['capability'] ) )
					unset($links[$key]);
			}
			?>
			<div class="action_buttons__button">
				<div class = "usam_menu">
					<div class="menu_name button"><?php _e('Действия','usam'); ?></div>
					<div class="menu_content menu_content_form">
						<div class="menu_items">
						<?php				
						foreach ( $links as $link )
						{		
							if ( !empty($link['group']) )
							{
								?><div class="menu_items__item menu_items__item_<?php echo $link['action']; ?> js-action-item" data-action='<?php echo $link['action']; ?>' data-group='<?php echo $link['group']; ?>' data-id='<?php echo $this->id; ?>'><?php echo $link['title']; ?></div><?php
							}		
							else
							{
								?><div class="menu_items__item <?php echo !empty($link['class'])?'menu_items__item_'.$link['class']:''; ?>" @click="<?php echo $link['action']; ?>" <?php echo !empty($link['if'])?'v-if="'.$link['if'].'"':''; ?>><?php echo $link['title']; ?></div><?php
							}							
						}														
						?>	
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
	
	protected function get_url_go_back( ) 
	{
		return remove_query_arg( array( 'id', 'n', 'form', 'form_name' )  );		
	}	

	public function display_webform( $form = 'view' ) 
	{			
		?>
		<div class="webform">
			<div class ="webform_steps" v-if="main_groups.length!=1">
				<div class ="webform_step" v-for="(group, g) in main_groups" v-html="group.name" :class="{'active':tab==g}" @click="tab=g"></div>
			</div>
			<div class ="edit_form active" v-for="(group, g) in main_groups" v-if="tab==g" :class="{'webform_content':main_groups.length!=1}">
				<div class ="edit_form__item" v-for="(property, k) in properties" v-if="property.group==group.code && property.field_type!='personal_data'">
					<div class ="edit_form__item_name"><span v-html="property.name"></span><span v-if="property.mandatory">*</span>:</div>
					<div class ="edit_form__item_option" v-if="form_type='view'">
						<?php include( usam_get_filepath_admin('templates/template-parts/view-property.php') ); ?>
					</div>
					<div class ="edit_form__item_option" v-else>
						<?php include( usam_get_filepath_admin('templates/template-parts/property.php') ); ?>
					</div>
				</div>
				<div v-for="group2 in propertyGroups" v-if="group2.parent_id==group.id">
					<div class ="edit_form__title" v-html="group2.name"></div>
					<div class ="edit_form__item" v-for="(property, k) in properties" v-if="property.group==group2.code && property.field_type!='personal_data'">
						<div class ="edit_form__item_name"><span v-html="property.name"></span><span v-if="property.mandatory">*</span>:</div>
						<div class ="edit_form__item_option" v-if="form_type='view'">
							<?php include( usam_get_filepath_admin('templates/template-parts/view-property.php') ); ?>
						</div>
						<div class ="edit_form__item_option" v-else>
							<?php include( usam_get_filepath_admin('templates/template-parts/property.php') ); ?>
						</div>
					</div>
				</div>
			</div>	
		</div>	
		<?php
	}		
		
	function display_properties( $form = 'view' )
	{	
		?>
		<usam-box :id="'properties_'+group.code" :handle="false" :title="group.name" v-for="group in propertyGroups" :key="group.id" v-if="check_group(group.code)">
			<template v-slot:body>
				<div class ="edit_form">
					<div class ="edit_form__item" v-for="(property, k) in properties" v-if="property.group==group.code && property.field_type!='personal_data'" :class="{'edit_form__row_error':property.error}">
						<div class ="edit_form__item_name"><span v-html="property.name"></span><span v-if="property.mandatory">*</span>:</div>
						<div class ="edit_form__item_option">
							<?php 
							if ( $form == 'view' )
								include( USAM_FILE_PATH . '/admin/templates/template-parts/view-property.php' );
							else
								include( USAM_FILE_PATH . '/admin/templates/template-parts/property.php' );
							?>
						</div>
					</div>
				</div>
			</template>
		</usam-box>
		<?php
    }
		
	function display_printed_form( $document )
	{
		if ( !current_user_can( 'print_'.$document ) )
			return '';
		$printed_forms = usam_get_printed_forms_document( $document );	
		$count = count($printed_forms);
		$time = time();
		if ( $count == 1 )
		{
			?><div v-if="data.id>0" class="action_buttons__button">
				<a :href="'<?php echo usam_url_action('printed_form', ['form' => $printed_forms[0]['id'], 'time' => $time] ); ?>&id='+data.id" target='_blank' class="button"><?php _e('Печать','usam'); ?></a>
			</div>
			<div v-if="data.id>0" class="action_buttons__button">
				<a :href="'<?php echo usam_url_action('printed_form_to_pdf', ['form' => $printed_forms[0]['id'], 'time' => $time] ); ?>&id='+data.id" target='_blank' class="button">PDF</a>
			</div><?php
		}
		elseif ( $count > 1 )
		{
			foreach ( ['printed_form' => __('Печать','usam'), 'printed_form_to_pdf' => 'PDF'] as $key => $title )
			{
				?>
				<div v-if="data.id>0" class="action_buttons__button">
					<div class = "usam_menu">
						<div class="menu_name button"><?php echo $title; ?></div>
						<div class="menu_content menu_content_form">
							<div class="menu_items">
							<?php 		
							foreach ( $printed_forms as $link )
							{
								?><a class="menu_items__item" :href="'<?php echo usam_url_action($key, ['form' => $link['id'], 'time' => $time]); ?>&id='+data.id" target="_blank"><?php echo $link['title']; ?></a><?php	
							}		
							?>	
							</div>
						</div>
					</div>
				</div>	
				<?php 
			}
		}
		$printed_forms = usam_get_printed_forms_document( $document, 'xlsx-forms' );	
		$count = count($printed_forms);
		if ( $count == 1 )
		{
			?><div v-if="data.id>0" class="action_buttons__button"><a :href="'<?php echo usam_url_action('printed_form_to_excel', ['form' => $printed_forms[0]['id'], 'time' => $time] ); ?>&id='+data.id" target='_blank' class="button">Xlsx</a></div><?php
		}
		elseif ( $count > 1 )
		{
			?>
			<div v-if="data.id>0" class="action_buttons__button">
				<div class = "usam_menu">
					<div class="menu_name button"><?php _e('Excel','usam'); ?></div>
					<div class="menu_content menu_content_form">
						<div class="menu_items">
						<?php 		
						foreach ( $printed_forms as $link )
						{		
							?><a class="menu_items__item" :href="'<?php echo usam_url_action('printed_form_to_excel', ['form' => $link['id'], 'time' => $time]); ?>&id='+data.id" target="_blank"><?php echo $link['title']; ?></a><?php	
						}		
						?>	
						</div>
					</div>
				</div>
			</div>	
			<?php
		}
	}	
		
	public function display_ribbon()
	{					
		if ( $this->id )
			require_once( USAM_FILE_PATH.'/admin/templates/vue-templates/ribbon.php' );	
	}
		
	function add_action_lists( $title = '' )
	{
		$title = $title ? $title : __('Список действий','usam');
		?>
		<usam-box :id="'usam_sidebar_event'" :handle="false">			
			<template v-slot:title>
				<div class='event_actions_title'>
					<?php echo $title; ?>
					<div class='actions_performed' v-if='actions.length'>
						<div class='progress'>
							<div class='progress_text'>{{actionsPerformedPercent}}%</div>
							<div class='progress_bar' :style="'width:'+actionsPerformedPercent+'%'"></div>
						</div>
						<?php _e('выполнено {{actionsPerformed}} из {{actions.length}}','usam'); ?>
					</div>
				</div>
			</template>
			<template v-slot:body>
				<div class='action_lists' v-if="!data.status_is_completed && rights.edit_action">
					<div class="action_lists__action_box"  :class="{'made_action':action.status==1}" v-for="(action, k) in actions" draggable="true" @drop="drop_action($event, k)" @dragover="allowDrop" @dragstart="drag_action($event, k)" @dragend="drag_end_action($event, k)">	
						<div class='action_lists__status' @click="action_status_update(k, action.status?0:1)"></div>
						<div class='action_lists__name' v-if="!action.edit">
							<div class="event_action_name" v-html="action.name" @click="action_edit(k, $event)"></div>
							<a class="delete_item" @click="delete_action(k, $event)" href="#"></a>					
						</div>					
						<input class="text_element_edit" v-show="action.edit" v-model="action.name" v-on:keyup.enter="action.edit=0"/>
					</div>
				</div>
				<div class='action_lists' v-else>
					<div class="action_lists__action_box" :class="{'made_action':action.status==1}" v-for="(action, k) in actions">	
						<div class='action_lists__status'></div>
						<div class='action_lists__name'>
							<div class="event_action_name" v-html="action.name"></div>
						</div>	
					</div>
				</div>	
				<div class='event_action_button' v-if="!data.status_is_completed && rights.add_action">
					<input type="text" class="event_action_input" v-model="new_action" v-on:keyup.enter="add_action" placeholder="<?php _e( 'Пиши и нажми ENTER', 'usam'); ?>">
					<button type="button" @click="add_action" class="button"><?php _e( 'Добавить', 'usam'); ?></button>
				</div>
			</template>
		</usam-box>	
		<?php
	}
	
	function display_attachments( $attr = '' )
	{
		if ( current_user_can('view_my_files') || current_user_can('view_all_files') )
		{
			$change = $this->change;
			require( USAM_FILE_PATH ."/admin/templates/template-parts/attachments.php");
		}
	}
	
	//Vue таблица товаров
	public function table_products_add_button( $columns, $type, $products = null )
	{
		$form_file = usam_get_admin_template_file_path( "table_products_{$type}_form", 'table-form' );
		if ( file_exists($form_file) )
		{	
			require_once( $form_file ); 						
			$class = "USAM_Table_Products_{$type}_Form";
			$list_table = new $class( $type, $this->data );		
			$list_table->display( $columns, $products );
		} 		
	}
	
	public static function product_viewer() 
	{
		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/product-viewer.php' );	
	}
}
?>