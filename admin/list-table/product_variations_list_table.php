<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_product_variations extends USAM_Product_List_Table
{
	private $product_id;
	private $object_terms_cache = array();
	private $args = array();
	private $is_trash             = false;
	private $is_draft             = false;
	private $is_pending           = false; // на утверждении
	private $is_publish           = false;
	private $is_all               = true;
	private $is_bulk_edit         = false;
	public $post_status = 'publish, inherit, draft';
	protected $filter_box = false;	
	
	public function __construct( $args = [] ) 
	{
		$this->product_id = absint($_REQUEST['product_id']);
		if ( isset($_REQUEST['post_status'] ) ) 
		{
			$this->is_trash = $_REQUEST['post_status'] == 'trash';
			$this->is_draft = $_REQUEST['post_status'] == 'draft';
			$this->is_pending = $_REQUEST['post_status'] == 'pending';
			$this->is_publish = $_REQUEST['post_status'] == 'publish';
			$this->is_all = $_REQUEST['post_status'] == 'all';
			if ( in_array($_REQUEST['post_status'], array( 'trash', 'draft', 'pending', 'publish' ) ) )
				$this->post_status = $_REQUEST['post_status'];			
		} 
		else 			
			$this->is_all = true;	
		
		parent::__construct(['singular' => 'product_variations', 'plural' => 'product_variations', 'screen' => 'product_variations']);
	}

	public function prepare_items() 
	{
		global $wp_query;	
		
		if ( !empty($this->items) )
			return;
		
		$per_page = $this->get_items_per_page( 'edit_usam-product-variations_per_page' );
		$per_page = apply_filters( 'edit_usam_product_variations_per_page', $per_page );

		$this->args = [
			'post_type'      => 'usam-product',
			'orderby'        => 'menu_order post_title',
			'order'          => "ASC",
			'post_parent'    => $this->product_id,
			'post_status'    => $this->post_status,
			'numberposts'    => -1,
			'order'          => "ASC",
			'posts_per_page' => $per_page,
			'prices_cache' => true,
			'stocks_cache' => true,
			'update_post_meta_cache' => true,
			'product_meta_cache' => true,
			'update_post_term_cache' => true,
		];
		if ( isset($_REQUEST['s'] ) )
			$this->args['s'] =  esc_sql( $wpdb->esc_like( trim($_REQUEST['s'])));

		if ( isset($_REQUEST['paged'] ) )
			$this->args['paged'] = $_REQUEST['paged'];

		$wp_query = new WP_Query( $this->args );
		$this->items = $wp_query->posts;	
		
		update_post_thumbnail_cache( $wp_query );

		$this->total_items = $wp_query->found_posts;
		$total_pages = $wp_query->max_num_pages;

		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'total_pages' => $total_pages, 'per_page' => $per_page ) );

		if ( empty($this->items) )
			return;

		$ids = wp_list_pluck( $this->items, 'ID' );
		$object_terms = wp_get_object_terms( $ids, 'usam-variation', ['fields' => 'all_with_object_id']);
		foreach ( $object_terms as $term ) 
		{
			if ( ! array_key_exists( $term->object_id, $this->object_terms_cache ) )
				$this->object_terms_cache[$term->object_id] = array();
			$this->object_terms_cache[$term->object_id][$term->parent] = $term->name;
		}
	}

	public function get_hidden_columns() 
	{
		return array();
	}

	public function get_columns() 
	{
		$columns = array(
			'cb'        => '<input type="checkbox">',		
			'title'     => __('Название', 'usam'),
			'sku'       => __('Артикул', 'usam'),				
			'stock'     => __('Запас', 'usam'),		
			'reserve'   => __('Резерв', 'usam'),				
			'price'     => __('Цена', 'usam'),	
			'author'    => __('Автор', 'usam'),
			'date'      => __('Дата', 'usam'),		
		);		
		return apply_filters( 'usam_variation_column_headers', $columns );
	}

	public function get_sortable_columns() {
		return array();
	}
	
// Действия в таблицы вариаций
	private function get_row_actions( $item ) 
	{
		$post_type_object = get_post_type_object( 'usam-product' );
		$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $item->ID );

		$actions = array();
		if ( apply_filters( 'usam_show_product_variations_edit_action', true, $item ) && $can_edit_post && 'trash' != $item->post_status )
			$actions['edit'] = '<a target="_blank" href="'.get_edit_post_link( $item->ID, true ).'" title="'.esc_attr( __('Редактировать вариацию' ), 'usam').'">'.__('Изменить' ).'</a>';
		$actions['price hide-if-no-js'] = '<a class="usam-variation-editor-link" data-key="price" href="#" title="'.__('Показать редактор цен', 'usam').'">'.__('Цены', 'usam').'</a>';		
		$actions['stock hide-if-no-js'] = '<a class="usam-variation-editor-link" data-key="stock" href="#" title="'.__('Показать редактор запасов', 'usam').'">'.__('Запас', 'usam').'</a>';		
		$actions['vendor hide-if-no-js'] = '<a class="usam-variation-editor-link" data-key="vendor" href="#" title="'.__('Показать редактор поставщика товара', 'usam').'">'.__('Поставщик', 'usam').'</a>';
		$actions['dimensions hide-if-no-js'] = '<a class="usam-variation-editor-link" data-key="dimensions" href="#" title="'.__('Показать редактор размера товара', 'usam').'">'.__('Размеры', 'usam').'</a>';		
		if ( $item->post_status == 'draft' ) 
			$js_actions['show'] = __('Публиковать', 'usam');
		elseif ( in_array($item->post_status, ['publish', 'inherit']) ) 
			$js_actions['draft'] = __('Черновик', 'usam');			
		if ( current_user_can( $post_type_object->cap->delete_post, $item->ID ) )
		{
			$force_delete = 'trash' == $item->post_status || ! EMPTY_TRASH_DAYS;		
			if ( 'trash' == $item->post_status ) 
				$js_actions['untrash'] = __('Восстановить' ,'usam');
			elseif ( EMPTY_TRASH_DAYS )
				$js_actions['trash'] = __('В корзину' ,'usam');
			if ( $force_delete )
				$js_actions['delete'] = __('Удалить' ,'usam');
		}		
		foreach( $js_actions as $action => $title )
		{
			$actions[$action] = "<a class='js-table-action-link' data-action='{$action}' href='#'>{$title}</a>";			
		}
		return $actions;
	}

	public function column_title( $item ) 
	{
		if( isset($this->object_terms_cache[$item->ID]) )
			$title = implode( ', ', $this->object_terms_cache[$item->ID] ).($item->post_status == 'draft'?' ('.__('Черновик', 'usam').')':'');
		else
			$title = '';
		$thumbnail_id = get_post_thumbnail_id( $item->ID );
		if ( empty($thumbnail_id) )
			$thumbnail_id = get_post_thumbnail_id($this->product_id);		
		?>
		<div class="usam-product-variation-thumbnail">
			<a id ="set-product-thumbnail" data-attachment_id="<?php echo $thumbnail_id; ?>" data-title="<?php echo esc_attr( $title ); ?>" data-product_title="<?php echo esc_attr( $item->post_title ); ?>" data-post_id="<?php echo $item->ID; ?>" href="<?php echo esc_url( admin_url( 'media-upload.php?post_id='.$item->ID. '&TB_iframe=1&width=640&height=566' ) ) ?>">
				<?php echo usam_get_product_thumbnail( $item->ID, 'manage-products' ); ?>
			</a>
		</div>
		<?php	
		$show_edit_link = apply_filters( 'usam_show_product_variations_edit_action', true, $item );		
		?>	
		<strong class="row-title">
			<?php if ( $show_edit_link ): ?>
				<a target="_blank" href="<?php echo esc_url( get_edit_post_link( $item->ID, true ) ); ?>" title="<?php esc_attr_e( __('Изменить элемент' ), 'usam'); ?>">
			<?php endif; ?>
			<?php echo esc_html( $title ); ?>
			<?php if ( $show_edit_link ): ?>
				</a>
			<?php endif; ?>
		</strong>
		<?php 
		echo $this->row_actions( $this->get_row_actions( $item ) ); 
	}
		
	public function column_sku( $item )
	{		
		?><input type="text" name="productmeta[<?php echo $item->ID; ?>][sku]" value="<?php echo esc_attr( usam_get_product_meta( $item->ID, 'sku' ) ); ?>"><?php
	}
	
	private function vendor_editor( $item = false ) 
	{
		static $alternate = '';

		if ( ! $item )
			$alternate = '';
		else
			$alternate = ( $alternate == '' ) ? ' alternate' : '';
		$style = '';
		if ( !$item )
		{
			if ( $this->is_bulk_edit )
				$style = ' style="display:table-row;"';
			else
				$style = ' style="display:none;"';			
		} 	
		$colspan = count( $this->get_columns() );
		?>
		<tr class="usam-vendor-editor-row inline-edit-row<?php echo $alternate; ?> usam-editor-row"<?php echo $style; ?> id="usam-vendor-editor-row-<?php echo $item->ID; ?>">
			<td colspan="<?php echo $colspan; ?>" class="colspanchange">
				<?php
				//	$_product_meta = new USAM_Product_Meta_Box( $item->ID ); 
				//	$_product_meta->product_link_webspy( false );
				?>	
			</td>
		</tr>
		<?php
	}	
	
	function dimensions_control( $item )
	{
		$dimension_units = usam_get_dimension_units();	
		$dimension_unit = get_option('usam_dimension_unit'); 		
		$measurement_fields = array(
			array(
				'name'   => 'weight',
				'label'  => __('Вес', 'usam'),
				'units'  => usam_get_name_weight_units(),
			),			
			array(
				'name'   => 'length',
				'label'  => __('Длина', 'usam'),
				'units'  => $dimension_units[$dimension_unit]['short'],
			),
			array(
				'name'   => 'width',
				'label'  => __('Ширина', 'usam'),
				'units'  => $dimension_units[$dimension_unit]['short'],
			),		
			array(
				'name'   => 'height',
				'label'  => __('Высота', 'usam'),
				'units'  => $dimension_units[$dimension_unit]['short'],
			),
			array(
				'name'   => 'volume',
				'label'  => __('Объем', 'usam'),
				'units'  => $dimension_units[$dimension_unit]['short'].'<sup>3</sup>',
			),
		);			
		?>	
		<strong><?php _e( 'Вес и размеры коробки', 'usam'); ?>:</strong>
		<table class = "measurement_fields">
			<?php
			foreach ( $measurement_fields as $field ):
				$value = usam_exp_to_dec(usam_string_to_float( usam_get_product_meta($item->ID, $field['name']) ));
				?>
				<tr>
					<td><label for="usam-product-shipping-<?php echo $field['name']; ?>"><?php echo esc_html( $field['label'] ); ?></label></td>
					<td><input type="text" id="usam-product-shipping-<?php echo $field['name']; ?>" name="<?php echo "productmeta[{$item->ID}][".$field['name']."]"; ?>" value="<?php echo $value?$value:''; ?>" placeholder='0'/></td>
					<td><?php echo $field['units']; ?></td>
				</tr>			
			<?php
			endforeach;
			?>
		</table>
		<?php				
	}
	
	private function dimensions_editor( $item = false ) 
	{
		static $alternate = '';		

		if ( ! $item )
			$alternate = '';
		else
			$alternate = ( $alternate == '' ) ? ' alternate' : '';
		$style = '';
		if ( ! $item )
		{		
			if ( $this->is_bulk_edit )
				$style = ' style="display:table-row;"';
			else
				$style = ' style="display:none;"';			
		} 	
		$colspan = count( $this->get_columns() );		
		?>
		<tr class="usam-dimensions-editor-row inline-edit-row<?php echo $alternate; ?> usam-editor-row"<?php echo $style; ?> id="usam-dimensions-editor-row-<?php echo $item->ID; ?>">
			<td colspan="<?php echo $colspan; ?>" class="colspanchange">			
				<?php $this->dimensions_control( $item ); ?>	
			</td>
		</tr>
		<?php
	}	
	
	private function stock_editor( $item = false ) 
	{
		static $alternate = '';		

		if ( ! $item )
			$alternate = '';
		else
			$alternate = ( $alternate == '' ) ? ' alternate' : '';
		$style = '';
		if ( ! $item )
		{			
			if ( $this->is_bulk_edit )
				$style = ' style="display:table-row;"';
			else
				$style = ' style="display:none;"';			
		} 	
		$colspan = count( $this->get_columns() );		
		?>
		<tr class="usam-stock-editor-row inline-edit-row<?php echo $alternate; ?> usam-editor-row"<?php echo $style; ?> id="usam-stock-editor-row-<?php echo $item->ID; ?>">
			<td colspan="<?php echo $colspan; ?>" class="colspanchange">
				<h4><?php esc_html_e( 'Управление запасом', 'usam'); ?></h4>
				<?php
					$_product_meta = new USAM_Product_Meta_Box($item->ID); 
					$_product_meta->stock_control();
				?>			
			</td>
		</tr>
		<?php
	}	
	
	private function price_editor( $item = false ) 
	{
		static $alternate = '';	

		if ( ! $item )
			$alternate = '';
		else
			$alternate = ( $alternate == '' ) ? ' alternate' : '';
		$style = '';
		if ( ! $item )
		{		
			if ( $this->is_bulk_edit )
				$style = ' style="display:table-row;"';
			else
				$style = ' style="display:none;"';			
		} 			
		$colspan = count( $this->get_columns() );		
		?>
		<tr class="usam-price-editor-row inline-edit-row<?php echo $alternate; ?> usam-editor-row"<?php echo $style; ?> id="usam-price-editor-row-<?php echo $item->ID; ?>">
			<td colspan="<?php echo $colspan; ?>" class="colspanchange">
				<div id = "usam_prices_forms">
					<?php
					$_product_meta = new USAM_Product_Meta_Box( $item->ID ); 
					$_product_meta->price_control();
					?>
				</div>
			</td>
		</tr>
		<?php
	}	

	public function single_row( $item ) 
	{		
		static $count = 0;
		$count ++;
		$item->index = $count;
		echo '<tr class = "row_product_variation" >';
		$this->single_row_columns( $item );
		echo '</tr>';	
		$this->dimensions_editor( $item );
		$this->stock_editor( $item );
		$this->price_editor( $item );		
		$this->vendor_editor( $item );		
	}

	public function get_bulk_actions()
	{
		$actions = array();
		if ( $this->is_trash )
			$actions['untrash'] = __('Восстановить' );
				
		if ( !$this->is_draft )
			$actions['draft'] = __('Черновик', 'usam');			
		if ( !$this->is_publish )
			$actions['show'] = __('Опубликовать', 'usam');	
		if ( $this->is_trash || !EMPTY_TRASH_DAYS )
			$actions['delete'] = __('Удалить изображение' );
		else
			$actions['trash'] = __('Переместить в корзину' );
		return $actions;
	}
	
	private function count_variations()
	{
		global $wpdb;
		$results = $wpdb->get_results($wpdb->prepare( "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = 'usam-product' AND post_parent = %d GROUP BY post_status", $this->product_id ));
	
		$return = array();
		foreach ( $results as $row ) {
			$return[$row->post_status] = $row->num_posts;
		}
		return (object) $return;
	}

	public function get_views()
	{
		$parent = get_post( $this->product_id );
		$avail_post_stati = get_available_post_statuses( 'usam-product' );
		$post_type_object = get_post_type_object( 'usam-product' );
		$post_type = $post_type_object->name;
		$url_base = usam_url_admin_action('product_variations_table', ['product_id' => $this->product_id, 'post_status' => 'all'], admin_url('post.php'));
		
		$status_links = array();		
		
		$num_posts = $this->count_variations();		
		$class = '';
		$current_user_id = get_current_user_id();		
		$total_posts = array_sum( (array) $num_posts );
	
		foreach( get_post_stati(['show_in_admin_all_list' => false]) as $state ) 
		{
			if ( isset($num_posts->$state ) )
				$total_posts -= $num_posts->$state;
		}
		$class = empty( $class ) && ( empty($_REQUEST['post_status']) || $_REQUEST['post_status'] == 'all' )&& empty( $_REQUEST['show_sticky'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='{$url_base}' $class>".sprintf( _nx( 'Все <span class="count">(%s)</span>', 'Все <span class="count">(%s)</span>', $total_posts, 'usam'), number_format_i18n( $total_posts ) ) . '</a>';
		foreach( get_post_stati(array(), 'objects') as $status ) 
		{ 
			$class = '';
			$status_name = $status->name;	
			if ( !in_array( $status_name, $avail_post_stati ) )
				continue;
			if ( empty( $num_posts->$status_name ) ) 
			{ 
				if ( isset($_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
					$num_posts->$status_name = 0;
				else
					continue;
			}
			if ( isset($_REQUEST['post_status']) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			$status_links[$status_name] = "<a href='" . esc_url( add_query_arg( 'post_status', $status_name, $url_base ) ) ."'$class>" . sprintf( translate_nooped_plural( $status->label_count, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}
		return $status_links;
	}

	private function display_bulk_edit_row()
	{
		$style = $this->is_bulk_edit ? '' : " style='display:none;'";
		$classes = 'usam-bulk-edit';
		if ( $this->is_bulk_edit )
			$classes .= ' active';
		echo "<tr {$style} class='{$classes}'>";
		list( $columns, $hidden ) = $this->get_column_info();
		foreach ( $columns as $column_name => $column_display_name )
		{
			$class = "class='$column_name column-$column_name inline-edit-row'";
			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';
			$attributes = "$class $style";
			if ( $column_name == 'cb' )
				echo '<td></td>';
			elseif ( method_exists( $this, 'bulk_edit_column_' . $column_name ) )
			{
				echo "<td $attributes>";
				echo call_user_func( array( &$this, 'bulk_edit_column_' . $column_name ) );
				echo "</td>";
			}
		}
		echo '</tr>';	
		$this->stock_editor( );
		$this->price_editor( );		
		$this->vendor_editor();
	}
	
	public function extra_tablenav( $which )
	{
		$post_type_object = get_post_type_object( 'usam-product' );
		?><div class="alignleft actions"><?php
		if ( $this->is_trash ) 
		{
			if ( current_user_can( $post_type_object->cap->edit_others_posts ) ) 
				submit_button( __('Очистить корзину', 'usam'), 'button-secondary apply', 'delete_all', false );
		}		
		else
			submit_button( __('Сохранить вариацию', 'usam'), 'primary button js-save-variation', '', false );
		?></div><?php
	}
}