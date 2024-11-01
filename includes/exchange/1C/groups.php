<?php
if (!defined('ABSPATH')) exit;

require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_Groups_Element_Handler extends USAM_Products_Handler
{
	private $groups = [];
	private $group_parent_ids = [''];
	
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{ 
		$this->is_full = $is_full;
		 
		if (!$depth && $name != 'КоммерческаяИнформация') {
			usam_error("XML parser misbehavior.");
		}	
		elseif (@$names[$depth - 2] == 'Группы' && $name == 'Группы') 
		{		
			$group = end($this->groups);
			$this->group_parent_ids[] = @$group['Ид'];
		}				
		elseif (@$names[$depth - 1] == 'Группы' && $name == 'Группа') 
		{			
			$this->groups[] = ['ИдРодителя' => end($this->group_parent_ids)];
		}
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{
		if (@$names[$depth - 2] == 'Группы' && @$names[$depth - 1] == 'Группа' && $name != 'Группы') {
			
			$i = count($this->groups)-1;
			@$this->groups[$i][$name] .= $data;
		}		
	}

	function end_element_handler( $is_full, $names, $depth, $name) 
	{
		if (@$names[$depth - 2] == 'Группы' && $name == 'Группы') 
		{ 
			array_pop($this->group_parent_ids);
		}
		if (@$names[$depth - 1] == 'Классификатор' && $name == 'Группы') 
		{ 				 
			$setting = get_option('usam_1c', ['product' => ['categories' => 0]]);	
			if ( !empty($setting['product']['categories']) )
			{				
				$codes = array();
				foreach ($this->groups as $sort => $group )
					$codes[] = $group['Ид'];			
				usam_terms_id_by_meta('external_code', $codes, 'usam-category'); 
				
				$term_ids = array();				
				foreach ($this->groups as $sort => $group )
				{
					if ( ! apply_filters('usam_import_group_xml', $group, $sort, $is_full) ) 
						continue;
					$term_ids[] = $this->replace_term($group['Ид'], $group['Наименование'], $group['ИдРодителя'], 'usam-category', $sort, $is_full);	
				}				
			/*	if ( !empty($term_ids) )
					usam_get_products( ['tax_query' => [['taxonomy' => 'usam-category', 'field' => 'id', 'terms' => $term_ids, 'operator' => 'IN']], 'posts_per_page' => 300, 'cache_results' => true, 'update_post_meta_cache' => true, 'update_post_term_cache' => true, 'stocks_cache' => false, 'prices_cache' => false, 'product_meta_cache' => true, 'product_attribute_cache' => true, 'product_images_cache' => true] );*/
			}
		}	
		elseif (!$depth && $name == 'КоммерческаяИнформация') 
		{ 		
			do_action('usam_1c_import_groups', $is_full);
		}
	}
}