<?php 
require_once( USAM_FILE_PATH . '/includes/parser/parser.class.php' );
require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');

class USAM_Competitors_Prices_Update_Parser
{
	private $product = [];	
	protected $options = [];
	private $message = array();
	private $errors = array();
	
	public function __construct( $options, $product ) 
	{	
		$this->product = $product;
		$this->options = $options;
	}

	// Функция проверки остатков. Получает массив данных
	public function check_product( )
	{					
		$data = $this->get_website_data( $this->product['url'] );				
		if( $data )
		{														
			if ( !empty($data['price']) )
			{								
				$status = !isset($data['not_available']) || $data['not_available'] === false ?'available':'not_available';
				$update = ['current_price' => $data['price'], 'status' => $status];
				if ( !empty($data['title']) )
					$update['title'] = $data['title'];
				if ( !empty($data['thumbnail']) ) 
					$update['thumbnail'] = $data['thumbnail'];
				
				global $wpdb;
				$product_price = $wpdb->get_row("SELECT * FROM `".USAM_TABLE_COMPETITOR_PRODUCT_PRICE."` WHERE competitor_product_id=".$this->product['id']." ORDER BY date_insert DESC LIMIT 1");
				if ( !empty($product_price) )
				{
					$update['old_price'] = $product_price->price;
					$update['old_price_date'] = $product_price->date_insert;
				}
				usam_update_product_competitor($this->product['id'], $update);				
				usam_insert_competitor_product_price(['competitor_product_id' => $this->product['id'], 'price' => $data['price']]);				
			}			
		}
	}
		
	private function get_website_data( $url )
	{
		$parser = new USAM_Parser( $this->options );
		$data = $parser->get_website_data( $url );
		$parser->clear();
		return $data;
	}	
}
?>