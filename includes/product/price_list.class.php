<?php
/**
 * Скачать прайс лист
 */
require_once( USAM_FILE_PATH . '/includes/product/product_exporter.class.php' );
class USAM_Price_List extends USAM_Product_Exporter
{	
	public function customer_pricelist_download() 
	{		
		if ( $this->check_availability() )
		{
			$file_generation = usam_get_exchange_rule_metadata( $this->rule['id'], 'file_generation' );
			if ( $file_generation )
			{
				$file_path = USAM_UPLOAD_DIR."exchange/exporter_".$this->rule['id'].".".usam_get_type_file_exchange( $this->rule['type_file'], 'ext' );				
				if ( is_file( $file_path ) )
				{ 
					usam_download_file( $file_path, $this->rule['name'].'.'.usam_get_type_file_exchange( $this->rule['type_file'], 'ext' ) );	
					exit();
				}
				else
					wp_die(__('Файл не существует!', 'usam'));
			}	
			else			
				$this->export_file();
		}
	}		
	
	protected function get_exel_args( ) 
	{
		$columns = $this->get_name_columns();	
		return ['header_data' => [ 
			['value' => __("Прайс-лист","usam"), 'font' => ['name' => 'Times New Roman', 'size' => 30, 'color' => ['rgb' => '4b711d']], 'merge_cells' => ['start' => 'A', 'end' => 'D'] ], 
			['value' => $this->rule['name'], 'merge_cells' => ['start' => 'A', 'end' => 'W'] ], 
			['value' => usam_local_date( date("Y-m-d H:i:s"), get_option( 'date_format', 'Y/m/d' ) ) ],
			['value' => '' ]
		], 'headers' => $columns, 'list_title' => __("Прайс-лист","usam")];
	}

	protected function get_data( $param = [] ) 
	{			
		$products = $this->get_products_data( $param );		
		if ( $this->rule['type_file'] == 'exel' )
		{
			$style = [		
				'borders' => [
					'top' => ['color' => '#4b711d'],
					'left' => ['color' => '#4b711d'],
					'right' => ['color' => '#4b711d'],
					'bottom' => ['color' => '#4b711d'],
				],
				'alignment' => ['horizontal' => 'left']
			];
			$columns = ['product_id', 'stock', 'unit', 'weight', 'views', 'rating', 'rating_count', 'box_length', 'box_width', 'box_height', 'rating', 'barcode', 'post_parent'];
			$storages = usam_get_storages();					
			foreach ( $storages as $storage )
			{	
				$columns[] = 'storage_'.$storage->code;
			}	
			$prices = usam_get_prices( );					
			foreach ( $prices as $price )
			{	
				$columns[] = 'price_'.$price['code'];
			}		
			foreach ($products as &$product )
			{
				foreach ($product as $key => $value )
				{
					$style['value'] = $value;
					if ( in_array($key, $columns) )
					{
						$style['alignment']['horizontal'] = 'right';
						$style['format'] = 'number';
					}
					else
						$style['alignment']['horizontal'] = 'left';
					$product[$key] = $style;
				}
			}
		}
		return $products;
	}	
	
	private function check_availability() 
	{				
		if ( !empty($this->rule) && $this->rule['type'] == 'pricelist' )
		{ 
			if ( empty($this->rule['schedule']) )
				return false;			
		
			$user_id = get_current_user_id();
			$user = get_userdata( $user_id );	
			$roles = usam_get_exchange_rule_metadata( $this->rule['id'], 'roles' );
			if ( empty($roles) )
				return true;
			
			$result = array_intersect($roles, $user->roles);		
			if ( !empty($result) )					
				return true;				
		}	
		return false;		
	}	
}	
?>