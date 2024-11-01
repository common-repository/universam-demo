<?php
/*
	Name: Атол онлайн
	Description: Отправка чеков в Атол онлайн
	Price: paid
	Group: cashbox
	Icon: atol
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_atol_online extends USAM_Application
{			
	protected $version  = '3';	
	protected $API_URL = "https://online.atol.ru/possystem";
	
	protected function get_token_args( )
	{ 
		$headers["Authorization"] = 'Bearer '.base64_encode($this->option['login'].':'.$this->option['pass']);
		return [
			'method' => 'POST',
			'httpversion' => '1.1',
			'headers' => $headers,
		];	
	}
	
	protected function get_token_url( )
	{ 
		return $this->get_url( "getToken" );
	}
	
	protected function processing_received_token( $result )
	{ 
		if ( !isset($result['code']) || $result['code'] >= 2 ) 
		{		
			$this->set_error( $result['text'] );	
			return false;
		}	
		if ( !empty($result['token']) )
		{
			set_transient( 'application_access_token_'.$this->option['id'], $result['token'], $this->expiration );
			return $result['token'];
		}
		return false;
	}	
		
	public function preparation_request( $function, $params ) 
	{		
	//	$headers["Authorization"] = 'Basic '.base64_encode($this->option['login2'].':'.$this->option['pass2']);			
		$headers["Cache-Control"] = 'no-cache';	
	//	$headers["Content-type"] = 'application/json';			
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => json_encode($params),
		);	
		$result = $this->send_request( $function, $args );
		return $result;
	}
	
	/*
	operation: тип операции, которая должна быть выполнена. Возможные типы операция:
	* sell: чек «Приход»;
	* sell_refund: чек «Возврат прихода»;
	* sell_correction: чек «Коррекция прихода»;
	* buy: чек «Расход»;
	* buy_refund: чек «Возврат расхода»;
	* buy_correction: чек «Коррекция расхода»
	*/
	private function register_document( $operation, $params )
	{	
		$params['tokenid'] = $this->get_token();
		if ( empty($params['tokenid']) )
			return false;
			
		$result = $this->preparation_request( "group_code/{$operation}", $params );
		if ( !isset($result['code'] ) || $result['code'] >= 2 ) 
		{		
			$this->set_error( $result['text'] );	
			return false;
		}	
		return $result;
	}	
	
	protected function get_data_refund( $id )
	{						
		$document = usam_get_document( $id );
		$products = usam_get_products_document( $id );				
	
		$data = array( 'totalprice' => $document['sum'], 'date_insert' => $document['date_insert'], 'products' => array() );
		
		$product_taxes = usam_get_document_product_taxes( $id ); 
		$taxes = array();
		foreach ( $product_taxes as $product_tax ) 
		{
			if ( $product_tax->is_in_price == 0 )
			{
				$taxes[$product_tax->product_id] = isset($taxes[$product_tax->product_id])?$taxes[$product_tax->product_id]:0;
				$taxes[$product_tax->product_id] += $product_tax->tax;
			}
		}			
		foreach ( $products as $product ) 
		{
			$tax = !empty($taxes[$product->product_id])?$taxes[$product->product_id]:0;
			$sum = ($product->price + $tax) * $product->quantity;		
			
			$data['products'][] = array( 
				"sum" => usam_string_to_float( $sum ),
				"tax" => $this->option['tax'],
				"tax_sum" => usam_string_to_float( $tax * $product->quantity ),
				"name" => $product->name,
				"price" => usam_string_to_float( $product->price ),
				"quantity" => $product->quantity,
			);
		}	
		//$data['phone'] = 
	//	$data['email'] = 
		$params = $this->get_send_data( $data );	
		return $params;
	}		

	protected function get_data_sell( $order_id )
	{						
		$order_data = usam_get_order( $order_id );	
		$order_products = usam_get_products_order( $order_id );			
		$product_taxes = usam_get_order_product_taxes( $order_id ); 
		$taxes = array();
		foreach( $product_taxes as $product_tax ) 
		{
			if ( $product_tax->is_in_price == 0 )
			{
				if ( !isset($taxes[$product_tax->product_id]) )
					$taxes[$product_tax->product_id][$product_tax->unit_measure] = 0;
				$taxes[$product_tax->product_id][$product_tax->unit_measure] += $product_tax->tax;
			}
		}			
		$data = array( 'totalprice' => $order_data['totalprice'], 'date_insert' => $order_data['date_insert'], 'products' => array() );
		foreach( $order_products as $product ) 
		{
			$tax = !empty($taxes[$product->product_id][$product_tax->unit_measure])?$taxes[$product->product_id][$product_tax->unit_measure]:0;
			$sum = ($product->price + $tax) * $product->quantity;			
			$data['products'][] = array( 
				"sum" => usam_string_to_float( $sum ),
				"tax" => $this->option['tax'],
				"tax_sum" => usam_string_to_float( $tax * $product->quantity ),
				"name" => $product->name,
				"price" => usam_string_to_float( $product->price ),
				"quantity" => $product->quantity,
			);
		}	
		if ( usam_is_type_payer_company( $order_data['type_payer'] ) )
		{
			$data['email'] = usam_get_order_metadata( $order_id, 'company_email' );
			$data['phone'] = usam_get_order_metadata( $order_id, 'company_phone' );	
		}
		else
		{
			$data['email'] = usam_get_order_metadata( $order_id, 'billingemail' );	
			$data['phone'] = usam_get_order_metadata( $order_id, 'billingphone' );
		}
		$params = $this->get_send_data( $data );	
		return $params;
	}
	
	/*
	payment_type - Вид оплаты. Возможные значения:
	* «0» – наличными;
	* «1» – электронными,
	* «2» – предварительная оплата (аванс);
	* «3» – последующая оплата (кредит);
	* «4» – иная форма оплаты (встречное предоставление);
	* «5» – «9» – расширенные типы оплаты. Для каждого фискального типа оплаты можно указать расширенный тип оплаты.
	
	Устанавливает номер налога в ККТ. Перечисление со значениями:
	* «none» – без НДС;
	* «vat0» – НДС по ставке 0%;
	* «vat10» – НДС чека по ставке 10%;
	* «vat18» – НДС чека по ставке 18%;
	* «vat110» – НДС чека по расчетной ставке 10/110;
	* «vat118» – НДС чека по расчетной ставке 18/118.
	
	type  Вид оплаты. Возможные значения: «1» – электронный;
	*/		
	protected function get_send_data( $data )
	{	
		$export = array( 
			"timestamp" => date( "d.m.Y H:i:s", strtotime($data['date_insert']) ),
			"total" => usam_string_to_float($data['totalprice']),
			"service" => array(
				"inn"             => $this->option['inn'],				
				"payment_address" => $this->option['address'],
				"callback_url"    => $this->callback_url,
			),
			"receipt" => array(
				"items" => $data['products'],
			),		
			"payments" => array(
				"sum"  => usam_string_to_float($data['totalprice']),
				"type" => 1,
			),	
			"attributes" => array(				
				"sno"   => $this->option['sno'],
				"email" => !empty($data['email'])?$data['email']:'',
				"phone" => !empty($data['phone'])?$data['phone']:'',
			),	
		);		
		return $export;
	}
	
	protected function get_default_option( ) 
	{
		return ['group_code' => '', 'sno' => '', 'tax' => '', 'inn' => '', 'address' => ''];
	}
		
	public function display_form() 
	{				
		?>	
		<div class="edit_form" > 
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_login'><?php esc_html_e( 'Логин', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_login' name="login" value="<?php echo $this->option['login']; ?>">
				</div>
			</div>
				<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_pass'><?php esc_html_e( 'Пароль', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><?php usam_get_password_input( $this->option['password'], ['name' => 'password', 'id' => 'option_pass']); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_group_code'><?php esc_html_e( 'Код группы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_group_code' name="group_code" value="<?php echo $this->option['group_code']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sno'><?php esc_html_e( 'Налоговая система', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="sno" id='option_sno'>
						<option value="osn" <?php selected( $this->option['sno'], 'osn') ?>><?php esc_html_e( 'общая', 'usam'); ?></option>
						<option value="usn_income" <?php selected( $this->option['sno'], 'usn_income') ?>><?php esc_html_e( 'упрощенная', 'usam'); ?></option>
						<option value="usn_income_outcome" <?php selected( $this->option['sno'], 'usn_income_outcome') ?>><?php esc_html_e( 'пурощенная (доходы минус расходы)', 'usam'); ?></option>
						<option value="envd" <?php selected( $this->option['sno'], 'envd') ?>><?php esc_html_e( 'единый налог на вмененный доход', 'usam'); ?></option>
						<option value="esn" <?php selected( $this->option['sno'], 'esn') ?>><?php esc_html_e( 'единый сельскохозяйственный налог', 'usam'); ?></option>
						<option value="patent" <?php selected( $this->option['sno'], 'patent') ?>><?php esc_html_e( 'патентная', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_tax'><?php esc_html_e( 'Номер налога в ККТ', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="tax" id='option_tax'>
						<option value="none" <?php selected( $this->option['tax'], 'none') ?>><?php esc_html_e( 'без НДС', 'usam'); ?></option>
						<option value="vat0" <?php selected( $this->option['tax'], 'vat0') ?>><?php esc_html_e( 'НДС по ставке 0%', 'usam'); ?></option>
						<option value="vat10" <?php selected( $this->option['tax'], 'vat10') ?>><?php esc_html_e( 'НДС чека по ставке 10%', 'usam'); ?></option>
						<option value="vat18" <?php selected( $this->option['tax'], 'vat18') ?>><?php esc_html_e( 'НДС чека по ставке 18%', 'usam'); ?></option>
						<option value="vat110" <?php selected( $this->option['tax'], 'vat110') ?>><?php esc_html_e( 'НДС чека по расчетной ставке 10/110', 'usam'); ?></option>
						<option value="vat118" <?php selected( $this->option['tax'], 'vat118') ?>><?php esc_html_e( 'НДС чека по расчетной ставке 18/118', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_inn'><?php esc_html_e( 'ИНН организации', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" name="inn" id='option_inn' value="<?php echo $this->option['inn']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_address'><?php esc_html_e( 'Адрес места расчетов', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_address" name="address" value="<?php echo $this->option['address']; ?>">
				</div>
			</div>
		</div>
		<?php
	}
	
	public function save_form( ) 
	{ 
		$metas = [];	
		$metas['group_code'] = isset($_POST['group_code'])?sanitize_text_field($_POST['group_code']):'';		
		$metas['sno'] = isset($_POST['sno'])?sanitize_text_field($_POST['sno']):'';
		$metas['tax'] = isset($_POST['tax'])?sanitize_text_field($_POST['tax']):'';		
		$metas['inn'] = isset($_POST['inn'])?sanitize_text_field($_POST['inn']):'';		
		$metas['address'] = isset($_POST['address'])?sanitize_text_field($_POST['address']):'';		
		foreach( $metas as $meta_key => $meta_value)
		{	
			usam_update_application_metadata($this->id, $meta_key, $meta_value);
		}	
	}
	
	public function order_paid( $purchase_log ) 
	{
		$order_id = $purchase_log->get( 'id' );
		$params = $this->get_data_sell( $order_id );			
		$this->register_document( 'sell', $params );
	}
	
	public function sell_refund( $document_id, $current_status, $previous_status, $t ) 
	{
		if ( $t->get('type') == 'check_return' && $current_status == 'approved' )
		{
			$params = $this->get_data_refund( $document_id );			
			$this->register_document( 'sell_refund', $params );
		}
	}
	
	public function service_load() 
	{
		add_action( 'usam_order_paid', [$this, 'order_paid']);	
		add_action( 'usam_update_document_status', [$this, 'sell_refund'], 10, 4 );
	}
}