<?php
// Рассчитать товары для увеличения продаж заданного товара
class USAM_Increase_Sales_Product
{	
	private $product_id;
	private $type_prices;
	private $limit_upsell = 300;
	private $limit_crosssell = 300;
	
	public function __construct( $product_id ) 
	{		
		$this->product_id = $product_id;
		$this->type_prices = usam_get_prices(['type' => 'R']);			
	}		
	
	 //Рассчитать перекрестные продажи (Cross-Sells) в корзине
	public function cross_sell( $rule )
	{		
		global $wpdb;	
									
		/*foreach ( $this->type_prices as $value )
		{
			$code_price = $value['code'];
			break;
		}*/			
		$post = get_post( $this->product_id );
		$post_title = mb_strtolower($post->post_title);	
		
		if ( empty($rule['active']) || empty($rule['conditions']) )
			return false;
		
		$work = false;
		foreach( $rule['words'] as $word )
		{							
			if ( $word && strpos($post_title, mb_strtolower($word)) !== false) 
			{					
				$work = true;
				break;
			}
		}	
		if ( $work ) 
		{			
			$joins = [];
			$category_join = true;
			$where = '';
			$count = count($rule['conditions'])-1;
			foreach( $rule['conditions'] as $key => $condition )
			{						
				$value = $condition['value'];	
				if ( $condition['logic_operator'] == 'AND' )
					$relation = 'AND';
				else				
					$relation = 'OR';
				if ( $condition['type'] == 'name' )
				{												
					switch (  $condition['logic'] ) 
					{						
						case 'not_equal' :										
							$compare = '!=';
							$value = "'$value'";
						break;						
						case 'contains' :										
							$compare = 'LIKE LOWER ';
							$value = "('%$value%')";
						break;
						case 'not_contain' :										
							$compare = 'NOT LIKE ';
							$value = "'%$value%'";
						break;
						case 'begins' :										
							$compare = 'LIKE LOWER ';
							$value = "('$value%')";
						break;
						case 'ends' :										
							$compare = 'LIKE LOWER ';
							$value = "('%$value')";
						break;
						case 'equal' :	
						default:															
							$compare = '=';
							$value = "'$value'";
						break;
					}						
					$where .= " (p.post_title $compare {$value}) ";
				}
				elseif ( $condition['type'] == 'attr' )
				{
					$attribute = usam_get_product_attribute( $this->product_id, $value );	
					switch (  $condition['logic'] ) 
					{
						case 'equal' :										
							$compare = '=';
						break;
						case 'not_equal' :										
							$compare = '!=';
						break;
						case 'greater' :										
							$compare = '>';
						break;
						case 'less' :										
							$compare = '<';
						break;
						case 'eg' :										
							$compare = '>=';
						break;
						case 'el' :										
							$compare = '=<';
						break;
						case 'contains' :										
							$compare = 'LIKE ';
							$attribute = "%$attribute%";
						break;
						case 'not_contain' :										
							$compare = 'NOT LIKE ';
							$attribute = "%$attribute%";
						break;
						case 'begins' :										
							$compare = 'LIKE ';
							$attribute = "$attribute%";
						break;
						case 'ends' :										
							$compare = 'LIKE ';
							$attribute = "%$attribute";
						break;
					}					
					$joins[] = "LEFT JOIN `".usam_get_table_db('product_attribute')."` AS pa ON (p.ID = pa.product_id AND pa.meta_key = '$value')";
					$where .= " ( pa.meta_value $compare '$attribute' )";							
				}
				elseif ( $condition['type'] == 'category' )
				{
					//$joins[] = "INNER JOIN $wpdb->term_relationships AS tr_$key ON (p.ID = tr_$key.object_id) INNER JOIN $wpdb->term_taxonomy AS tt_$key ON (tr_$key.term_taxonomy_id = tt_$key.term_taxonomy_id)";
				//	$where .= "(tt_$key.taxonomy = 'usam-category' AND tt_$key.term_id=$value)";		
					if ( $category_join )
					{
						$joins[] = "INNER JOIN $wpdb->term_relationships AS tr ON (p.ID = tr.object_id) INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'usam-category')";
						$category_join = false;
					}
					$where .= "(tt.term_id='$value')";	
				}						
				if ( $count != $key )	
					$where .= " $relation ";
			}			
			$products = $wpdb->get_results("SELECT DISTINCT p.ID FROM ".$wpdb->posts." AS p ".implode(' ', $joins)." WHERE $where AND p.post_type='usam-product' AND p.post_status='publish' AND p.post_parent = '0' LIMIT $this->limit_crosssell");				
			$product_ids = array();
			if ( !empty($products) )
			{
				foreach( $products as $product )
					$product_ids[] = $product->ID;
			}	
			usam_add_associated_products( $this->product_id, $product_ids, 'crosssell' );
		}
		return $work;
	} 	
	
	 //Рассчитать поднятие суммы продажи (Upsell)
	public function up_sell( )		
	{			
		$collection = usam_get_product_meta( $this->product_id, 'collection' );				
		if ( empty($collection))		
			return false;
		
		$brand = wp_get_post_terms($this->product_id, 'usam-brands', array("fields" => "all"));		
		if ( empty($brand[0]) )
			return false;		
					
		foreach ( $this->type_prices as $value )
		{
			$code_price = $value['code'];
			break;
		}
		$price = usam_get_product_price($this->product_id, $code_price);		
		$query = array (		
			'orderby' => array( 'attribute_value_num' => 'DESC' ),		
			'attribute_key' => $collection,
			'post__not_in' => array($this->product_id),				
			'price_meta_query' => [['key' => 'price_'.$code_price, 'value' => $price, 'compare' => '>']],
			'fields' => 'ids',
			'usam-brands' => $brand[0]->slug, 		
			'post_status' => 'publish',
			'update_post_term_cache' => true,
			'cache_results' => true,
			'stocks_cache' => false, 
			'prices_cache' => false,
			'posts_per_page' => $this->limit_upsell,
		);
		$product_ids = usam_get_products( $query );	
		usam_add_associated_products( $this->product_id, $product_ids, 'upsell' );
	}
	
	public function down_sell( )		
	{
		
		
	}
}
?>