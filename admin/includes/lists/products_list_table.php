<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_products_Table extends USAM_Product_List_Table
{	
	protected function display_tablenav( $which ) 
	{
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( $this->has_items() ): ?>
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}
		
	function column_image( $item ) 
	{
		echo "<a href='' data-product_id='$item->ID'>".usam_get_product_thumbnail($item->ID, 'manage-products' )."</a>";
	}
	
	function column_product_title( $item ) 
	{
		echo "<a href='' class='product_title_link' data-product_id='$item->ID'>$item->post_title</a>";
	}
	
	function get_sortable_columns()
	{
		$sortable = array(
			'product_title'  => array('product_title', false),	
			'price'          => array('price', false),	
			'stock'          => array('stock', false),		
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           									
			'image'          => '',
			'product_title'  => __('Имя', 'usam'),		
			'price'          => __('Цена', 'usam'),	
			'sku'            => __('Артикул', 'usam'),								
			'stock'          => __('Запас', 'usam'),			
        );		
        return $columns;
    }
}