<?php	
require_once( USAM_FILE_PATH . '/admin/includes/form/edit-form-discount.php' );
class USAM_Form_fix_price_discount extends USAM_Form_Rule_Discount
{		
	protected function get_data_tab(  )
	{							
		if ( $this->id != null )
			$this->data = usam_get_discount_rule( $this->id ); 
		else
			$this->data = ['name' => '', 'description' => '', 'active' => 0, 'term_slug' => '', 'priority' => 100, 'code' => '', 'end' => 0, 'discount' => 0, 'dtype' => 'p', 'start_date' => '', 'end_date' => '', 'type_rule' => 'fix_price'];
		
		add_filter( 'usam_product_importer_columns', function($a) { 
			return ['sku' => __('Артикул', 'usam'), 'barcode' => __('Штрих-код', 'usam'), 'discount_price' => __('Цена со скидкой', 'usam')]; 		
		});
	}
	
	public function label_product( )
	{
		$label_name = usam_get_discount_rule_metadata($this->id, 'label_name');
		$label_color = usam_get_discount_rule_metadata($this->id, 'label_color');
		?>		
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_label_name'><?php esc_html_e( 'Название стикера', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" autocomplete="off" id="option_label_name" value="<?php echo $label_name; ?>" name="label_name"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_label_color'><?php esc_html_e( 'Цвет стикера', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" autocomplete="off" id="option_label_color" value="<?php echo $label_color; ?>" name="label_color"/>
				</div>
			</div>
		</div>		
		<?php
	}	
		
	function display_left()
	{	
		$this->titlediv( $this->data['name'] );		
		$this->add_box_description( $this->data['description'] );
		usam_add_box( 'usam_options', __('Настройки правила','usam'), array( $this, 'box_options' ) );
		usam_add_box( 'usam_label_product', __('Метка на товар в каталоге','usam'), array( $this, 'label_product' ) );			
		$columns = [
			'n'         => __('№', 'usam'),
			'title'     => __('Товары', 'usam'),
			'discount_price'     => __('Цена со скидкой', 'usam'),
			'discount_displayed' => __('Скидка', 'usam'),
			'price' => __('Текущая цена', 'usam'),
			'delete'    => '',
		];
		$this->register_modules_products();		
		?>
		<usam-box :id="'section_products'" :title="'<?php _e( 'Товары', 'usam'); ?>'">
			<template v-slot:body>				
				<table-products :columns='<?php echo json_encode( $columns ); ?>' :table_name="'fix_price'" :loaded="$root.loaded" :items="products" @change="formattingProduct" :column_names='<?php echo json_encode( usam_get_columns_product_table() ); ?>' :query="query">
					<template v-slot:tbody="slotProps">
						<tr v-if="slotProps.products.length" v-for="(product, k) in slotProps.products">
							<td class="column-n">{{k+1}}</td>
							<td class="column-title">
								<div class="product_name_thumbnail">
									<div class="product_image image_container viewer_open" @click="slotProps.viewer(k)">
										<img :src="product.small_image">
									</div>
									<div class="product_name">	
										<a :href="product.url" v-html="product.post_title"></a>
										<p class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="product.sku"></span></p>
									</div>							
								</div>				
							</td>
							<td :class="'column-'+column" v-for="column in slotProps.user_columns"><span v-html="product[column]"></span></td>
							<td class="column-discount_price">
								<input size='4' type='text' :name="'products['+product.product_id+']'" v-model="product.discount_price">
							</td>
							<td class="column-discount_displayed" v-html="slotProps.formatted_number(100-product.discount_price*100/product.price)+'%'"></td>
							<td class="column-price" v-html="product.price"></td>						
							<td class="column-delete">					
								<a class="action_delete" href="" @click="slotProps.delElement($event, k)"></a>
							</td>	
						</tr>
					</template>
				<template v-slot:tfoot="slotProps"></template>				
			</table-products>	
			</template>
		</usam-box>
		<?php
    }
}
?>