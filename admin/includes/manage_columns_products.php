<?php
require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');
/**
 * Вывод столбцов. Получает название столбца $column и подключает его к хуку "usam_manage_products_column__{$column}"
 */
final class USAM_Manage_Columns_Products
{	
	private static $instance; 
	
	public function __construct( )
	{	
		$this->load_manage_custom_column();
	}
	
	public static function get_instance() 
	{
		if ( ! self::$instance ) 
			self::$instance = new USAM_Manage_Columns_Products();		
		return self::$instance;
	}	
	
	public static function load_manage_custom_column( ) 
	{	
		add_action( 'manage_usam-product_posts_custom_column', ['USAM_Manage_Columns_Products', 'column_display'], 11, 2 );	
		add_action( 'admin_footer', ['USAM_Manage_Columns_Products', 'footer']);	
	}
	
	public static function footer() 
	{
		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/product-viewer.php' );	
	}
	
	public static function column_display( $column, $product_id ) 
	{		
		$product = get_post( $product_id );		
		if ( !empty($product) )
		{
			$function_column = "column_".strtolower( $column );	
			if ( method_exists(__CLASS__, $function_column) )
				self::$function_column( $product );
			else
				self::column_default( $column, $product );
				
		}
	}	
	
	public static function column_default( $column, $product ) 
	{	
		if ( stripos($column, 'storage_') !== false )
		{
			$storages = usam_get_storages( );	
			foreach( $storages as $storage )
			{ 
				if ( $column == 'storage_'.$storage->id )
				{				
					echo "<div class='stock'>".usam_get_stock_in_storage( $storage->id, $product->ID )."</div>";
					$reserve = usam_get_reserve_in_storage( $storage->id, $product->ID );
					if ( $reserve )
					{
						echo "<div class='reserve' title='".sprintf( __('В резерве %s','usam'),$reserve)."'><div class='reserve_number'>".$reserve."</div></div>";
					}
					break;				
				}			
			}
		}
		elseif ( stripos($column, 'sale_area_') !== false )
		{
			$sales_area = usam_get_sales_areas();
			foreach ( $sales_area as $sale_area )
			{
				if ( $column == 'sale_area_'.$sale_area['id'] )
				{ 
					$stock = usam_get_product_stock( $product->ID, 'stock_'.$sale_area['id'] );			
					if ( $stock >= USAM_UNLIMITED_STOCK )
						$stock = USAM_UNLIMITED_STOCK;
					elseif ( usam_is_weighted_product( $product->ID ) )
						$stock = usam_string_to_float( $stock );
					else
						$stock = (int)$stock;
					echo $stock;
					break;
				}	
			}
		}
		elseif ( stripos($column, 'attr_') !== false )
		{
			echo usam_get_product_attribute_display($product->ID, str_replace('attr_', '', $column) );
		}
	}
		
	//Колонка "Изображение" товара	 
	public static function column_image( $product ) 
	{	
		echo "<a href='".get_edit_post_link( $product->ID )."'>".usam_get_product_thumbnail( $product->ID, 'manage-products' )."</a>";
	}
	
	public static function column_product_title( $product ) 
	{			
		echo "<span class='js-product-viewer-open viewer_open product_image image_container' product_id='$product->ID'>".usam_get_product_thumbnail( $product->ID, 'manage-products' )."</span>";
		echo "<a href='".get_edit_post_link( $product->ID )."' class='product_title_link product_$product->post_status'>".$product->post_title."</a>"; 
		$sku = usam_get_product_meta( $product->ID, 'sku' );
		if ( $sku )
		{
			?><div class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span id="product_sku-<?php echo $product->ID; ?>" class="js-copy-clipboard"><?php echo esc_html( $sku ) ?></span></div><?php
		}		
		get_inline_data( $product );		
	}	
		 
	public static function column_name( $product ) 
	{	
		echo "<a href='".get_edit_post_link( $product->ID )."'>".$product->post_title."</a>";		
		echo '
			<div class="hidden" id="usam_inline_' . absint( $product->ID ) . '">			
				<div class="sku js-copy-clipboard">' . esc_html( usam_get_product_meta( $product->ID, 'sku' ) ) . '</div>
			</div>';
	}
	
	//Колонка "Дата изменения" товара	 
	public static function column_post_modified( $product ) 
	{	
		echo date( "d.m.Y", strtotime($product->post_modified) );	
	}	
	
	public static function column_status( $product ) 
	{			 
		if ( $product->post_status == 'publish' )
			echo "<span class='item_status_valid item_status'>".get_post_status_object( $product->post_status )->label."<span></span></span>";
		elseif ( $product->post_status == 'draft' )
			echo "<span class='status_blocked item_status'>".get_post_status_object( $product->post_status )->label."<span></span></span>";		
		elseif ( $product->post_status == 'archive' )
			echo "<span class='item_status_attention item_status'>".get_post_status_object( $product->post_status )->label."<span></span></span>";		
		else
			echo "<span class='item_status_notcomplete item_status'>".get_post_status_object( $product->post_status )->label."<span></span></span>";			
	}		
		
	//Колонка "Цена" товара	 	
	public static function column_price( $product ) 
	{					
		$type_price = usam_get_manager_type_price();
		$out = ''; 
		$product_type = usam_get_product_type( $product->ID );	
		if ( $product_type == 'variable' ) 				
			$out .= __("от", 'usam').' ';
		$price = usam_get_product_price( $product->ID, $type_price );
		$old_price = usam_get_product_old_price( $product->ID, $type_price );		
		if( $old_price != 0 ) 					
			$out .= '<span class = "price"><strong>'.usam_get_formatted_price( $price ).'</strong></span><br><span class = "old_price"><strike>'.usam_get_formatted_price( $old_price )."</strike></span>";
		else
			$out .= "<strong>".usam_get_formatted_price( $price )."<strong>";
		echo $out;
	}
	
	//Колонка "Артикул" товара	
	public static function column_sku( $product ) 
	{ 
		$sku = usam_get_product_meta( $product->ID, 'sku' );
		echo "<span class='js-copy-clipboard'>$sku</span>";	
	}	
	
	public static function column_code( $product ) 
	{ 
		$code = usam_get_product_meta( $product->ID, 'code' );	
		echo $code;	
	}	
	
	private static function display_term( $product_id, $taxonomies ) 
	{
		$terms = get_the_terms( $product_id, $taxonomies );	
		if ( !empty( $terms ) )
		{
			$out = array();
			foreach ( $terms as $term )
				$out[] = "<a href='".admin_url("edit.php?post_type=usam-product&amp;$taxonomies={$term->slug}")."'> ".$term->name."</a>";
			echo join( ', ', $out );
		} 		
	}
	
	//Колонка "Категории" товара	
	public static function column_cats( $product ) 
	{
		self::display_term( $product->ID, 'usam-category' );
	}
	
	//Колонка "Категории" товара	
	public static function column_brand( $product ) 
	{		
		self::display_term( $product->ID, 'usam-brands' );
	}
	
	// Колонка "Избранный" в таблице продуктов админского интерфейса.
	public static function column_featured( $product )
	{
		if ( usam_check_user_product_by_list( $product->ID, 'sticky' ) ) : ?>
			<span class="usam-dashicons-icon list_selected js-featured-product-toggle"></span>					
		<?php else: ?>
			<span class="usam-dashicons-icon js-featured-product-toggle" title="<?php _e( 'Добавить в список', 'usam'); ?>"></span>					
		<?php endif;
	}	

	/**
	 * Колонка "Описание" в таблице продуктов админского интерфейса.	
	 */
	public static function column_pdesc( $product )
	{
		if ( empty($product->post_excerpt) )
			$class = "no-description-icon";
		else
			$class = "yes-description-icon";
		?>	
		<div class = "usam-description-icon <?php echo $class; ?>"></div>
		<?php
	}	

	/**
	 * Колонка "Просмотры" в таблице продуктов админского интерфейса.	
	 */
	public static function column_views( $product ) 
	{
		echo usam_get_post_meta( $product->ID, "views" );	
	}	
			
	// Колонка "Вес" в таблице продуктов админского интерфейса.
	public static function column_weight( $post ) 
	{
		$product_type = usam_get_product_type( $post->ID );	
		if ( $product_type == 'variable' ) 	
		{
			esc_html_e( 'N/A', 'usam');
			return;
		}	
		echo usam_get_product_weight( $post->ID );	
	}	

	// Колонка "Запас" в таблице продуктов админского интерфейса.
	public static function column_stock( $product ) 
	{
		$stock = usam_product_remaining_stock( $product->ID, "stock" );		
		if( $stock >= USAM_UNLIMITED_STOCK )
			$out = '&#8734;';		
		else		
			$out = usam_get_formatted_quantity_product_unit_measure( $stock, usam_get_product_meta( $product->ID, 'unit_measure' ) );
		if ( $stock == 0.000 )
			$out = "<span class='item_status_attention item_status'>".$out."<span>";
		echo $out;
	}	
	
	public static function column_reserve( $product ) 
	{
		$storages = usam_get_storages();					
		$reserve = 0;
		foreach ( $storages as $storage )
			$reserve += usam_get_reserve_in_storage($storage->id, $product->ID);
		if ( $reserve > 0 )
			echo "<span class='item_status_valid item_status'>".usam_get_formatted_quantity_product_unit_measure( $reserve, usam_get_product_meta( $product->ID, 'unit_measure' ) )."<span>";
	}	
		
	// Колонка "Общий запас" в таблице продуктов админского интерфейса.
	public static function column_total_balance( $product ) 
	{
		$stock = usam_get_product_stock( $product->ID, "total_balance" );
		if( $stock >= USAM_UNLIMITED_STOCK )
			$out = '&#8734;';		
		else		
			$out = usam_get_formatted_quantity_product_unit_measure( $stock, usam_get_product_meta( $product->ID, 'unit_measure' ) );
		if ( $stock == 0.000 )
			$out = "<span class='item_status_attention item_status'>".$out."<span>";
		echo $out;	
	}		

	// Колонка "Комментарии" в таблице продуктов админского интерфейса.
	public static function column_comment( $post ) 
	{
		echo '<a href="'.admin_url('admin.php?page=feedback&tab=reviews&page_id='.$post->ID).'" title="0 ожидающих" class="post-com-count"><span class="comment-count">'.(int)usam_get_post_meta($post->ID, 'comment').'</span></a>';
	}
	
	//Колонка "Рейтинг" в таблице продуктов админского интерфейса.
	public static function column_prating( $product ) 
	{
		$rating_count = usam_get_post_meta( $product->ID, 'rating_count' );
		$p_rating = usam_get_post_meta( $product->ID, 'rating' );		
		
		$p_rating = $p_rating > 6 ? 5 : $p_rating;				
		$output = "<span class='rating product_rating' data-product_id='".$product->ID."'>";
		for ( $l = 1; $l <= $p_rating; ++$l )
		{ 
			$output .= "<span class='star rating__selected'></span>";		
		}
		$remainder = 5 - $p_rating;
		for ( $l = 1; $l <= $remainder; ++$l ) {
			$output .= "<span class='star'></span>";
		}
		$output .= "<span class='vote_total'>(<span id='vote_total_{$product->ID}'>".$rating_count."</span>)</span>";
		echo $output;		
	}

	public static function column_author( $product ) 
	{
		echo usam_get_manager_name( $product->post_author );
	}
	
	public static function column_seller( $product ) 
	{
		$seller = usam_get_seller_product( $product->ID );
		echo isset($seller['name'])?$seller['name']:'';
	}
}
?>