<?php
// Класс форм объектов
require_once( USAM_FILE_PATH .'/admin/includes/form.class.php' );		
class USAM_Edit_Form extends USAM_Form
{	
	protected $form_name = '';
	protected $action = 'save';	
	protected $vue = false;
	protected $JSON = false;	
	
	public function __construct( $args = [] ) 
	{ 
		if ( isset($args['id']) )
			$this->id = sanitize_title($args['id']);	
		elseif ( isset($_REQUEST['id']) )
			$this->id = sanitize_title($_REQUEST['id']);	
			
		if ( isset($args['form_name']) )
			$this->form_name = sanitize_title($args['form_name']);	
		elseif ( isset($_REQUEST['form_name']) )
			$this->form_name = sanitize_title($_REQUEST['form_name']);	
			
		$this->get_data_tab();		
		$this->data = apply_filters( "usam_{$this->form_name}_edit_form_data", $this->data, $this );
		if ( empty($this->data) )
			$this->not_exist = true;
		add_action( 'admin_footer', [$this, 'edit_form_js'], 9 );
		parent::__construct( $args );
	}	
		
	public function edit_form_js() 
	{			
		wp_enqueue_script( 'wp-tinymce' );
	}
			
	protected function get_fastnav( ) 
	{
		return [];
	}
	
	protected function toolbar_buttons( ) 
	{					
		$this->display_toolbar_buttons();
		if( $this->vue )
			$this->main_actions_button();
		else
		{
			if ( $this->id != null )
				$this->delete_button();
		}
	}
	
	protected function title_save_button( ) 
	{ 		
		return '<span v-if="data.id>0">'.__('Сохранить','usam').'</span><span v-else>'.__('Добавить','usam').'</span>';
	}
	
	protected function get_toolbar_buttons( ) 
	{
		if ( $this->change )
		{
			if( $this->vue )
				$links = [
					['vue' => ["@click='saveForm'"], 'primary' => true, 'name' => '<span v-if="data.id>0">'.__('Сохранить','usam').'</span><span v-else>'.__('Добавить','usam').'</span>', 'display' => 'all'],
				];
			else
				$links = [
					['submit' => "save", 'name' => $this->id ? __('Сохранить','usam'):__('Добавить','usam'), 'display' => 'all'], 
				];	
		}
		return $links;
	}
		
	protected function display_buttons()
	{    
		if ( $this->id != null )
		{
			submit_button( __('Сохранить и закрыть', 'usam'), 'primary button_save_close', 'save-close', false, array( 'id' => 'submit-save-close' ) ); 
			submit_button( __('Сохранить', 'usam'), 'secondary button_save', 'save', false, array( 'id' => 'submit-save' ) ); 
		}
		else
		{
			submit_button( __('Сохранить', 'usam').' &#10010;', 'primary button_save_close', '', false, array( 'id' => 'submit-add' ) ); 
		}
	}
		
	protected function display_navigation( ) 
	{		
		$nav = $this->get_fastnav();
		if ( !empty($nav) )
		{
			?>			
			<div class="fastnav">			
				<div class="fastnav__title"><?php _e( 'Навигация', 'usam'); ?>:</div>
				<div class="fastnav__list">	
					<?php 				
					foreach ( $nav as $id => $name )
					{
						?><a href="#nav-<?php echo $id; ?>" class="js-move-block" id="fastnav_<?php echo $id; ?>"><?php echo $name; ?></a><?php 
					}
					?>
					<a href="#usam-page-tabs-title" class="js-move-block" id="usam-page-tabs-title"><?php _e( 'В начало', 'usam'); ?>&nbsp;&uarr;</a>
				</div>				
			</div>	
			<?php
		}
	}	
	
	protected function form_attributes( ) { }
	
	protected function form_class( ) { }
	
	public function get_form_id() 
	{
		return $this->form_name;
	}
	
	protected function display_toolbar()
    {
		?>
		<h2 class="tab_title form_toolbar js-fasten-toolbar">
			<span class="form_title_go_back"><a href="<?php echo $this->get_url_go_back(); ?>" class="go_back"><span class="dashicons dashicons-arrow-left-alt2"></span></a><span class="form_title"><?php echo $this->get_title_tab( ); ?></span></span>
			<?php $this->display_navigation(); ?>
			<div class="action_buttons">
				<?php $this->toolbar_buttons();	?>			
			</div>			
		</h2>
		<?php		
	}

// Отображение элемента таблицы	
	public function display()
    {
		?>	
		<div id="edit_form_<?php echo $this->get_form_id(); ?>" <?php echo $this->vue?"v-cloak":""; ?> <?php $this->form_attributes(); ?> class="element_form form_<?php echo $this->form_name; ?> element_form <?php echo $this->id?"edit_element":"new_element"; ?> <?php echo $this->form_class(); ?>" data-id="<?php echo $this->id; ?>">
			<?php
			if ( $this->not_exist )
			{ 
				$this->display_blank_slate();
			}	
			else
			{
				if ( $this->change && !$this->vue ) 
				{
					?><form method='POST' action='' id='element_editing_form'><?php
				}		
				$this->display_toolbar();	
				do_action( 'usam_before_edit_form', $this );		
				$this->display_form();
				if ( $this->change && !$this->vue ) 
				{	
					$screen = get_current_screen();	
					wp_nonce_field('usam-'.$screen->id,'usam_name_nonce_field');
					?>
					<input type='hidden' value='<?php echo $this->action; ?>' name='action' />
					<input type='hidden' value='edit' name='form' />
					<input type='hidden' value='<?php echo $this->form_name; ?>' name='form_name' />
					<?php if ( $this->id != null ) { ?>	
						<input type='hidden' value='<?php echo $this->id; ?>' name='id' />	
					<?php } ?>				
					</form> 
					<?php			
				}
				do_action( 'usam_after_edit_form', $this, $this->form_name );
				do_action( 'usam_after_form', $this );
				if ( $this->JSON )
					require_once( USAM_FILE_PATH.'/admin/includes/modal/modal-load-from-JSON.php' );
			}
			?>				
		</div> 
		<?php	
	}		
	
	public function display_blank_slate(  ) 
	{
		?>
		<div class="blank_state">
			<h2 class="blank_state__message blank_state__icon"><?php _e("Ничего не найдено","usam"); ?></h2>			
		</div>
		<?php
	}
	
	public function display_form( ) 
	{		
		ob_start();
		$this->display_right();
		$content_right = ob_get_clean();
		if ( $content_right )
		{
			?>
			<div class="columns-2">
				<div class = 'page_main_content'>
					<?php $this->display_left(); ?>
				</div>				
				<div class = 'page_sidebar'>	
					<div class = 'menu_fixed_right'>
						<?php echo $content_right; ?>
					</div>	
				</div>	
			</div>		
			<?php
		}
		else
		{
			?>
			<div class = 'page_main_content'>		
				<?php $this->display_left(); ?>
			</div>
			<?php
		}
	}		
	
	function display_right() {}		
	function display_left() {}		
	
	//Вывести боксы
	public function checklist_meta_boxs( $boxs )
	{        	
		?>
		<div id="all_taxonomy" class="all_taxonomy">
			<?php
			foreach ($boxs as $key => $checklist)
			{					
				$this->display_meta_box_group( $key, $checklist ); 
			}
			?>				
		</div>	
      <?php
	}		
	
	public function display_select_type_discont( $name, $checked )
	{  
		$currency = usam_get_currency_sign();
		?>
		<select class="select_type_discont" name="<?php echo $name; ?>">
			<option value="f" <?php echo (($checked=='f')?'selected="selected"':'');?>><?php echo $currency; ?></option>
			<option value="p" <?php echo (($checked=='p')?'selected="selected"':'')?>>%</option>
		</select>
		<?php
	}
	
	public function display_meta_box_group( $group, $checked_list = array(), $title = '' )
	{        	
		if ( $title == '' )
		{
			switch( $group ) 		
			{		
				case 'roles': 
					$title = __('Роли пользователей','usam');
				break;    
				case 'users': 
					$title = __('Посетители','usam');
				break;	
				case 'employees': 
					$title = __('Сотрудники','usam');
				break;				
				case 'products': 
					$title = __('Товары','usam');
				break;	
				case 'weekday': 
					$title = __('Дни недели','usam');
				break;		
				case 'type_prices': 
					$title = __('Цены','usam');
				break;	
				case 'selected_gateway': 
					$title = __('Способ оплаты','usam');
				break;
				case 'delivery_option': 
					$title = __('Варианты доставки','usam');
				break;
				case 'types_products': 
					$title = __('Типы товаров','usam');
				break;				
				case 'selected_shipping': 
					$title = __('Способ доставки','usam');
				break;
				case 'sales_area': 
					$title = __('Мультирегиональность','usam');
				break;
				case 'units': 
					$title = __('Единица измерения','usam');
				break;	
				case 'storages': 
					$title = __('Склады','usam');
				break;				
				case 'types_payers': 
					$title = __('Типы плательщиков','usam');
				break;		
				case 'contractors': 
					$title = __('Поставщики товара','usam');
				break;				
				case 'vk_profiles': 
					$title = __('Профили пользователей','usam');
				break;	
				case 'statuses': 
					$title = __('Статусы','usam');
				break;	
				case 'ok_groups': 
				case 'vk_groups': 
					$title = __('Группы','usam');
				break;							
				default:
					if ( taxonomy_exists('usam-'.$group) )
					{
						$taxonomy = get_taxonomy( 'usam-'.$group );	
						$title = $taxonomy->label; 
					}
					elseif ( taxonomy_exists($group) )
					{
						$taxonomy = get_taxonomy( $group );
						$title = $taxonomy->label; 
					}
					else
						$title = '';                      
				break;
			}
		}		
		ob_start();
		$this->checklist_meta_box( $group, $checked_list );
		$output = ob_get_clean();
		if ( $output )
		{
			?>
			<div id="group-<?php echo $group; ?>" class="taxonomy_box">
				<h4><label><input id="checked_all-<?php echo $group; ?>" type="checkbox"/>&nbsp;<?php echo $title; ?></label></h4>
				<?php echo $output; ?>
			</div>
		  <?php
		}
	}
	
	//Вывести бокс
	public function checklist_meta_box( $meta_box, $checked_list )
	{        	
		$c = 'controller_get_condition_'.$meta_box;
		if ( method_exists( $this, $c ) )
		{
			$data = $this->$c( );
			$output = usam_get_checklist( $meta_box, $data, $checked_list );						
		}
		else						
		{								
			if ( taxonomy_exists('usam-'.$meta_box) )
				$output = $this->build_checkbox_contents('usam-'.$meta_box, $checked_list);
			elseif ( taxonomy_exists($meta_box) )
				$output = $this->build_checkbox_contents($meta_box, $checked_list);   
			else
				$output = $this->build_checkbox_contents($meta_box, $checked_list);
		}
		if ( $output )
		{
			?>
			<div id="taxonomy-<?php echo $meta_box; ?>" class="categorydiv">
				<div id="<?php echo $meta_box; ?>-all" class="tabs-panel">				
					<ul id="<?php echo $meta_box; ?>checklist" class="categorychecklist form-no-clear"><?php echo $output; ?></ul>
			  </div>
			</div>
			<?php
		}
	}	
	
	protected function controller_get_condition_statuses()
	{ 
		require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');		
		$statuses = usam_get_object_statuses(['fields' => ['internalname', 'name'], 'type' => $this->data['type'], 'not_in__internalname' => [$this->data['internalname']]]);
		$result = [];	
		foreach ($statuses as $status) 
		{
            $result[$status->internalname] = $status->name;
        }	
		return $result;
    }
	
	// Поставщики товара
	protected function controller_get_condition_contractors()
	{ 
        $companies = usam_get_companies(['fields' => ['id', 'name'], 'type' => 'contractor']);
		$result = [];
		foreach ($companies as $company) 
		{
            $result[$company->id] = $company->name;
        }	
		return $result;
    }
	
	protected function controller_get_condition_types_products()
	{ 
        $types = usam_get_types_products_sold();
		$result = [];
		foreach ($types as $key => $type) 
		{
            $result[$key] = $type['plural'];
        }
		return $result;
    }
			
	// Условие корзины - роли пользователя
	protected function controller_get_condition_roles()
	{ 
        $roles = get_editable_roles();	
        $result['notloggedin'] = __('Не вошел в систему','usam');
		foreach ($roles as $role => $info) 
		{
            $result[$role] = translate_user_role( $info['name'] );
        }	
		return $result;
    } 
	
	protected function controller_get_condition_storages( )
	{     
		$storages = usam_get_storages( );		
		$results = array();	
		foreach ( $storages as $storage )
		{
			$results[$storage['id']] = $storage['title'];
		}
		return $results;
    } 
	
	protected function controller_get_condition_units( )
	{     
		$units = usam_get_list_units( );		
		$results = array();	
		foreach ( $units as $unit )
		{
			$results[$unit['code']] = $unit['title'];
		}
		return $results;
    } 
	
	// Условие зоны продаж
	protected function controller_get_condition_sales_area( )
	{     
		$results = array();		
		foreach (usam_get_sales_areas() as $value)
		{
			$results[$value['id']] = $value['name'];
		}
		return $results;
    } 
	
	protected function controller_get_condition_types_payers( )
	{     
		$option = get_option('usam_types_payers');
		$grouping = maybe_unserialize( $option );
	
		$results = array();
		foreach ( $grouping as $value )
		{
			$results[$value['id']] = $value['name'];
		}
		return $results;
    } 

	// Условие корзины - типы цен
	protected function controller_get_condition_type_prices( )
	{     
		$prices = usam_get_prices( array('type' => 'R') );		
		$results = array();
		foreach ( $prices as $value )
		{
			$results[$value['code']] = $value['title'];
		}
		return $results;
    }   

	protected function controller_get_condition_delivery_option( )
	{     		
		return [0 => __("Курьером","usam"), 1 => __("Самовывоз","usam")];
    }   		
			
	// Условие корзины - способ оплаты
	public function controller_get_condition_selected_gateway(  )
	{     
		$gateways = usam_get_payment_gateways(['fields' => ['name', 'id'], 'active' => 'all']);		
		$results = array();
		foreach ( $gateways as $gateway )
			$results[$gateway->id] = $gateway->name." ( $gateway->id )";	
			
		return $results;
    }   
	
	// Условие корзины - способ доставки
	public function controller_get_condition_selected_shipping(  )
	{     
		$delivery_service = usam_get_delivery_services(['fields' => ['name', 'id'], 'active' => 'all']);
		
		$shipping = array();
		foreach ($delivery_service as $value)
			$shipping[$value->id] = $value->name." ( $value->id )";	
		
		return $shipping;
    }   
	
	// Условие корзины - дни недели 
	protected function controller_get_condition_weekday( )
	{    	
		return usam_get_weekday();
    }   
	
	// Условие корзины - пользователи
	protected function controller_get_condition_users ( )
	{ 
        $args = array( 'orderby' => 'nicename', 'fields' => array( 'ID','user_nicename','display_name') );
		$users = get_users( $args );    
		$result = array();
		foreach ( $users as $user ) 
		{            
           $result[$user->ID] = "$user->display_name ($user->user_nicename)";
        }	
		return $result;
    }   		
	
	protected function controller_get_condition_employees( )
	{ 
        $args = array( 'orderby' => 'nicename', 'fields' => array( 'ID','user_nicename','display_name'), 'role__in' => array('editor','shop_manager','administrator', 'shop_crm') );
		$users = get_users( $args );    
		$result = array();
		foreach ( $users as $user ) 
		{            
           $result[$user->ID] = "$user->display_name ($user->user_nicename)";
        }	
		return $result;
    }  
		
	protected function build_checkbox_contents ( $taxonomy, $checked_list = [] ) 
	{    
		$walker = new Walker_Category_Checklist;		
		if ( is_array( $checked_list ) ) 
			$checked_list = array_map( 'intval', $checked_list );
		else 
			$checked_list = [];
		$args = ['taxonomy' => $taxonomy, 'selected_cats' => $checked_list ];
		$tax   = get_taxonomy( $taxonomy );

		$args['list_only'] = 0;

		$categories = (array) get_terms(['taxonomy' => $taxonomy, 'get' => 'all']);
		return $walker->walk( $categories, 0, $args );
    } 	
	
	public function titlediv ( $value = '', $placeholder = '' ) 
	{
		if ( $this->change )
		{	
			?> 
			<div id="titlediv">			
				<input type="text" name="name" value="<?php echo htmlspecialchars($value); ?>" placeholder="<?php echo $placeholder == '' ? __('Введите название', 'usam'): $placeholder ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>					
			<?php
		}		
	}
	
	public function selecting_locations( $selecting = array() ) 
	{					
		?>  	
		<div class="locations_checklist">
			<?php usam_locations_checklist(['selected' => $selecting]); ?>
		</div>		
	   <?php   
	}
	
	public function selecting_type_prices( )
	{        
		$this->display_meta_box_group( 'type_prices', $this->data['type_prices'] );		
	} 
	
	public function box_status( $checked = 0 )
	{  		
		$checked = (int)$checked;
		?>	
		<div class="usam_checked">
			<div class="usam_checked__item js-checked-item usam_checked-active <?php echo $checked?'checked':''; ?>">
				<div class="usam_checked_enable">
					<input type="checkbox" name="active" class="input-checkbox" value="1" <?php checked($checked, 1); ?>>
					<label><?php _e('Активно', 'usam'); ?></label>
				</div>										
			</div>
		</div>    
	   <?php
	}
	
	public function vue_box_status()
	{  				
	   usam_add_box( 'usam_status', __('Статус активности','usam'), function() { 		
			?>	
			<div class="usam_checked">
				<div class="usam_checked__item usam_checked-active" :class="{'checked':data.active}" @click="data.active=data.active?0:1">
					<div class="usam_checked_enable">
						<input type="checkbox" class="input-checkbox" value="1" v-model="data.active">
						<label><?php _e('Активно', 'usam'); ?></label>
					</div>										
				</div>
			</div>    
		   <?php
	   });
	}
	
	public function add_box_status_active( $checked = 0 )
	{	
		usam_add_box( 'usam_status', __('Статус активности','usam'), [$this, 'box_status'], $checked );
	}
	
	public function display_rules_work_basket( $conditions ) 
	{
		USAM_Admin_Assets::basket_conditions();
		require_once( USAM_FILE_PATH . '/admin/includes/rules/basket_discount_rules.class.php' );			
		$rules_work_basket = new USAM_Basket_Discount_Rules( );
		$rules_work_basket->load();
		$rules_work_basket->display( $conditions );	
	}	
	
	public function add_tinymce_description( $description, $textarea_name = 'description' )
	{	
		static $i = 0;
		$i++;
		?>
		<div class ="description_tinymce">		
			<?php
			add_editor_style( USAM_URL . '/admin/assets/css/email-editor-style.css' );		
			wp_editor( $description, "description_tinymce_$i", [
				'textarea_name' => $textarea_name,
				'media_buttons'=> false,
				'textarea_rows' => 7,	
				'wpautop' => 0,	
				'quicktags' => false,				
				'tinymce' => [
					'autoresize_min_height' => 200,
					'wp_autoresize_on' => true,
					'plugins'               => 'wpautoresize',						
					'theme_advanced_buttons3' => 'invoicefields,checkoutformfields',
				]
			]); 
			?>
		</div>		
		<?php	
	}
	
	public function add_box_description( $description, $name = 'description', $title = ''  )
	{	
		$args['name'] = $name;		
		$args['description'] = $description;	
		if ( $title == '' )
			$title = __('Описание','usam');
		
		usam_add_box( 'usam_description', $title, array( $this, 'box_description' ), $args );	
	}
	
	public function box_description( $args ) 
	{		
		if ( $this->change )
		{
		?>
		<div class ="form_description">			
			<textarea name='<?php echo $args['name']; ?>'><?php echo stripcslashes($args['description']); ?></textarea>
		</div>
		<?php
		}
		else
		{
			?><p><?php echo stripcslashes($args['description']); ?></p><?php
		}
	}
	
	public function box_help_setting( $args, $id, $hidden = 0 ) 
	{		
		if ( is_array($args) )
		{
			$title = $args['title'];
			$description = $args['description'];
		}
		else
			return;		
		?>		
		<div class="box_help_setting <?php if ( $hidden == 0 ) { echo "hidden"; } ?>" id ="<?php echo $id; ?>">
			<h4><?php echo $title; ?><span> - <?php _e('объяснение', 'usam')?></span></h4>
			<p><?php echo $description; ?>        
			</p>
		</div>		
		<?php		
	}	
	
	public function display_imagediv( $thumbnail_id, $title = '', $button_text = ''  )
	{	
		if ( $button_text == '' )				
			$button_text = __('Задать миниатюру', 'usam');
		$args['thumbnail_id'] = $thumbnail_id;
		$args['title'] = $title;
		$args['button_text'] = $button_text;
		usam_add_box( 'usam_thumbnail', __('Миниатюра','usam'), [$this, 'imagediv'], $args );	
	}
		
	public function imagediv( $args ) 
	{					
		$image_url = wp_get_attachment_image_url( $args['thumbnail_id'], 'full' );
		$hide = '';
		if ( empty($image_url) )
		{
			$image_url = USAM_CORE_IMAGES_URL . '/no-image-uploaded.png';	
			$hide = 'hide';
		}		
		$anonymous_function = function() { 				
			USAM_Admin_Assets::set_thumbnails();
			return true; 
		};
		add_action('admin_footer', $anonymous_function, 1);
		
		?>
		<div class="usam_thumbnail">
			<a id="select_thumbnail" data-attachment_id="<?php echo $args['thumbnail_id']; ?>" data-title="<?php echo $args['title']; ?>" title="<?php _e( 'Нажмите, чтобы установить миниатюру', 'usam'); ?>" data-button_text="<?php echo $args['button_text']; ?>" href="<?php echo esc_url( admin_url( 'media-upload.php?tab=gallery&TB_iframe=true&width=640&height=566' ) ) ?>" class="image_container js-thumbnail-add">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="">
			</a>
			<input type='hidden' class='js-thumbnail-id' name ="thumbnail" value='<?php echo $args['thumbnail_id']; ?>' />		
			<div class="js-thumbnail-remove <?php echo $hide; ?>"><?php esc_html_e( 'Удалить миниатюру', 'usam'); ?></div>
		</div>			
		<?php		
	}	
	
	protected function display_user_block( $user_id, $input_name, $text, $company = false )
	{		
		?>
		<input type="hidden" name="<?php echo $input_name; ?>" class ="js-user-none" value="0"/>
		<div class='user_block'>	
			<?php	
			if ( $user_id )
			{
				$contact = usam_get_contact( $user_id, 'user_id' );
				if ( !empty($contact['id']) )
					$link = usam_get_contact_url( $contact['id'] ); 
				else
					$link = '';
				?>			
				<div class='user_foto'><a href="<?php echo $link; ?>" class ='image_container usam_foto'><img src='<?php echo usam_get_contact_foto( $contact['id'] ); ?>'></a></div>
				<div class='user_name'>
					<a href="<?php echo $link; ?>"><?php echo $contact['appeal']; ?></a>
					<?php				
					if ( $company )
					{
						$company = usam_get_company( $user_id, 'user_id' );
						if ( !empty($company) )	
						{
							?>		
							<div class='user_name'><a href="<?php echo usam_get_company_url( $company['id'] ); ?>"><?php echo $company['name']; ?></a></div>	
							<?php 
						} 							
					} 
					?>
				</div>
				<input type="hidden" name="<?php echo $input_name; ?>" class="js-user-id" value="<?php echo $user_id; ?>"/>				
				<a class="js_delete_action" href="#"></a>
				<?php
			}
			else
				echo $text;		
			?>
		</div>		
		<?php		
	}
	
	
	function display_manager_metabox()
	{	
		$this->display_user_block( $this->data['manager_id'], 'manager_id', __('Нет ответственного','usam') );
	}	
	
	function display_user_metabox()
	{		
		$this->display_user_block( $this->data['user_id'], 'user_id', __('Нет пользователя','usam') );
	}
		
	function display_select_contacts( )
	{ 
		?>					
		<div class='contacts-block object_column'>			
			<?php
			if ( empty($this->data['contacts']) )
			{
				?>
					<p class = "items_empty"><?php _e( 'Нет контактов', 'usam'); ?></p>
				<?php
			}
			else
			{		
				foreach ( $this->data['contacts'] as $contact )
				{									
					$link = usam_get_contact_url( $contact->id ); 
					?>		
					<div class='user_block' id = "contact_<?php echo $contact->id; ?>">	
						<div class='user_foto'><a href="<?php echo $link; ?>" class ='image_container usam_foto'><img src='<?php echo usam_get_contact_foto( $contact->id ); ?>'></a></div>
						<div class='user_name'><a href="<?php echo $link; ?>"><?php echo $contact->appeal; ?></a></div>					
						<a class="js_delete_action" href="#"></a>
						<input type='hidden' name='contacts_ids[]' value='<?php echo $contact->id; ?>'/>	
					</div>	
					<?php
				}
			}
			?>	
		</div>	
		<?php
	}		
			
	function display_select_users()
	{
		?>					
		<div class='users_block object_column'>
			<?php
			if ( !empty($this->users) )
			{ 
				foreach ( $this->users as $user_id )
				{				
					$contact = usam_get_contact( $user_id, 'user_id' );
					if ( !empty($contact['id']) ) 
						$link = usam_get_contact_url( $contact['id'] ); 
					else
						$link = '';				
					?>		
					<div class='user_block' data-user_id="<?php echo $user_id; ?>" id="user_block-<?php echo $user_id; ?>">	
						<div class='user_foto'><a href="<?php echo $link; ?>" class ='image_container usam_foto'><img src='<?php echo usam_get_contact_foto( $user_id, 'user_id' ); ?>'></a></div>
						<div class='user_name'><a href="<?php echo $link; ?>"><?php echo $contact['appeal']; ?></a></div>					
						<a class="js_delete_action" href="#"></a>
						<input type='hidden' value='<?php echo $user_id; ?>' name='user_ids[]' />
					</div>	
					<?php
				}
			}
			else
			{
				?><p class = "items_empty"><?php _e( 'Не выбраны', 'usam'); ?></p><?php
			}	
			?>	
		</div>	
		<?php
	}	
	
	function display_properties( $form = 'edit' )
	{
		parent::display_properties( 'edit' );
	}		
	
	function display_form_status( $type )
	{	
		?>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='object_status'><?php esc_html_e( 'Статус', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<?php 				
				if ( $type == 'contact' || $type == 'company' || current_user_can('edit_status_'.$type) ) 
				{ 
					$statuses = usam_get_object_statuses_by_type( $type, $this->data['status'] );
					?>
					<select name="status" id = "object_status">
						<?php
						foreach ( $statuses as $status ) 
						{									
							$attr = [];
							$attr[] = $status->color != ''?'background:'.$status->color:'';	
							$attr[] = $status->text_color != ''?'color:'.$status->text_color:'';
							$style = $attr?'style="'.implode('; ',$attr).'"':'';				
							?><option value='<?php echo $status->internalname; ?>' <?php echo $style; ?> <?php selected($this->data['status'], $status->internalname); ?>><?php echo $status->name; ?></option><?php
						}
						?>
					</select>	
				<?php } 
				else 
				{ 
					echo usam_get_object_status_name( $this->data['status'], $type );
				}					
				?>	
			</div>
		</div>
		<?php 		
	}
		
	public function display_icon() 
	{	
		?>
		<div class="form_settings__sections">
			<div v-for="(name, icon) in tabSettings[tab].icons" @click="section[tab]=icon" :class="{'active':section[tab]==icon}" class="form_settings_icon">
				<span class="svg_icon"><svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+icon"></use></svg></span>
				<div v-html="name" class="form_settings_icon_name"></div>
			</div>		
		</div>
		<?php	
	}
}
?>