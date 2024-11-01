<?php
/* Этот файл используется для добавления полей в страницу редактирование таксоманий
 */
 
$category_class = new USAM_Taxonomy_Forms_Admin();
class USAM_Taxonomy_Forms_Admin
{
	protected $tree = 0;
	private $taxonomies = ["category", "usam-brands", "usam-category", "usam-category_sale", 'usam-variation', 'usam-product_attributes', 'usam-catalog', 'usam-selection', 'product_tag', 'usam-gallery'];
	function __construct( ) 
	{		
		if ( isset($_REQUEST['tag_ID']) )
		{			// Если категория		
			foreach ( $this->taxonomies as $taxonomy ) 
			{
			//	add_filter( 'manage_' . $taxonomy . '_custom_column', 'taxonomy_image_plugin_taxonomy_rows', 15, 3 );
			//	add_filter( 'manage_edit-' . $taxonomy . '_columns',  'taxonomy_image_plugin_taxonomy_columns' );
				add_action( $taxonomy.'_edit_form_fields', [$this, 'edit_forms'], 8, 2 );// форма редактирования	
				add_action( "saved_{$taxonomy}", [$this, 'saved_term_meta'], 100, 2);				
			}
			foreach ( ["usam-brands", "usam-category", "usam-category_sale", 'usam-catalog', 'usam-selection'] as $taxonomy ) 
			{
			//	add_filter( 'manage_' . $taxonomy . '_custom_column', 'taxonomy_image_plugin_taxonomy_rows', 15, 3 );
			//	add_filter( 'manage_edit-' . $taxonomy . '_columns',  'taxonomy_image_plugin_taxonomy_columns' );
				add_action( $taxonomy.'_edit_form_fields', [$this, 'edit_forms_product_sort'], 8, 2 );// форма редактирования	
			}
			$taxonomies = get_taxonomies();
			foreach ( $taxonomies as $taxonomy ) 
			{
				if ( !in_array($taxonomy, ['usam-variation', 'usam-product_attributes']) )
					add_action( $taxonomy.'_edit_form_fields', [$this, 'meta_tags'], 100, 2 );			
			}
		}		
		else
		{ // Если все категории
			add_filter( 'admin_footer', array($this, 'print_term_list_levels_script'), 1 );
			add_filter( 'term_name', array($this, 'term_list_levels'), 10, 2 );	
			add_filter( 'get_terms_defaults', array($this, 'sort_order_taxonomy'), 10, 2 );		
			add_filter( 'get_terms', array( $this, 'get_terms'), 10, 4);
										
			foreach ( $this->taxonomies as $taxonomy ) 
			{
				add_filter( 'manage_'.$taxonomy.'_custom_column', [$this, 'custom_column_data'], 10, 3);
				if ( $taxonomy == 'usam-product_attributes' )
					add_filter( 'manage_edit-'.$taxonomy.'_columns', [$this, 'custom_columns_status']);
				else
					add_filter( 'manage_edit-'.$taxonomy.'_columns', [$this, 'custom_columns']);			
			}			
			if ( isset($_REQUEST['taxonomy']) && in_array($_REQUEST['taxonomy'], ['usam-category', 'usam-product_attributes', 'usam-variation'] ) )
			{
				$user_id = get_current_user_id();
				$tree = get_the_author_meta('usam-category-tree', $user_id);
				$this->tree = isset($_GET['tree'])?absint($_GET['tree']):$tree;		
				if ( $this->tree )
				{ 
					$_REQUEST['orderby'] = !empty($_REQUEST['orderby'])?$_REQUEST['orderby']:'meta_value_num';			
				}
				if ( isset($_GET['tree']) && $tree != $this->tree )
					update_user_meta($user_id, 'usam-category-tree', $this->tree );
				
				add_action( 'after-usam-category-table', array( $this, 'after_table') );	
				add_action( 'after-usam-product_attributes-table', array( $this, 'after_table') );	
				add_action( 'after-usam-variation-table', array( $this, 'after_table') );	
				add_filter( 'get_terms_args',  array( $this, 'taxonomy_filter'), 10, 2 );	
			}			
		}
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}	
	
	function edit_forms_product_sort( $tag, $taxonomy)
	{	
		$sort_by = usam_get_term_metadata($tag->term_id, 'product_sort_by');		
		?>								
		<tr class="form-field">
			<th scope="row" valign="top"><?php esc_html_e( 'Сортировка товаров', 'usam'); ?></th>
			<td>		
				<select name='product_sort_by'>
					<option value='' <?php selected( '', $sort_by ); ?>><?php _e('По умолчанию', 'usam'); ?></option>
					<?php
					$sort = usam_get_product_sorting_options();
					$options = get_option( 'usam_sorting_options', ['name', 'price', 'popularity', 'date']);	
					foreach( $sort as $id => $name )
					{
						if( in_array($id, $options) )
						{
							?><option value='<?php echo $id; ?>' <?php selected( $id, $sort_by ); ?>><?php echo $name; ?></option><?php					
						}
					} ?>
				</select>				
			</td>			
		</tr>			
		<?php
	}	
	
	public function admin_enqueue_scripts() 
	{		
		wp_enqueue_media();
	}	
	
	function after_table()
	{		
		?>
		<label id ="select-tree"><input type='checkbox' name='tree' <?php checked( $this->tree, 1); ?> value='1'/><?php _e('Скрыть уровни','usam') ?></label>
		<script>		
			jQuery(document).ready(function()
			{ 				
				jQuery('.bulkactions').append( jQuery('#select-tree') );
				jQuery("body").delegate('#select-tree input', 'change', function()
				{
					if( jQuery(this).prop('checked') )
						window.location.replace(location.href +'&tree=1');	
					else
						window.location.replace(location.href +'&tree=0');
				});
				jQuery("body").delegate('.wp-list-table .row-title', 'click', function(e)
				{
					if( jQuery("#select-tree input").prop('checked') )
					{
						e.preventDefault();
						var attrs = usam_get_url_attrs(jQuery(this).attr('href'));						
						window.location.replace(location.href +'&tree=1&parent='+attrs['tag_ID']);						
					}
				});
			});
		</script>
		<?php
	}
	
	function taxonomy_filter( $args, $taxonomies ) 
	{ 	
		global $pagenow;	
		if ( 'edit-tags.php' !== $pagenow ) 
			return $args;
		
		if ( $this->tree )
			$args['parent'] = !empty($_GET['parent'])?sanitize_text_field($_GET['parent']):0;	
		if ( empty($_GET['orderby']) )
			$args['orderby'] = 'sort';
		$args['term_meta_cache'] = true;		
		return $args;
	}
	
	function get_terms( $terms, $taxonomy, $query_vars, $term_query  ) 
	{
		$thumb_ids = array();	
		foreach( $terms as $term ) 
		{ 
			if ( !empty($term->term_id) )
			{
				$attachment_id = (int)get_term_meta($term->term_id, 'thumbnail', true);
				if ( $attachment_id )
					$thumb_ids[] = $attachment_id;
			}
		}				
		if ( !empty($thumb_ids) )
			_prime_post_caches( $thumb_ids, false, true );
		return $terms;
	}
	
	/**
	 * Добавляет столбец изображения в категории колонке.
	 */
	function custom_columns( $columns ) 
	{						
		$custom_array = ['cb' => '<input type="checkbox" />', 'image' => ''];
		$columns = array_merge( $custom_array, $columns );
		$columns['status'] = __('Статус', 'usam');	
		return $columns;
	}
	
	function custom_columns_status( $columns ) 
	{						
		$custom_array = ['cb' => '<input type="checkbox" />'];
		$columns = array_merge( $custom_array, $columns );
		$columns['status'] = __('Статус', 'usam');	
		return $columns;
	}
	
	/*
	 * Добавляет изображения в колонке на странице категорий
	 */
	function custom_column_data( $string, $column_name, $taxonomy_id )
	{	
		$html = '';
		switch ( $column_name ) 
		{
			case 'image':	
				$image = usam_get_term_image_url( $taxonomy_id, 'manage-products' );
				$attachment_id = (int)get_term_meta($taxonomy_id, 'thumbnail', true);
				$html = "<img class='taxonomy_thumbnail' data-attachment_id='0' data-id='$taxonomy_id' src='$image' width='50' height='50' />";
			break;
			case 'status':				
				$status = usam_get_term_metadata($taxonomy_id, 'status');	
				if( $status == 'publish' || $status == 'hidden' )
				{
					echo '<span class="item_status_valid item_status js-term-status js-term-status_publish '.($status == 'hidden'?'hide':'').'" data-status="publish">'.usam_get_term_status_name( 'publish' ).'</span>'; 
					echo '<span class="status_blocked item_status js-term-status js-term-status_hidden '.($status == 'publish'?'hide':'').'" data-status="hidden">'.usam_get_term_status_name( 'hidden' ).'</span>'; 
				}
				else
					echo usam_get_term_status_name( $status );
			break;	
		}		
		echo $html;
	}
	
	function sort_order_taxonomy( $query_var_defaults, $taxonomies ) 
	{		
		if ( (empty($_REQUEST['orderby']) || $_REQUEST['orderby'] == 'meta_value_num' && empty($query_var_defaults['meta_key'])) && !empty($taxonomies) )
		{
			foreach ( $this->taxonomies as $taxonomy ) 
			{
				if ( in_array($taxonomy, $taxonomies) )
				{ 
					$query_var_defaults['orderby'] = 'sort';
					$query_var_defaults['order'] = 'ASC';
					break;
				}
			}
		}
		return $query_var_defaults;
	}	
	
	function term_list_levels( $term_name, $term ) 
	{
		global $wp_list_table, $usam_term_list_levels;		
		
		if ( !isset($term->term_id) || !isset($wp_list_table->level) )
			return $term_name;
		if ( !isset($usam_term_list_levels ) )
			$usam_term_list_levels = [];
		$usam_term_list_levels[$term->term_id] = $wp_list_table->level;
		return $term_name;
	}
	/**
	При выполнении изменения продукта и перетаскивании категории, мы хотим ограничить перетаскивание тем же уровнем (дети категория не могут быть удалены). Чтобы сделать это, мы должны быть определить уровень глубины термина. Мы можем сделать это с WP хуками. Эта функция добавляется в меню "term_name" фильтр. Его задача заключается в записи глубины уровня каждого терминов в глобальной переменной. Эта глобальная переменная позже будет вывод на JS 
	 */
	function print_term_list_levels_script()
	{		
		global $usam_term_list_levels;		
		?>
		<script>
		//<![CDATA[
		var USAM_Term_List_Levels = <?php echo json_encode( $usam_term_list_levels ); ?>;
		//]]>
		</script>
		<?php
		USAM_Admin_Assets::set_thumbnails();
	}	
	
	function meta_tags( $term, $taxonomy )
	{		
		if ( current_user_can('view_seo') )
		{
			wp_enqueue_script( 'usam-term' );
			?>						
			<tr id="term-meta-tags" class="form-field">
				<td scope="row" valign="top" colspan='2'>
					<h2><?php printf(__('SEO мета-теги %s', 'usam'), $term->name); ?></h2>
					<meta-seo :data="data.meta" inline-template>
						<?php include( usam_get_filepath_admin('templates/template-parts/post-meta.php') ); ?>
					</meta-seo>
					<?php
					if ( $taxonomy == 'usam-category' || $taxonomy == 'usam-category_sale' || $taxonomy == 'usam-catalog' || $taxonomy == 'usam-brands')
					{
						?>
						<h2><?php printf(__('Мета-теги фильтров в %s', 'usam'), $term->name); ?></h2>
						<meta-seo :data="data.meta_filter" :name="'meta_filter'" inline-template>
							<?php include( usam_get_filepath_admin('templates/template-parts/post-meta.php') ); ?>
						</meta-seo>
						<?php
					}
					if ( $taxonomy == 'usam-category' || $taxonomy == 'usam-category_sale' || $taxonomy == 'category' )
					{
						?>
						<h2><?php printf(__('Мета-теги записей в %s', 'usam'), $term->name); ?></h2>
						<meta-seo :data="data.postmeta" :name="'postmeta'" inline-template>
							<?php include( usam_get_filepath_admin('templates/template-parts/post-meta.php') ); ?>
						</meta-seo>
						<?php
					}
					?>
				</td>			
			</tr>
			<?php
		}
	}	
	
	function edit_forms( $term, $taxonomy ) 
	{		
		$attachment_id = (int)get_term_meta($term->term_id, 'thumbnail', true);			
		$sort = usam_get_term_metadata($term->term_id, 'sort');			
		$status = usam_get_term_metadata($term->term_id, 'status');		
		$status = $status?$status:'hidden';		
	
		$image = usam_get_term_image_url( $term->term_id );
		$hide = !$attachment_id?'hide':'';
		
		add_action('admin_footer', function() { 				
			wp_enqueue_media();	
		});	
		if ( !is_plugin_active('wordpress-seo/wp-seo.php') )
		{
			?>		
			<tr id="new_term_description" class="form-field">
				<th scope="row" valign="top">
					<label><?php esc_html_e( 'Описание', 'usam'); ?></label>				
				</th>
				<td>				
					<?php wp_editor( htmlspecialchars_decode($term->description), 'description' ); ?>
				</td>
			</tr>  
		<?php } ?>
		<tr id="term_images" class="form-field">
			<th scope="row" valign="top">
				<label for="image"><?php esc_html_e( 'Изображения', 'usam'); ?></label>
			</th>
			<td>				
				<input type="hidden" v-model="data.representative_image" name="presentation">
				<div class="photo_gallery">
					<div class="image" v-for="(image, i) in data.images" draggable="true" @dragover="allowDrop($event, i)" @dragstart="drag($event, i)" @dragend="dragEnd($event, i)" @dblclick="data.representative_image=image.ID">
						<div class="image_container"><img loading='lazy' :src="image.full"></div>
						<a href="#" class="delete dashicons" @click="deleteMedia(i)"><?php _e('Удалить', 'usam'); ?></a>
						<input type="hidden" v-model="image.ID" name="image_gallery[]">						
						<span class="dashicons dashicons-star-filled" v-if="data.representative_image==image.ID"></span>
					</div>
				</div>
				<wp-media inline-template @change="addMedia" :title="'<?php esc_attr_e( 'Добавить изображение в галерею', 'usam'); ?>'" :multiple="true">						
					<a @click="addMedia"><?php _e( 'Добавить изображение', 'usam'); ?></a>
				</wp-media>				
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php esc_html_e( 'Статус', 'usam'); ?></th>
			<td>					
				<?php $statuses = usam_get_statuses_terms(); ?>
				<div class="usam_radio statuses_terms">	
					<?php foreach ( $statuses as $key => $name ) { ?>
						<div class="usam_radio__item usam_radio-<?php echo $key; ?> <?php echo $status == $key?'checked':''; ?>">
							<div class="usam_radio_enable">
								<input type="radio" name="status" class="input-radio" value="<?php echo $key; ?>" <?php checked($status, $key); ?>/>
								<label><?php echo $name; ?></label>
							</div>										
						</div>
					<?php } ?>						
				</div>
			</td>			
		</tr>
		<?php 		
		if ( usam_check_current_user_role( 'administrator' ) )
		{
			$external_code = usam_get_term_metadata($term->term_id, 'external_code');
			?>	
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="external_code"><?php esc_html_e( 'Внешний код', 'usam'); ?></label>				
				</th>
				<td><input type='text' name='external_code' id = "external_code" value='<?php echo $external_code; ?>' /></td>
			</tr> 
			<?php 
		}	
		?>			
		<tr class="form-field">
			<th scope="row" valign="top"><label for="sort_order"><?php esc_html_e( 'Сортировка', 'usam'); ?></label></th>
			<td><input type='text' name='sort' id = "sort_order" value='<?php echo $sort; ?>' /></td>
		</tr> 
		<?php		
	}	
	
	function saved_term_meta($term_id, $tt_id)
	{
		if( isset($_POST['sort']) )
		{
			$sort = (int)$_POST['sort'];
			usam_update_term_metadata($term_id, 'sort', $sort );	
		}
		if( isset($_POST['status']) )
		{
			$status = sanitize_title($_POST['status']);
			usam_update_term_metadata($term_id, 'status', $status );	
		}
		if ( isset($_POST['meta']) )
		{
			foreach( $_POST['meta'] as $key => $value ) 
				update_term_meta($term_id, 'meta_'.$key, sanitize_textarea_field(stripslashes($value)));
		}
		if ( isset($_POST['postmeta']) )
		{
			foreach( $_POST['postmeta'] as $key => $value ) 
				update_term_meta($term_id, 'postmeta_'.$key, sanitize_textarea_field(stripslashes($value)));
		}
		if ( isset($_POST['meta_filter']) )
		{ 
			foreach( $_POST['meta_filter'] as $key => $value ) 
				update_term_meta($term_id, 'meta_filter_'.$key, sanitize_textarea_field(stripslashes($value)));
		}			
		if ( !empty($_POST['product_sort_by']) )
		{
			$product_sort_by = sanitize_title($_POST['product_sort_by']);
			usam_update_term_metadata( $term_id, 'product_sort_by', $product_sort_by );
		}		
		if ( usam_check_current_user_role( 'administrator' ) )
		{
			$external_code = !empty($_POST['external_code'])?sanitize_title($_POST['external_code']):'';
			usam_update_term_metadata( $term_id, 'external_code', $external_code );	
		}
		$images = !empty($_POST['image_gallery'])?array_map('intval', (array)$_POST['image_gallery']):[];
		update_term_meta( $term_id, 'images', $images );
						
		$thumbnail_id = !empty($images[0])?$images[0]:0;
		update_term_meta( $term_id, 'thumbnail', $thumbnail_id );	

		$presentation = !empty($_POST['presentation'])?(int)$_POST['presentation']:0;
		update_term_meta( $term_id, 'representative_image', $presentation );		
	}
}
?>