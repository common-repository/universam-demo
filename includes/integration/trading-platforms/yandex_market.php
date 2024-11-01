<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/trading_platforms.class.php' );
/*
  Name: Яндекс Маркет
  Icon: yandex-market
  https://yandex.ru/support/partnermarket/offers.html
 */
class USAM_Yandex_Market_Exporter extends USAM_Trading_Platforms_Exporter
{		
	protected $category_ids = [];
	
	protected function get_default_option( ) 
	{
		$default =  ['delivery' => 0, 'delivery-options' => [], 'pickup' => 0];
		$yandex_attributes = $this->get_yandex_attributes();
		foreach( $yandex_attributes as $key => $name )
			$default[$key] = '';
		return $default;		
	}
	
	protected function get_export_product( $post ) 
	{	
		$brand = get_the_terms($post->ID, 'usam-brands');	
		$price = usam_get_product_price( $post->ID, $this->rule['type_price'] );	
		if ( empty($price) )		
			return '';		
		$product_categories = get_the_terms($post->ID, 'usam-category');				
		$category_id = '';	
		if( empty($product_categories) )
			return '';	
		foreach( $product_categories as $category )		
		{
			$category_id = $category->term_id;
			$this->category_ids[] = $category_id;
			break;
		}		
		if ( !$category_id )		
			return '';	
		$available = usam_product_has_stock($post->ID) > 0?'true':'false';	
		$result = "<offer id='$post->ID' available='$available'>
						<url>".$this->get_product_url( $post->ID )."</url>
						<price>$price</price>";

		$attachments = usam_get_product_images( $post->ID );				
		foreach ($attachments as $attachment)
		{						
			$image = wp_get_attachment_image_src($attachment->ID, 'full');
			if ( $image )
				$result .= "<picture>".$image[0]."</picture>";
		}
		$product_attributes = usam_get_product_attributes_display( $post->ID, ['show_all' => true] );
		foreach ($product_attributes as $attribute)
		{
			if ( $attribute['parent'] && in_array($attribute['term_id'], $this->rule['product_characteristics']) )
				$result .= "<param name='".$attribute['name']."' unit=''>".$this->text_decode(implode(',',$attribute['value']))."</param>";
		}
		if ( !empty($this->rule['description']) )
			$result .= "<sales_notes>".htmlspecialchars($this->rule['description'])."</sales_notes>";	
		$delivery = !empty($this->rule['delivery'])?'true':'false';
		$pickup = !empty($this->rule['pickup'])?'true':'false';						
		if( usam_check_product_type_sold('product', $post->ID) )
		{
			$weight = usam_get_product_weight( $post->ID, false, false );	
			if ( !empty($weight) )
				$result .= "<weight>".$weight."</weight>";				
			$result .= "<barcode>".usam_get_product_meta( $post->ID, 'barcode' )."</barcode>";
		
			$width = usam_get_product_meta( $post->ID, 'width' );
			$height = usam_get_product_meta( $post->ID, 'height' );
			$length = usam_get_product_meta( $post->ID, 'length' );
			if ( $width && $height && $length )
				$result .= "<dimensions>".usam_string_to_float($width).'/'. usam_string_to_float($height).'/'. usam_string_to_float($length)."</dimensions>";
		}
		if ( !empty($brand[0]) )
			$result .= "<vendor>".$brand[0]->name."</vendor>";		
			
		$yandex_attributes = $this->get_yandex_attributes();
		foreach( $yandex_attributes as $key => $data )
		{
			if ( $data['type'] == 'simple' && !empty($this->rule[$key]) )
			{
				$attribute = usam_get_product_attribute($post->ID, $this->rule[$key]);
				if ( $attribute )
					$result .= "<".$key.">".$attribute."</".$key.">";	
			}
		}
		if( !empty($this->rule['condition']) )
		{
			if( usam_get_product_attribute($post->ID, $this->rule['condition']) )
			{				
				$condition_type = usam_get_product_attribute_code($post->ID, $this->rule['condition_type']);
				$condition_quality = usam_get_product_attribute_code($post->ID, $this->rule['condition_reason']);				
				$result .= "<condition type='".($condition_type?$condition_type:'preowned')."'>
					<quality>".($condition_quality?$condition_quality:'good')."</quality>
					<reason><![CDATA[".usam_get_product_attribute($post->ID, $this->rule['condition_reason'])."]]></reason>
				</condition>";
			}
		}
		$product_description = !empty($this->rule['product_description']) ? usam_get_product_attribute_display( $post->ID, $this->rule['product_description'] ) : $post->post_excerpt;
		$result .= "<currencyId>".$this->rule['currency']."</currencyId>
					<categoryId>{$category_id}</categoryId>
					<pickup>".$pickup."</pickup>	
					<delivery>".$delivery."</delivery>
					<vendorCode>".usam_get_product_meta( $post->ID, 'sku' )."</vendorCode>						
					<name>".$this->get_product_title( $post )."</name>
					<description><![CDATA[".$this->text_decode( $product_description )."]]></description>	
					<count>".usam_product_remaining_stock( $post->ID )."</count>
				</offer>";				
		return $result;				
	}
	
	protected function set_category( $category )
	{
		$parentId = $category->parent ? "parentId='{$category->parent}'" : '';
		return '<category id="'.$category->term_id.'" '.$parentId.'>'.$category->name.'</category>'."\n";
	}
	
	protected function export_categories()
	{		
		$html = '';
		$ids = [];
		if ( $this->category_ids )
		{			
			foreach( $this->category_ids as $category_id )
			{				
				$term_ids = usam_get_ancestors( $category_id, "usam-category" );
				$term_ids = array_reverse($term_ids);				
				$term_ids[] = $category_id;
				$ids = array_merge( $ids, $term_ids );
			}	
			$ids = array_unique($ids);
		}		
		if ( $ids )
		{
			$category_list = get_terms(['hide_empty' => 0, "taxonomy" => "usam-category", 'include' => $ids, 'orderby' => 'include']);
			foreach( $ids as $id )
			{
				foreach( $category_list as $category )
				{
					if ( $id == $category->term_id )
						$html .= $this->set_category( $category );
				}
			}
		}
		return $html;
	}	
	
	protected function get_export_file( $offers ) 
	{
		$requisites = usam_shop_requisites_shortcode();		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<yml_catalog date="'.date_i18n("c").'">
				<shop>
					<name>'. $this->text_decode( get_option( 'blogname' ) ) ."</name>
					<company>".$this->text_decode($requisites['full_company_name'])."</company>
					<url>".home_url()."</url>
					<currencies>
						<currency id='".$this->rule['currency']."' rate='1'/>
					</currencies>						
					<categories>".$this->export_categories()."</categories>";	
					if ( !empty($this->rule['delivery-options']) )
					{
						$xml .= '<delivery-options>';
						foreach($this->rule['delivery-options'] as $option) 	
							$xml .= '<option cost="'.$option['cost'].'" days="'.$option['days'].'" order-before="'.$option['before'].'"/>';
						$xml .= "</delivery-options>";	
					}
				$xml .= "<offers>".$offers."</offers></shop>
				</yml_catalog>";
		
		return $xml;	
	}
	
	function get_yandex_attributes( ) 
	{
		return [
			'period-of-validity-days' => ['title' => __( 'Срок годности', 'usam'), 'type' => 'simple', 'id' => 'period-of-validity-days'], 
			'min-quantity' => ['title' => __( 'Минимальное количество товара', 'usam'), 'type' => 'simple'], 
			'step-quantity' => ['title' => __( 'Квант продажи', 'usam'), 'type' => 'simple'],	
			'warranty-days' => ['title' => __( 'Гарантийный срок', 'usam'), 'type' => 'simple'],	
			'comment-life-days' => ['title' => __( 'Комментарий к сроку службы', 'usam'), 'type' => 'simple'],	
			'service-life-days' => ['title' => __( 'Срок службы', 'usam'), 'type' => 'simple'],
			'period-of-validity-days' => ['title' => __( 'Срок годности', 'usam'), 'type' => 'simple'],
			'comment-validity-days' => ['title' => __( 'Комментарий к сроку годности', 'usam'), 'type' => 'simple'],			
			'country_of_origin' => ['title' => __( 'Страна производства', 'usam'), 'type' => 'simple'],		
			'box-count' => ['title' => __( 'Товар занимает больше одного места', 'usam'), 'type' => 'simple'],
			'condition' => ['title' => __( 'Признак б/у товара', 'usam'), 'type' => 'complex'],
			'condition_type' => ['title' => __( 'Тип б/у', 'usam'), 'type' => 'complex'],
			'condition_quality' => ['title' => __( 'Состояние, внешний вид товара', 'usam'), 'type' => 'complex'],
			'condition_reason' => ['title' => __( 'Описание следов использования или дефектов', 'usam'), 'type' => 'complex'],
		];
	}
	
	function get_form( ) 
	{		
		$this->display_form_campaign();		
		$yandex_attributes = $this->get_yandex_attributes();
		?>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='option_description'><?php esc_html_e( 'Описание', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<textarea rows='10' autocomplete='off' cols='40' name='description' id='market_description' ><?php echo !empty($this->rule['description'])?$this->rule['description']:''; ?></textarea><p class='description'><?php _e( 'Используйте описание, чтобы указать: – минимальную сумму заказа (обязательно); – минимальную партию товара (обязательно); – необходимость предоплаты (обязательно); – варианты оплаты (необязательно); – условия акции (необязательно). Содержание элемента должно соответствовать требованиям к рекламным материалам, размещаемым на Маркете.', 'usam'); ?></p>
			</div>
		</div>	
		<?php 
		foreach ( $yandex_attributes as $key => $attribute ) 
		{			
			?>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php echo $attribute['title']; ?>:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data['<?php echo $key; ?>']=$event.id" :lists="attributes" :selected="data['<?php echo $key; ?>']" :none="'<?php _e( 'Не требуется', 'usam'); ?>'"></select-list>
					<input type="hidden" name="<?php echo $key; ?>" v-model="data['<?php echo $key; ?>']">
				</div>
			</div>
			<?php
		}
		?>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='delivery'><?php esc_html_e( 'Возможность курьерской доставки', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="checkbox" name="delivery" id="delivery"  value="1" <?php checked( $this->rule['delivery'], 1 ); ?>/>
			</div>
		</div>					
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label><?php esc_html_e( 'Курьерская доставка', 'usam'); ?>:</label></div>
			<div class ="option usam_table_container">
				<table class = "table_rate">
					<thead>
						<tr>
							<th><?php _e('Стоимость доставки', 'usam'); ?></th>
							<th><?php _e('Срок в днях', 'usam'); ?></th>
							<th><?php _e('Время', 'usam'); ?></th>
						</tr>
					</thead>
					<tbody>					
						<?php if ( !empty($this->rule['delivery-options']) ): ?>
							<?php
								foreach( $this->rule['delivery-options'] as $option )
									$this->output_row( $option );							
							?>
						<?php else: ?>
							<?php $this->output_row(); ?>
						<?php endif ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='pickup'><?php esc_html_e( 'Возможность самовывоза', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="checkbox" name="pickup" id="pickup"  value="1" <?php checked( $this->rule['pickup'], 1 ); ?>/>
			</div>
		</div>
		<?php
	}	
	
	private function output_row( $option = array() ) 
	{		
		$option = empty($option)?array( 'cost' => 0,'days' => 0, 'before' => 0 ):$option;
		?>
			<tr>
				<td>
					<input type="text" name="delivery_options[cost][]" value="<?php echo $option['cost']; ?>" />	
				</td>
				<td>
					<input type="text" name="delivery_options[days][]" value="<?php echo $option['days']; ?>" />		
				</td>
				<td>
					<input type="text" name="delivery_options[before][]" value="<?php echo $option['before']; ?>" size="3" />		
				</td>
				<td class="column_actions">
					<?php 
					usam_system_svg_icon("plus", ["class" => "action add"]);
					usam_system_svg_icon("minus", ["class" => "action delete"]);
					?>
				</td>
			</tr>
		<?php
	}
	
	function save_form() 
	{ 
		$new_rule['description'] = !empty($_POST['description'])?sanitize_textarea_field(stripslashes($_REQUEST['description'])):'';		
		$yandex_attributes = $this->get_yandex_attributes();
		foreach( $yandex_attributes as $key => $name )
			$new_rule[$key] = !empty($_POST[$key])?sanitize_text_field($_POST[$key]):'';
		$new_rule['delivery'] = !empty($_POST['delivery'])?1:0;
		$new_rule['pickup'] = !empty($_POST['pickup'])?1:0;	
		$delivery_options = array();		
		if ( !empty($_POST['delivery_options']) )
		{							
			foreach($_POST['delivery_options']['cost'] as $id => $cost )
			{	
				if ( empty($cost) )				
					continue;
				
				if ( isset($_POST['delivery_options']['before'][$id]) )
					$before = $_POST['delivery_options']['before'][$id];
				else
					continue;
				if ( isset($_POST['delivery_options']['days'][$id]) )
					$days = $_POST['delivery_options']['days'][$id];
				else
					continue;
				
				$delivery_options[] = ['cost' => $cost, 'before' => $before, 'days' => $days];
			}		
		}		
		$new_rule['delivery-options'] = $delivery_options;
		return $new_rule;
	}
}
?>