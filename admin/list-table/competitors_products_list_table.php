<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/parser/products_competitors_query.class.php' );
class USAM_List_Table_competitors_products extends USAM_List_Table
{		
	function get_bulk_actions_display() 
	{
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}
		
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];
	}
	
	function column_product_title( $item )
	{
		$title = '';
		if( $item->thumbnail )
			$title = "<span class='product_image image_container'><img src='$item->thumbnail'></span>";
		$title .= "<a href='".$item->url."'>".$item->title."</a>";
		if ( $item->sku )
			$title .= "<div class='product_sku'>".__( 'Артикул', 'usam').": <span class='js-copy-clipboard'>".esc_html( $item->sku )."</span></div>";
		echo $title;	
	}

	function column_price( $item )
	{ 
		if ( $item->product_id )
		{
			$site = usam_get_parsing_site( $item->site_id );			
			echo "<a href='".get_permalink($item->product_id)."'>".usam_get_product_price_currency($item->product_id, false, $site['type_price'])."</a>";
		}
	}	
	
	function column_difference( $item )
	{
		if ( $item->product_id )
		{
			$site = usam_get_parsing_site( $item->site_id );
			if ( usam_get_product_price($item->product_id, $site['type_price']) )
			{
				$difference = (float)$item->current_price*100/usam_get_product_price($item->product_id, $site['type_price']) - 100;
				if ( $difference > 0 )
					echo "<span class='item_status_valid item_status'>".round($difference,1)."%</span>";
				elseif ( $difference == 0 )
					echo "<span class='item_status_notcomplete item_status'>".round($difference,1)."%</span>";
				else
					echo "<span class='item_status_attention item_status'>".round($difference,1)."%</span>";
			}
		}
	}
	
	function column_current_price( $item )
	{ 
		$item->old_price = (float)$item->old_price;
		$item->current_price = (float)$item->current_price;
		echo "<div class='comparison_parameters'>";
		echo $this->currency_display( $item->current_price );
		if ( $item->current_price > 0 && $item->old_price > 0 )
		{
			$difference = 100 - $item->old_price*100/$item->current_price;
			if ( $difference > 0 )
				echo "<span class='item_status_valid item_status'><span class='dashicons pointer_up'></span>".round($difference,1)."%</span>";
			elseif ( $difference == 0 )
				echo "<span class='item_status_notcomplete item_status'>".round($difference,1)."%</span>";
			else
				echo "<span class='item_status_attention item_status'><span class='dashicons pointer_down'></span>".round($difference,1)."%</span>";
		}	
		echo '</div>';		
	}
	
	function column_competitor_storage( $item )
	{
		echo $item->storage?$item->storage:'-';
	}
	
	function column_status( $item )
	{
		echo $item->status=='available'?"<span class='item_status_valid item_status'>".__("Доступен","usam")."</span>":"<span class='status_blocked item_status'>".__("Не доступен","usam")."</span>";
	}
	
	function column_date_update( $item )
	{
		echo usam_local_formatted_date( $item->date_update );
	}
	
	function column_competitor( $item )
	{
		$site = usam_get_parsing_site( $item->site_id );
		echo "<a href='".$site['scheme']."://".$site['domain']."'>".$site['name']."</a>";
	}
	
	function get_sortable_columns()
	{
		$sortable = array(
			'title'          => array('title', false),				
			'current_price'  => array('current_price', false),			
			'date_insert'    => array('date_insert', false),
			'date_update'    => array('date_update', false)
		);
		return $sortable;
	}	
		
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',
			'product_title' => __('Имя товара', 'usam'),
			'competitor'      => __('Конкурент', 'usam'),
			'status'         => __('Статус', 'usam'),	
			'current_price'  => __('Цена конкурента', 'usam'),				
			'difference'     => __('Разница', 'usam'),					
			'price'          => __('Ваша цена', 'usam'),			
			'date_update'     => __('Дата обновления', 'usam'),
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{			
		$this->get_query_vars();			
		if ( empty($this->query_vars['include']) )
		{		
			$selected = $this->get_filter_value( 'category_id' );
			if ( $selected )	
				$this->query_vars['competitor_category_id'] = array_map('intval', (array)$selected);			
			$selected = $this->get_filter_value( 'parsing_sites' );
			if ( $selected )
				$this->query_vars['site_id'] = array_map('intval', (array)$selected); 
			$this->get_digital_interval_for_query(['difference'], 'meta_price_query');			
			$this->get_digital_interval_for_query(['price', 'growth', 'decline']);
		} 			
		$this->query_vars['cache_product_meta'] = true;		
		$query = new USAM_Products_Competitors_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}	
	}
}