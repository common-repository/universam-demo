<?php
class USAM_Tab_products_report extends USAM_Tab
{		
	public function get_tab_sections() 
	{  
		$tables = array();
		if ( isset($_GET['product']) )
			$tables = ['products_report' => ['title' => __('Назад к товарам','usam')], 'product_report' => ['title' => __('Отчет по товару','usam'), 'type' => 'table']];
		else
		{
			$tables = array('groups_report' => array('title' => __('По группам','usam'), 'type' => 'table') );
			if ( isset($_GET['group_id']) && isset($_GET['taxonomy']) )
			{				
				$taxonomy = sanitize_title($_GET['taxonomy']);	
				$id = absint($_GET['group_id']);	
				$term = get_term( $id, $taxonomy );
				if ( $taxonomy == 'usam-category' )
				{
					
				}
				else
				{
					$tables['current'] = array( 'title' => $term->name, 'type' => 'table', 'url' => array( 'id' => $id, 'taxonomy' => $taxonomy, 'table' => 'groups_report' ) );
				}
			}
			else
				$tables['products_report'] = array( 'title' => __('По товарам','usam'), 'type' => 'table' );			
		}
		return $tables;
	}
	
	public function get_title_tab() 
	{				
		if ( $this->table == 'product_report') 
		{
			if ( isset($_GET['product']) )
			{
				$product_title = get_the_title( $_GET['product'] );
				return sprintf(__('Отчет по товару &#8220;%s&#8221;', 'usam'), $product_title);	
			}
			else
				return __('Выберите товар', 'usam');	
		}
		elseif ( $this->table == 'groups_report') 
		{		
			if ( isset($_GET['group_id']) )
			{
				$taxonomy = sanitize_title($_GET['taxonomy']);	
				$id = absint($_GET['group_id']);	
				$term = get_term( $id, $taxonomy );
				return sprintf(__('Отчет по %s', 'usam'),$term->name);	
			}
			else
				return __('Отчет по группам', 'usam');	
		}
		else
			return __('Отчет по проданным товарам', 'usam');	
	}
}