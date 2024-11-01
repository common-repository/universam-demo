<?php 
require_once( USAM_FILE_PATH . '/includes/parser/parser.class.php' );

// Класс для получения данных товаров на других сайтах
class USAM_Product_Update_Parser
{
	private $product_id = 0;	
	protected $options = [];
	private $message = array();
	private $errors = array();
	
	public function __construct( $product_id = 0, $options = [] ) 
	{	
		$this->product_id = $product_id;
		$this->options = $options;
	}
	
	private function set_stock_product( $status, $id )
	{
		global $wpdb;	
		$stock = $status == true ? USAM_UNLIMITED_STOCK : 0;
		$update_stock = $wpdb->update( USAM_TABLE_STOCK_BALANCES, ['meta_value' => $stock] , ['meta_key' => "storage_{$id}", 'product_id' => $this->product_id], ['%d'] );	
		usam_recalculate_stock_product( $this->product_id );		
		return $update_stock;
	}
	
	public function get_errors( )
	{
		return $this->errors;
	}
	
	private function set_error( $error, $product_id = 0 )
	{			
		if ( $product_id )
			$this->errors[] = sprintf( __('ID продукта %s. Ошибка: %s'), $this->product_id, $error );
		else
			$this->errors[] = sprintf( __('Ошибка: %s'), $error );
	}
	
	private function set_log_file(  )
	{				
		if ( !empty($this->options['domain']) )
			usam_log_file( $this->errors, 'web_spider_'.$this->options['domain'] );
		$this->errors = array();
	}

	// Функция проверки остатков. Получает массив данных
	public function check_product( )
	{				 	
		$url = usam_get_product_meta( $this->product_id, 'webspy_link' );
		if ( $url && $this->options )
		{						
			$data = $this->get_website_data( $url );				
			if( $data )
			{						
				if ( !empty($data['price']) )
				{			
					if ( usam_get_product_price($this->product_id, $this->options['type_price']) != $data['price'] )
					{								
						$prices['price_'.$this->options['type_price']] = $data['price'];				
						usam_edit_product_prices( $this->product_id, $prices );						
					}									
				}
				$tags = (array)usam_get_parsing_site_metadata( $this->options['id'], 'tags' );					
				if ( !empty($tags['not_available']['tag']) ) //Товар купить нельзя					
					$update = $this->set_stock_product( empty($data['not_available']), $this->options['store'] );
			}
			else
			{							
				$update = $this->set_stock_product( false, $this->options['store'] );			
				$this->set_error( __("Ссылка больше недоступна. Ошибка 404.",'usam'), $this->product_id );
			}			
			usam_clean_product_cache( $this->product_id );
		}	
		else							
			$this->set_error( __("Не найдена ссылка.",'usam'), $this->product_id );			
		usam_update_product_meta( $this->product_id, 'date_externalproduct', date("Y-m-d H:i:s") );	
		$this->set_log_file(); 
		return true;	
	}
		
	public function get_website_data( $url )
	{
		$parser = new USAM_Parser( $this->options );
		$data = $parser->get_website_data( $url );
		$parser->clear();
		return $data;
	}
}
?>