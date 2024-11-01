<?php
/**
 * Функции для редактирования и добавления товаров на странице ТОВАРОВ
 */
require_once( USAM_FILE_PATH . '/admin/admin-products-list.php' );
class USAM_Display_Products_Stocks extends USAM_Admin_Products_List
{
	function load(  )
	{			
		add_filter( 'manage_edit-usam-product_sortable_columns', array(&$this, 'additional_sortable_column_names') ); // Какие колонки можно будет сортировать
		add_filter( 'manage_edit-usam-product_columns', array(&$this, 'additional_column_names'),11 );
		add_filter( 'manage_usam-product_posts_columns', array(&$this, 'additional_column_names'),11 );		

		add_filter( 'page_row_actions', array(&$this, 'action_product_in_row'), 10, 2 );
		add_filter( 'page_row_actions', array(&$this, 'action_product_in_row'), 10, 2 );	
						
		add_action('admin_head', array( $this,'admin_bar_css' ) );
	}	
	
	public function admin_bar_css()
	{	
		?>
		<style>		
			.wp-list-table .column-product_title {width:20%;}
			<?php 
			$storages = usam_get_storages( );	
			foreach( $storages as $storage )
			{
				?>.wp-list-table thead .column-storage_<?php echo $storage->id ?> a{ writing-mode: vertical-lr; }<?php 					
			}	
			?>			
			.wp-list-table tfoot{display:none}
		</style>
		<?php 
	} 
	
	function additional_column_names( $columns )
	{
		$columns = [];
		$columns['cb']            = '<input type="checkbox" />';	
		$columns['product_title'] = __('Название товара', 'usam');	
		$columns['price']         = __('Цена', 'usam');			
		$columns['total_balance'] = __('Запас', 'usam');			
		$storages = usam_get_storages( );	
		foreach( $storages as $storage )
		{
			$t = strlen($storage->title) > 8 ? '...':'';
			$columns['storage_'.$storage->id] = mb_substr($storage->title,0,8).$t;
		}		
		return $columns;
	}
			
	function additional_sortable_column_names( $columns )
	{		
		$columns['total_balance'] = 'total_balance';
		$columns['price']         = 'price';
		$columns['sku']           = 'sku';
		$columns['code']          = 'code';		
		$columns['views']         = 'views';
		$columns['prating']       = 'rating';	
		$columns['featured']      = 'sticky';	
		$columns['weight']        = 'weight';		
		$columns['author']        = 'post_author';	
		$storages = usam_get_storages( );	
		foreach( $storages as $storage )
		{
			$columns['storage_'.$storage->id] = 'storage_'.$storage->id;	
		}	
		return $columns;
	}
	
	//Действие над продуктами в строке
	function action_product_in_row( $actions, $post ) 
	{
		if ( $post->post_type != "usam-product" )
			return $actions;
		$actions = array();
		$actions['editinline'] = '<button type="button" class="button-link editinline" aria-expanded="false">' . __("Свойство", "usam") . '</button>';
		return $actions;
	}
} 
?>