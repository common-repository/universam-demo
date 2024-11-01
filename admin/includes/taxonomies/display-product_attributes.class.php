<?php
new USAM_Product_Attributes_Forms_Admin();
class USAM_Product_Attributes_Forms_Admin
{
	function __construct( ) 
	{		
		add_action( 'created_usam-product_attributes', array( $this, 'save' ), 10 , 2 ); //После создания
		add_action( 'edited_usam-product_attributes', array( $this, 'save' ), 10 , 2 ); //После сохранения		
		
		add_action( 'usam-product_attributes_edit_form_fields', array( $this, 'edit_forms'), 10 , 2  ); // форма редактирования		
		add_action( 'usam-product_attributes_add_form_fields', array( $this, 'add_forms') ); // форма добавления
		
		if( isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'usam-product_attributes' && isset($_GET['tag_ID']) )
			add_action('admin_footer', array(&$this, 'admin_footer'));		
		
		add_filter( 'manage_edit-usam-product_attributes_columns', array( $this, 'add_columns' ), 1 );
		add_filter( 'manage_usam-product_attributes_custom_column', array( $this, 'parse_column' ), 10, 3 );
		add_filter( 'manage_edit-usam-product_attributes_sortable_columns', array( $this, 'sortable_columns' ), 10 );
		
		if( isset($_REQUEST['taxonomy']) && $_REQUEST['taxonomy'] == 'usam-product_attributes' )
			add_action( 'parse_term_query', [$this, 'parse_term_query'] );
		add_filter( 'bulk_actions-edit-usam-product_attributes', array( $this, 'bulk_actions_edit' ) );
	
		if( isset($_REQUEST['action']) )
			$this->action_form();		
	}	
	
	public function action_form( )
	{ 		
		if( !isset($_REQUEST['delete_tags']) )
			return;	
		
		$ids = array_map('intval', $_REQUEST['delete_tags']);
		switch( $_REQUEST['action'] ) 
		{			
			case 'filter':					
				foreach( $ids as $term_id )
				{
					usam_update_term_metadata($term_id, 'filter', 1 );	
					usam_calculate_product_filters( $term_id );	
				}					
			break;
			case 'no_filter':										
				foreach( $ids as $term_id )		
					usam_update_term_metadata($term_id, 'filter', 0 );	
				global $wpdb;
				$product_attribute_values = usam_get_product_attribute_values(['attribute_id' => $ids]);
				$filter_ids = array();
				foreach( $product_attribute_values as $option )	
					$filter_ids[] = $option->id;	
				if ( !empty($filter_ids) )
					$wpdb->query("DELETE FROM `".usam_get_table_db('product_filters')."` WHERE filter_id IN (".implode(',',$filter_ids).")"); 				
			break;
		}
	}
	
	public function bulk_actions_edit( $actions )
	{ 
		$actions['filter'] = __('Включить фильтр','usam');
		$actions['no_filter'] = __('Отключить фильтр','usam');
		return $actions;
	}

	public function parse_term_query( $query )
	{ 
		if ( !empty($query->query_vars['taxonomy']) && in_array('usam-product_attributes', $query->query_vars['taxonomy']) )
		{
			if( isset($_REQUEST['orderby']))
			{
				if( $_REQUEST['orderby'] == 'type' )
				{
					$query->query_vars['meta_key'] = 'field_type';
					$query->query_vars['orderby'] = 'meta_value';	
				}
				elseif( $_REQUEST['orderby'] == 'filter' )
				{
					$query->query_vars['meta_key'] = 'filter';
					$query->query_vars['orderby'] = 'meta_value_num';	
				}
				if( $_REQUEST['orderby'] == 'search_attribute' )
				{
					$query->query_vars['meta_key'] = 'search';		
					$query->query_vars['orderby'] = 'meta_value_num';	
				}
			}		
			$query->query_vars['relationships_cache'] = 'usam-category';	
			$query->query_vars['term_meta_cache'] = true;	
		}
		remove_action( 'parse_term_query', [$this, 'parse_term_query'] );
	}		
	
	public function sortable_columns( array $columns )
	{ 	
		$columns['type'] = 'type';	
		$columns['filter'] = 'filter';
		$columns['search_attribute'] = 'search_attribute';
		return $columns;
	}	
	
	public function add_columns( array $columns )
	{ 	
		unset( $columns["description"] );
		unset( $columns["slug"] );		
		$columns['category'] = __('Категории', 'usam');
		$columns['filter'] = __('Фильтр', 'usam');
		$columns['search_attribute'] = __('Поиск', 'usam');
		$columns['type'] = __('Тип', 'usam');
		unset($columns['posts']);
		return $columns;
	}
	
	public function parse_column( $content, $column_name, $term_id ) 
	{ 
		switch( $column_name ) 
		{
			case 'category':				
				$term = get_term_by( 'id', $term_id, 'usam-product_attributes' );
				if( $term->parent )
				{					
					$categories = usam_get_related_terms( $term_id );
					if( $categories )
					{
						$out = [];
						foreach( $categories as $term )
							$out[] = "<a href='".admin_url("edit.php?post_type=usam-product&amp;usam-category={$term->slug}")."'> ".$term->name."</a>";
						echo join( ', ', $out );
					} 
					else
						_e("Во всех категориях","usam");
				}
			break;
			case 'filter':	
				$filter = usam_get_term_metadata($term_id, 'filter');		
				echo $filter?'<span class="item_status_valid item_status">'.__('Да','usam').'</span>':'';					
			break;
			case 'search_attribute':	
				$search = usam_get_term_metadata($term_id, 'search');
				echo $search?'<span class="item_status_valid item_status">'.__('Да','usam').'</span>':'';			
			break;
			case 'type':	
				$type = usam_get_term_metadata($term_id, 'field_type');				
				switch( $type ) 
				{
					case 'T':
						_e('Текст','usam');
					break;
					case 'O':
						_e('Число','usam');
					break;
					case 'C':
						_e('Один флажок','usam');
					break;
					case 'M':
						_e('Несколько флажков','usam');
					break;
					case 'S':
						_e('Текстовые варианты','usam');
					break;
					case 'N':
						_e('Числовые варианты','usam');
					break;
					case 'BUTTONS':
						_e('Выбор варианта кнопками','usam');
					break;
					case 'AUTOCOMPLETE':
						_e('Выбор варианта поиском','usam');
					break;
					
					case 'PRICES':
						_e('Выбор типа цены','usam');
					break;					
					case 'COLOR':
						_e('Цвет','usam');
					break;
					case 'COLOR_SEVERAL':
						_e('Несколько цветов','usam');
					break;
					case 'F':
						_e('Файл','usam');
					break;
					case 'A':
						_e('Удаленные агенты','usam');
					break;
					case 'D':
						_e('Дата','usam');
					break;
					case 'TIME':
						_e('Время','usam');
					break;
					case 'L':
						_e('Ссылка','usam');
					break;					
					case 'LBLANK':
						_e('Ссылка в новом окне','usam');
					break;
					case 'LDOWNLOAD':
						_e('Ссылка скачать','usam');
					break;					
					case 'LDOWNLOADBLANK':
						_e('Ссылка скачать в новом окне','usam');
					break;				
					case 'YOUTUBE':
						_e('Ссылка на канал в youtube','usam');
					break;
				}
			break;
		}			
	}
	
	function add_forms( ) 
	{	
		$settings = ['mandatory' => 0, 'do_not_show_in_features' => 0, 'filter' => 0, 'important' => 0,'sorting_products' => 0, 'compare_products' => 0, 'switch_to_selection' => 0, 'search' => 0, 'type' => 'T', 'admin_column' => 0, 'rating' => 0];	
		?>		
		<div id="add_new_term" class="postbox usam_box">
			<h3 class="usam_box__title"><?php esc_html_e('Дополнительные настройки', 'usam'); ?></h3>
			<div class="inside">
				<table class ="form-table">				
					<?php $this->display_settings( $settings ); ?>
				</table>
				<p><?php esc_html_e('Остальные настройки Вы можете сделать, открыв свойство.', 'usam'); ?></p>
			</div>
		</div>	
	  <?php
	}
	
	function display_settings( $settings ) 
	{	
		$data = ['important' => __("Важная", "usam"), 'mandatory' => __("Обязательное", "usam"), 'do_not_show_in_features' => __( "Скрыть в характеристиках", "usam" ), 'rating' => __("Рейтинг по характеристике", "usam"), 'switch_to_selection' => __( "Кликабельно в характеристиках", "usam" ), 'filter' => __( "Фильтр по этому свойству", "usam" ), 'sorting_products' => __( "Сортировать по этому свойству", "usam" ), 'compare_products' => __( "Не показывать в сравнении товаров", "usam" ), 'search' => __("Поиск по этому свойству", "usam"), 'admin_column' => __("Колонка в списке товаров", "usam") ];
		if ( $settings['type'] != 'S' && $settings['type'] != 'N' ) 
			unset($data['switch_to_selection']);
		foreach( $data as $key => $name )
		{
			?>
			<tr>
				<th scope="row" valign="top"><label for="type_field"><?php echo $name; ?>:</label></th>
				<td>
					<label>
						<input type='radio' value='1' name='<?php echo $key; ?>' <?php checked( $settings[$key], 1 ); ?>> 		
						<?php _e( 'Да', 'usam'); ?>
					</label> &nbsp;
					<label>
						<input type='radio' value='0' name='<?php echo $key; ?>' <?php checked( $settings[$key], 0 ); ?>>
						<?php _e( 'Нет', 'usam'); ?>
					</label>						
				</td>
			</tr>
		<?php } ?>		
		<tr>
			<th scope="row" valign="top"><label for="type_field"><?php esc_html_e( "Тип", "usam" ); ?>:</label></th>
			<td>
				<select id="type_product_attributes" name="type_product_attributes">
					<optgroup label="<?php esc_html_e( "Базовый тип", "usam" ); ?>">							
						<option value="T"<?php selected($settings['type'], 'T'); ?>><?php _e( 'Текст', 'usam'); ?></option>
						<option value="DESCRIPTION"<?php selected($settings['type'], 'DESCRIPTION'); ?>><?php _e( 'Описание', 'usam'); ?></option>
						<option value="O"<?php selected($settings['type'], 'O'); ?>><?php _e( 'Число', 'usam'); ?></option>
						<option value="C"<?php selected($settings['type'], 'C'); ?>><?php _e( 'Галочка', 'usam'); ?></option>
					</optgroup>				
					<optgroup label="<?php esc_html_e( "Выбор одного из готовых вариантов", "usam" ); ?>">							
						<option value="S"<?php selected($settings['type'], 'S'); ?>><?php _e( 'Текст с вариантами выбора', 'usam'); ?></option>
						<option value="N"<?php selected($settings['type'], 'N'); ?>><?php _e( 'Число с вариантами выбора', 'usam'); ?></option>
						<option value="AUTOCOMPLETE"<?php selected($settings['type'], 'AUTOCOMPLETE'); ?>><?php _e( 'Выбор варианта поиском', 'usam'); ?></option>
						<option value="BUTTONS"<?php selected($settings['type'], 'BUTTONS'); ?>><?php _e( 'Выбор варианта кнопками', 'usam'); ?></option>						
						<option value="COLOR"<?php selected($settings['type'], 'COLOR'); ?>><?php _e( 'Цвет', 'usam'); ?></option>						
					</optgroup>	
					<optgroup label="<?php esc_html_e( "Выбор нескольких из готовых вариантов", "usam" ); ?>">
						<option value="M"<?php selected($settings['type'], 'M'); ?>><?php _e( 'Несколько', 'usam'); ?></option>
						<option value="COLOR_SEVERAL"<?php selected($settings['type'], 'COLOR_SEVERAL'); ?>><?php _e( 'Несколько цветов', 'usam'); ?></option>						
					</optgroup>						
					<optgroup label="<?php esc_html_e( "Данные из базы", "usam" ); ?>">						
						<option value="A"<?php selected($settings['type'], 'A'); ?>><?php _e( 'Выбор сотрудника', 'usam'); ?></option>
						<option value="PRICES"<?php selected($settings['type'], 'PRICES'); ?>><?php _e( 'Тип цены', 'usam'); ?></option>						
					</optgroup>	
					<optgroup label="<?php esc_html_e( "Другое", "usam" ); ?>">	
						<option value="D"<?php selected($settings['type'], 'D'); ?>><?php _e( 'Дата', 'usam'); ?></option>		
						<option value="F"<?php selected($settings['type'], 'F'); ?>><?php _e( 'Файл', 'usam'); ?></option>		
						<option value="L"<?php selected($settings['type'], 'L'); ?>><?php _e( 'Ссылка', 'usam'); ?></option>	
						<option value="LBLANK"<?php selected($settings['type'], 'LBLANK'); ?>><?php _e( 'Ссылка в новом окне', 'usam'); ?></option>
						<option value="LDOWNLOAD"<?php selected($settings['type'], 'LDOWNLOAD'); ?>><?php _e( 'Ссылка скачать', 'usam'); ?></option>	
						<option value="LDOWNLOADBLANK"<?php selected($settings['type'], 'LDOWNLOADBLANK'); ?>><?php _e( 'Ссылка скачать в новом окне', 'usam'); ?></option>	
						<option value="YOUTUBE"<?php selected($settings['type'], 'YOUTUBE'); ?>><?php _e( 'Ссылка на канал в youtube', 'usam'); ?></option>
					</optgroup>							
				</select>
			</td>
		</tr>
		<?php		
	}
	
	function admin_footer( ) 
	{
		$html = '<div class="modal-body"><div class="categorydiv modal-scroll"><ul id="usam-categorychecklist" class="categorychecklist form-no-clear"></ul></div>';
		$html .= "<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'>".__('Добавить', 'usam')."</button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'>".__('Отменить', 'usam')."</button>
		</div></div>";
		echo usam_get_modal_window( __('Добавить категории','usam'), 'display_category_window', $html );	
		wp_enqueue_script( 'v-color' );		
	}

	function edit_forms( $tag, $taxonomy) 
	{
		if( $tag->parent != 0 )
		{
			$mandatory = usam_get_term_metadata($tag->term_id, 'mandatory');	
			$filter = usam_get_term_metadata($tag->term_id, 'filter');
			$rating = usam_get_term_metadata($tag->term_id, 'rating');
			$search = usam_get_term_metadata($tag->term_id, 'search');
			$sorting_products = usam_get_term_metadata($tag->term_id, 'sorting_products');
			$compare_products = usam_get_term_metadata($tag->term_id, 'compare_products');			
			$important = usam_get_term_metadata($tag->term_id, 'important');
			$do_not_show_in_features = usam_get_term_metadata($tag->term_id, 'do_not_show_in_features');
			$switch_to_selection = usam_get_term_metadata($tag->term_id, 'switch_to_selection');
			
			$settings['type'] = usam_get_term_metadata($tag->term_id, 'field_type');	
			$ready_options = usam_get_product_attribute_values(['attribute_id' => $tag->term_id, 'number' => 500]);
			
			$settings['mandatory'] = empty($mandatory)?0:1;	
			$settings['rating'] = empty($rating)?0:1;				
			$settings['filter'] = empty($filter)?0:1;
			$settings['sorting_products'] = empty($sorting_products)?0:1;
			$settings['search'] = empty($search)?0:1;				
			$settings['compare_products'] = empty($compare_products)?0:1;	
			$settings['important'] = empty($important)?0:1;	
			$settings['do_not_show_in_features'] = empty($do_not_show_in_features)?0:1;	
			$settings['switch_to_selection'] = empty($switch_to_selection)?0:1;				
			$settings['admin_column'] = usam_get_term_metadata($tag->term_id, 'admin_column')?1:0;			
			
			$this->display_settings( $settings ); 
			usam_vue_module('paginated-list');	
			?>
			<tr id = "ready_options" v-show="type == 'M' || type == 'S' || type == 'N' || type == 'COLOR' || type == 'BUTTONS' || type == 'AUTOCOMPLETE' || type == 'COLOR_SEVERAL'">
				<th scope="row" valign="top"><label for="type_field"><?php esc_html_e( "Варианты", "usam" ); ?>:</label></th>
				<td :class="{'level_table_load':load}">										
					<input v-for="row in delete_rows" type="hidden" name="delete_options_ids[]" v-model="row.id">
					<level-table :lists='rows' @delete="del">						
						<template v-slot:head="slotProps">
							<div class="tablenav-pages one-page" v-if="count>20">
								<span class="displaying-num">{{count}} элемента</span>
							</div>	
						</template>	
						<template v-slot:thead="slotProps">
							<th class="column-cb"></th>	
							<th class="column-code"><?php _e( 'Код', 'usam'); ?></th>
							<th class="column-value"><?php _e( 'Значение', 'usam'); ?></th>
							<th class="column-slug"><?php _e( 'Ярлык', 'usam'); ?></th>
							<th class="column-actions column_actions">
								<span v-show="!cb.length"><?php _e( 'Действия', 'usam'); ?></span>
								<span class="button" v-show="cb.length && main" @click="combine"><?php _e( 'Объединить', 'usam'); ?></span>
							</th>		
						</template>			
						<template v-slot:tbody="slotProps">
							<td class="column-cb" :class="{'selected':slotProps.row.cb}">
								<input type="checkbox" v-model="slotProps.row.cb">
							</td>
							<td class="column-code" v-if="type == 'COLOR' || type == 'COLOR_SEVERAL'">										
								<color-picker v-model="slotProps.row.code" placeholder="#000000" name="ready_options[code][]"></color-picker>	
								<input type="hidden" name="ready_options[code][]" v-model="slotProps.row.code">									
							</td>
							<td class="column-code" v-else>			
								<input type="text" name="ready_options[code][]" v-model="slotProps.row.code">									
							</td>
							<td class="column-value">
								<input :ref="'value'+slotProps.k" type="text" name="ready_options[value][]" v-model="slotProps.row.value" @keyup="keyup(slotProps.k, $event)">
								<input type="hidden" name="ready_options[id][]" v-model="slotProps.row.id">								
							</td>
							<td class="column-slug">
								<input type="text" name="ready_options[slug][]" v-model="slotProps.row.slug" @keyup.enter="add(slotProps.k)">
							</td>
							<td class="column_actions">		
								<?php usam_system_svg_icon("drag", ["draggable" => "true", "v-show" => "!cb.length"]); ?>
								<?php usam_system_svg_icon("plus", ["@click" => "slotProps.add(slotProps.k)", "v-show" => "!cb.length"]); ?>
								<?php usam_system_svg_icon("minus", ["@click" => "slotProps.del(slotProps.k)", "v-show" => "!cb.length"]); ?>	
								<selector v-model="main" v-show="slotProps.row.cb" :items="[{id:slotProps.row.id, name:'<?php _e('Основная', 'usam'); ?>'}]"></selector>
							</td>
						</template>
						<template v-slot:body>
							<paginated-list @change="page=$event" :page="page" :count='count' :size='size'></paginated-list>
						</template>
					</level-table>
				</td>
			</tr>				
			<tr class="product_attributes_category">
				<th scope="row" valign="top">
					<label for="type_field"><?php esc_html_e( "Прикрепить к категориям", "usam" ); ?>:</label>
				</th>
				<td>
					<div class ="box_category">
						<div class="categories_header">	
							<a href="" id="add_category"><?php esc_html_e( '+ Добавить категорию', 'usam') ?></a>
							<h2><?php esc_html_e( "Прикрепленные категории", "usam" ); ?></h2>
						</div>
						<div class="categories">				
							<table>
							<?php
								$categories = usam_get_related_terms( $tag->term_id );
								if( !empty($categories) )
								{
									foreach( $categories as $category )
									{												
										echo "<tr><td><input value='$category->term_id' type='hidden' name='tax_input[usam-category][]' id='in-usam-category-$category->term_id'>".$category->name."<a href='' id='delete_category'>Удалить</a></td></tr>";
									}
								}
								else
									echo "<tr id='no_data'><td>".__( "Во всех категориях", "usam" )."</td></tr>";
							?>					
							</table>
						</div>		
					</div>						
				</td>
			</tr>
		  <?php
		}
		else
		{
			
		}
	}	
	
	/**
	 * Сохраняет данные
	 */
	function save( $term_id, $tt_id )
	{		
		global $wpdb;
		if( !empty($_POST)  ) 
		{									
			$sorting_products = !empty($_POST['sorting_products'])?1:0;
			usam_update_term_metadata( $term_id, 'sorting_products', $sorting_products );
			
							
			$compare_products = !empty($_POST['compare_products'])?1:0;
			usam_update_term_metadata( $term_id, 'compare_products', $compare_products );		
					
			$term = get_term( $term_id, 'usam-product_attributes' );	
			if( $term->parent != 0 )
			{							
				$rating = !empty($_POST['rating'])?1:0;
				usam_update_term_metadata( $term_id, 'rating', $rating );		
				
				$product_attribute_values = usam_get_product_attribute_values(['attribute_id' => $term_id]);
				$filter_ids = [];
				foreach( $product_attribute_values as $option )	
					$filter_ids[] = $option->id;
				$args = ['post_status' => 'publish'];
				$args['attributes_query'] = [['key' => $term->slug, 'compare' => 'EXISTS']];
				
				$type = isset($_POST['type_product_attributes'])?strtoupper(sanitize_title($_POST['type_product_attributes'])):"T";				
				if( empty($_POST['tax_input']) )
				{				
					usam_delete_taxonomy_relationships(['term_id1' => $term_id, 'taxonomy' => 'usam-category']);	
				}
				else		
				{			
					$term_ids = usam_get_taxonomy_relationships_by_id( $term_id, 'usam-category', 1 );
					$category  = stripslashes_deep($_POST['tax_input']['usam-category']);	
					$term_ids = array_diff($term_ids, $category);
					$ids = usam_get_products(['fields' => 'ids', 'stocks_cache' => false, 'prices_cache' => false, 'tax_query' => [['taxonomy' => 'usam-category', 'field' => 'id', 'terms' => $category, 'operator' => 'IN']]]);
					if ( $filter_ids && $ids )
						$wpdb->query("DELETE FROM `".usam_get_table_db('product_filters')."` WHERE filter_id IN (".implode(',',$filter_ids).") AND product_id NOT IN(".implode(',',$ids).")"); 
					if ( $ids )
						$wpdb->query("DELETE FROM `".usam_get_table_db('product_attribute')."` WHERE meta_key='$term->slug' AND product_id NOT IN(".implode(',',$ids).")"); 
					if( !empty($term_ids) )
					{									
						foreach( $term_ids as $id )						
							usam_delete_taxonomy_relationships(['term_id1' => $term_id, 'term_id2' => $id, 'taxonomy' => 'usam-category']);
					}					
					foreach( $category as $category_id )
					{							
						usam_set_taxonomy_relationships($term_id, $category_id, 'usam-category' );	
					}	
				}								
				$old_filter = usam_get_term_metadata($term_id, 'filter');
				$filter = !empty($_POST['filter']) ?1:0;
				$calculate_product_filters = false;		
				if( !$filter )
				{
					if ( !empty($filter_ids) )
						$wpdb->query("DELETE FROM `".usam_get_table_db('product_filters')."` WHERE filter_id IN (".implode(',',$filter_ids).")"); 
				}		
				elseif( $old_filter != $filter )
				{
					$calculate_product_filters = true;					
				}
				usam_update_term_metadata($term_id, 'filter', $filter );							
				if( isset($_POST['mandatory']) )
				{
					$mandatory =(bool)$_POST['mandatory'];
					usam_update_term_metadata($term_id, 'mandatory', $mandatory );	
				}		
				if( isset($_POST['important']) )
				{
					$important =(bool)$_POST['important'];
					usam_update_term_metadata($term_id, 'important', $important );
				}										
				$old_type = usam_get_term_metadata($term_id, 'field_type');	
				if( !empty($old_type) && $old_type != $type )
				{					
					$total_products = usam_get_total_products( $args );					
					usam_create_system_process( sprintf(__("Обновление атрибутов товаров для &#8220;%s&#8221;","usam" ), $term->name), ['attribute_id' => $term_id, 'old_type' => $old_type, 'type' => $type], 'change_attribute_type', $total_products, "change_attribute_type_$term_id" );
					$calculate_product_filters = false;				
				}
				usam_update_term_metadata($term_id, 'field_type', $type );	
				if( usam_attribute_stores_values( $term_id ) )
				{ 				
					if ( !empty($_POST['delete_options_ids']) )
						$delete_options_ids = array_map('intval', $_POST['delete_options_ids']);
					else
						$delete_options_ids = [];
					if( isset($_POST['ready_options']) )
					{
						foreach( $_POST['ready_options']['code'] as $k => $code )
						{							
							if ( isset($_POST['ready_options']['id'][$k]) )
								$id = absint($_POST['ready_options']['id'][$k]);	
							if ( !empty($_POST['ready_options']['value'][$k]) )
							{			
								$new['attribute_id'] = $term_id;
								if( $type == 'N' )
									$new['value'] =  usam_string_to_float($_POST['ready_options']['value'][$k]);
								else
									$new['value'] = stripslashes($_POST['ready_options']['value'][$k]);
								if ( !empty($_POST['ready_options']['slug'][$k]) )
									$new['slug'] = sanitize_title($_POST['ready_options']['slug'][$k]);
								else
									$new['slug'] = sanitize_title($new['value']);
								$new['code'] = sanitize_text_field($code);								
								$new['sort'] = $k;	
								if ( $id )
									usam_update_product_attribute_variant( $id, $new);
								else
									usam_insert_product_attribute_variant( $new );
							}
							elseif ( $id )
								$delete_options_ids[] = $id;
						}					
					}
					if ( $delete_options_ids )
						$wpdb->query("DELETE FROM ".usam_get_table_db('product_attribute_options')." WHERE id IN (".implode(',',$delete_options_ids).")");					
				}				
				if( isset($_POST['do_not_show_in_features']) )
				{
					$do_not_show_in_features = (bool)$_POST['do_not_show_in_features'];
					usam_update_term_metadata($term_id, 'do_not_show_in_features', $do_not_show_in_features );	
				}					
				$old_search = usam_get_term_metadata($term_id, 'search');
				$search_value = empty($_POST['search']) ?0:(bool)$_POST['search'];				
				if( $old_search != $search_value )
				{					
					if ( $search_value )
					{
						if ( empty($total_products) )
							$total_products = usam_get_total_products( $args );	
						usam_create_system_process( sprintf(__("Изменение данных поиска для &#8220;%s&#8221;","usam" ), $term->name), array('slug' => $term->slug, 'old_search' => $old_search, 'search' => $search_value), 'change_attribute_search', $total_products, "change_attribute_search_$term_id" );
					}
					else
						$wpdb->query("DELETE FROM ".usam_get_table_db('posts_search')." WHERE meta_key='attribute_{$term_id}'");
					usam_update_term_metadata($term_id, 'search', $search_value );
				}							
				if( $calculate_product_filters )
				{						
					usam_calculate_product_filters( $term_id );		
				}	
				$admin_column = !empty($_POST['admin_column'])?1:0;			
				usam_update_term_metadata( $term_id, 'admin_column', $admin_column );	
				$switch_to_selection = !empty($_POST['switch_to_selection'])?1:0;			
				usam_update_term_metadata( $term_id, 'switch_to_selection', $switch_to_selection );					
				if ( $switch_to_selection )
				{
					global $wp_rewrite;
					$wp_rewrite->flush_rules();	
				}		
			}			
		}		
	}
}
?>