<?php
require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );
require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );
require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_price_analysis extends USAM_Product_List_Table
{	
	private $products = [];
	private $sites = [];
	
	function __construct( $args = [] )
	{	
		$this->sites = usam_get_parsing_sites(['site_type' => 'competitor', 'active' => 'all']);
		parent::__construct( $args );	
    }
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}
	
	public function get_views() {}	
	
	function column_default( $item, $column_name ) 
	{				
		if ( stripos($column_name, 'competitor_') !== false )
		{
			$id = str_replace('competitor_', '', $column_name);
			$competitor_product = [];
			foreach ( $this->products as $i => $product )
			{
				if ( $id == $product->site_id && $item->ID == $product->product_id )
				{					
					$competitor_product = $product;		
					unset($this->products[$i]);
					break;
				}
			}
			if ( $competitor_product )
			{
				$type_price = usam_get_manager_type_price();
				echo "<div class='product_{$competitor_product->status}'>";
				if ( $competitor_product->status == 'not_available' )
					echo '<div class="status_blocked item_status">'.__("Не доступен").'</div>';
				if ( $competitor_product->current_price )
				{
					$price = usam_get_product_price( $item->ID, $type_price );					
					$difference = $price > 0? $competitor_product->current_price*100/$price - 100 : 0;
					echo "<p><a href='$competitor_product->url' target='_blank'>".$this->currency_display( $competitor_product->current_price )."</a></p>";
					if ( $difference > 0 )
						echo "<span class='item_status_valid item_status'>".round($difference,1)."%</span>";
					elseif ( $difference == 0.0 )
						echo "<span class='item_status_notcomplete item_status'>".round($difference,1)."%</span>";
					else
						echo "<span class='item_status_attention item_status'>".round($difference,1)."%</span>";			
				}
				if ( $competitor_product->date_update )
					echo '<div class="document_date">'.usam_local_date( $competitor_product->date_update, get_option( 'date_format', 'd.m.Y' ) ).'</div>';
				echo '</div>';
			}
		}
		else
			parent::column_default( $item, $column_name );
	}
		
	function get_sortable_columns()
	{
		$sortable = [
			'product_title'  => array('product_title', false),	
			'price'          => array('price', false),		
			'views'          => array('views', false),	
		];
		foreach ($this->sites as $site ) 
			$sortable['competitor_'.$site->id] = 'competitor_'.$site->id;
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = [
			'cb'             => '<input type="checkbox" />',
			'product_title'  => __('Имя', 'usam'),
			'price'          => __('Ваша цена', 'usam'),						
        ];		
		foreach ($this->sites as $site ) 
			$columns['competitor_'.$site->id] = $site->name;
        return $columns;
    }	

	public function display_rows( $posts = array(), $level = 0 ) 
	{
		global $wp_query;

		if ( empty($posts) )
			$posts = $wp_query->posts;	
		if ( $posts )
		{
			$ids = [];
			foreach ( $posts as $post )
				$ids[] = $post->ID;
			$this->products = usam_get_products_competitors(['product_id' => $ids, 'fields' => 'site_id=>data']);
		}
		parent::display_rows( $posts, $level );
	}
}