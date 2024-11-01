<?php
if (!defined('ABSPATH')) exit;

require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_Storages_Element_Handler extends USAM_Products_Handler
{
	private $storages;
	
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{ 
		global $usam_attribute;

		$this->is_full = $is_full;
		 
		if (!$depth && $name != 'КоммерческаяИнформация') {
			usam_error("XML parser misbehavior.");
		}			
		elseif (@$names[$depth - 1] == 'Склады' && $name == 'Склад') 
		{
			$this->storages[] = array();
		}		
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{
		global $usam_attribute;
		
		if (@$names[$depth - 2] == 'Склады' && @$names[$depth - 1] == 'Склад') 
		{ 
			$i = count($this->storages)-1;
			@$this->storages[$i][$name] .= $data;
		}		
	}

	function end_element_handler( $is_full, $names, $depth, $name) 
	{  
		if (@$names[$depth - 1] == 'Классификатор' && $name == 'Склады')
		{
			static $storages;
			if ( $storages === null )
				$storages = usam_get_storages(['fields' => 'code=>meta_key', 'active' => 'all']);			
			foreach ($this->storages as $storage) 
			{  
				if ( !isset($storages[$storage['Ид']]) )
				{ 
					$id = usam_insert_storage(['active' => 1, 'shipping' => 1, 'code' => strtolower($storage['Ид']), 'title' => $storage['Наименование']]);
					$address = isset($storage['Адрес']['Представление'])?trim($storage['Адрес']['Представление']):'';
					usam_update_storage_metadata( $id, 'address', $address);
				}
			}			
		}
	}
}