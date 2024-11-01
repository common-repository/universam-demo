<?php
/**
 * Класс виджета "Группы товаров"
 */ 
class USAM_Widget_Product_Groups extends WP_Widget 
{
	function __construct() 
	{
		$widget_ops = array(
			'classname' => 'widget_usam_product_groups',
			'description' => __('Универсам: Виджет групп товаров', 'usam')
		);
		parent::__construct( 'usam_product_groups', __('Группы товаров', 'usam'), $widget_ops );
	}

	function widget( $args, $instance )
	{
		global $usam_query, $wp_query;
		
		$instance = wp_parse_args((array) $instance, $this->get_default_option());
		extract( $args );	
								
		if ( $instance['category'] )
		{			
			$display_category = true;
			if ( $instance['levels'] === 'active' )
				$class_ul = 'show_active_category';
			elseif ( $instance['levels'] === 'first' )
				$class_ul = 'show_first_active_category';
			else
				$class_ul = 'show_all_category';
			$args_category_list = ['taxonomy' => 'usam-category', 'show_active' => $instance['levels']!=='all', 'take_menu' => $instance['child_of'], 'term_count' => $instance['term_count'], 'active_term' => !empty($wp_query->query_vars['usam-category'])?$wp_query->query_vars['usam-category']:'', 'class_ul' => 'usam_categories_list '.$class_ul];			
			$child_of = $instance['child_category'];
			if( $instance['level'] != 'all' )
			{						
				if( !empty($wp_query->query_vars['usam-category']) )
				{
					if ( $instance['level'] == 'lower' )
					{
						$term = get_term_by('slug', $wp_query->query_vars['usam-category'], 'usam-category');
						$category_children = get_option('usam-category_children', []);	
						if( !empty($category_children[$term->term_id]) )
							$child_of = $term->term_id;
						else
							$display_category = false;					
					}
				}
				else
					$display_category = false;
			}
			if ( $display_category )
			{ 	
				$args_category = ['child_of' => $child_of, 'update_term_meta_cache' => 0, 'usam_meta_query' => []];
				if ( isset($wp_query->query['usam-brands']) || isset($wp_query->query['usam-category_sale']) || isset($wp_query->query['usam-selection']) || ( isset($wp_query->query['pagename']) && (in_array($wp_query->query['pagename'], usam_get_product_pages()) ) ) )
				{	
					$new_query = $usam_query->query_vars;			
					unset($new_query['usam-category']);			
					unset($new_query['taxonomy']);	
					unset($new_query['term']);	
				
					$new_query['nopaging'] = true;
					$new_query['fields'] = 'ids';
					$new_query['cache_results'] = false;
					$new_query['update_post_meta_cache'] = false;
					$new_query['update_post_term_cache'] = false;			
					$new_query['post_type'] = 'usam-product';	
					if ( !empty($new_query['tax_query']) )
						unset($new_query['tax_query']);
				
					$query = new WP_Query( $new_query );
					$object_terms = wp_get_object_terms( $query->posts, 'usam-category', ['orderby' => 'name', 'fields' => 'ids']);		
					
					$terms_ids = [];
					foreach ( $object_terms as $term_id ) 
					{			
						$terms_ids[] = $term_id;
						$terms_ancestors = usam_get_ancestors( $term_id, 'usam-category' );
						$terms_ids = array_merge( $terms_ids, $terms_ancestors );	
					}								
					$args_category['include'] = !empty($terms_ids)?$terms_ids:-1;
				}				
				$args_category['orderby'] = 'sort';
			}
		}
		else
			$display_category = false;
		$args_brands_list = ['taxonomy' => 'usam-brands', 'class_ul' => 'usam_categories_list'];
		if ( !empty($usam_query->query['usam-brands']))
		{
			$term = get_term_by('slug', $wp_query->query_vars['usam-brands'], 'usam-brands');
			if ( !empty($term->term_id) )
				$args_brands_list['select'] = [ $term->term_id ];
		}					
		$args_brands = ['update_term_meta_cache' => 0, 'orderby' => 'name'];
		$catalog = usam_get_active_catalog();		
		if ( $catalog )
		{
			if ( $display_category )
				$args_category['usam_meta_query'][] = ['key' => 'catalog', 'value' => $catalog->term_id, 'compare' => '='];	
			if ( $instance['brands'] )
				$args_brands['usam_meta_query'][] = ['key' => 'catalog', 'value' => $catalog->term_id, 'compare' => '='];	
		} 
		$sold = get_option( 'usam_display_sold_products', 'sort');				
		if ( $sold != 'show' )
		{
			if ( $sold == 'hide' )
				$in_stock = true;
			else
				$in_stock = false;			
			$args_category['in_stock'] = $in_stock;
			$args_brands['in_stock'] = $in_stock;
		}	
		if ( $instance['brands'] && $instance['brands'] )
		{
			if ( $display_category )
			{
				echo $before_widget;		
				?>
				<div id='product_groups' class = "usam_tabs">
					<div class='header_tab'>				
						<a href="#tab_categories" class ="tab current"><h3><?php _e( 'Категории', 'usam'); ?></h3></a>
						<a href="#tab_brands" class="tab"><h3><?php _e( 'Бренды', 'usam'); ?></h3></a>
					</div>
					<div class='countent_tabs'>
						<div id="tab_categories" class="tab current">
							<?php 
							$html = usam_get_walker_terms_list($args_category, $args_category_list); // Не менять положение
							if ( isset($usam_query->query['usam-brands']) )
							{
								remove_filter( 'term_link', ['USAM_Taxonomy_Filter', 'term_link'], 10 );
								global $usam_query;			
								$url = '';										
								if ( !empty( $usam_query->query_vars['usam-category'] ) )	
									$url = get_term_link( $usam_query->query_vars['usam-category'], 'usam-category' );
								else
								{
									$all_categories = get_terms(['status' => 'publish', 'hide_empty' => 0, 'orderby' => 'id', 'taxonomy' => 'usam-category', 'number' => 1]);
									if ( !empty( $all_categories ))
										$url = get_term_link( (int)$all_categories[0]->term_id, 'usam-category' );
								}
								if ( !is_string($url) )
									$url = usam_get_system_page_id('products-list');
								echo '<div class="title_categorisation_brand">'.
									__('Категории бренда','usam').': '.$usam_query->query['usam-brands'].'
									<a class = "delete" href="'.$url.'" title="'. __('Показать все категории', 'usam').'"></a>
								</div>';
							}							
							echo $html;
							?>
						</div>
						<div id="tab_brands" class="tab">
							<?php echo usam_get_walker_terms_list( $args_brands, $args_brands_list ) ?>
						</div>
					</div>
				</div>	
				<?php
				echo $after_widget;
			}
		}
		elseif ( $instance['category'] )
		{
			if ( $display_category )
				echo $before_widget.usam_get_walker_terms_list( $args_category, $args_category_list ).$after_widget;
		}
		elseif ( $instance['brands'] )
			echo $before_widget.usam_get_walker_terms_list( $args_brands, $args_brands_list ).$after_widget;
	}

	function update( $new_instance, $old_instance ) 
	{
		$instance = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['levels']      = sanitize_title($new_instance['levels']);	
		$instance['brands']      = $new_instance['brands'] ? 1 : 0;	
		$instance['category']    = $new_instance['category'] ? 1 : 0;	
		$instance['term_count']      = $new_instance['term_count'] ? 1 : 0;	
		$instance['child_of']      = $new_instance['child_of'] ? 1 : 0;	
		$instance['child_category'] = sanitize_title($new_instance['child_category']);	
		$instance['level'] = sanitize_title($new_instance['level']);	
		return $instance;
	}
	
	protected function get_default_option( ) 
	{
		return ['title' => '', 'child_of' => false, 'child_category' => 0, 'brands' => 1,	'term_count' => 0, 'category' => 1,	'level' => 'all', 'levels' => 'active'];
	}

	function form( $instance ) 
	{				
		require_once( USAM_FILE_PATH . '/includes/taxonomy/class-walker-category-select.php' );
		$instance = wp_parse_args((array) $instance, $this->get_default_option());
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Название:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo htmlspecialchars( esc_attr($instance['title']) ); ?>" />
		</p>
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>"<?php checked( $instance['category'] ); ?>/>
			<label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Показывать категории', 'usam'); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('child_category'); ?>"><?php _e( 'Категории для отображения:', 'usam'); ?></label>
			<select id="<?php echo $this->get_field_id( 'child_category' ); ?>" name='<?php echo $this->get_field_name( 'child_category' ); ?>' class='term_manager_product chzn-select' data-placeholder='<?php _e( 'Выберите', 'usam'); ?>' style="width: 100%;">		
				<option value=''><?php _e( 'Показать все категории', 'usam'); ?></option>
				<?php
				$args = array(
					'descendants_and_self' => 0,
					'selected_cats'        => array( $instance['child_category'] ),
					'walker'               => new Walker_Category_Select(),
					'taxonomy'             => 'usam-category',
					'list_only'            => false,		
					'checked_ontop'        => false, 
					'echo'                 => true,
				);
				usam_terms_checklist( $args );	
				?>		
			</select>
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id('levels'); ?>"><?php _e('Отображение', 'usam'); ?></label>
			<select id="<?php echo $this->get_field_id( 'levels' ); ?>" name='<?php echo $this->get_field_name( 'levels' ); ?>'>						
				<option value='active'><?php _e( 'Раскрыть все уровни активной категории', 'usam'); ?></option>
				<option value='first'><?php _e( 'Раскрыть только уровни выбранной категории', 'usam'); ?></option>		
				<option value='all'><?php _e( 'Раскрыть все уровни', 'usam'); ?></option>				
			</select>
		</p>
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('term_count'); ?>" name="<?php echo $this->get_field_name('term_count'); ?>"<?php checked( $instance['term_count'] ); ?>/>
			<label for="<?php echo $this->get_field_id('term_count'); ?>"><?php _e('Показывать количество товаров', 'usam'); ?></label>
		</p>
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('child_of'); ?>" name="<?php echo $this->get_field_name('child_of'); ?>"<?php checked( $instance['child_of'] ); ?>/>
			<label for="<?php echo $this->get_field_id('child_of'); ?>"><?php _e('Показывать с учетом выбранной категории в меню', 'usam'); ?></label>
		</p>
		<p>		
			<label for="<?php echo $this->get_field_id('level'); ?>"><?php _e( 'Показывать уровень:', 'usam'); ?></label>
			<select id="<?php echo $this->get_field_id( 'level' ); ?>" name='<?php echo $this->get_field_name( 'level' ); ?>'>		
				<option value='all'><?php _e('Все категории', 'usam'); ?></option>
				<option value='lower'><?php _e('Нижний уровень', 'usam'); ?></option>				
			</select>			
		</p>
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('brands'); ?>" name="<?php echo $this->get_field_name('brands'); ?>"<?php checked( $instance['brands'] ); ?>/>
			<label for="<?php echo $this->get_field_id('brands'); ?>"><?php _e('Показывать бренды', 'usam'); ?></label>
		</p>
<?php
	}
}