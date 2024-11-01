<?php
class Usam_Admin_Permalink_Settings 
{	
	public function __construct() 
	{
		$this->settings_init();
		$this->settings_save();
	}
	
	public function settings_init() 
	{		
		add_settings_section( 'usam-permalink', __('База постоянных ссылок на товар', 'usam'), array(&$this, 'display_settings'), 'permalink' );
		add_settings_field(	'usam_product_category_slug', __('База категорий товара', 'usam'),  array(&$this, 'product_category_slug_input'),  'permalink', 'optional' );
		add_settings_field(	'usam_product_category_sale_slug', __('База категорий скидок товара', 'usam'),  array(&$this, 'product_category_sale_slug_input'), 'permalink', 'optional' );
		add_settings_field(	'usam_product_selection_slug', __('База подборок товара', 'usam'),  array(&$this, 'product_selection_slug_input'), 'permalink', 'optional' );		
		add_settings_field(	'usam_product_brand_slug_input', __('База брендов товара', 'usam'),  array(&$this, 'product_brand_slug_input'),  'permalink', 'optional' );
		add_settings_field(	'usam_catalog_slug_input', __('База каталогов товара', 'usam'),  array(&$this, 'catalog_slug_input'), 'permalink', 'optional' );
		add_settings_field('usam_product_tag_slug', __('База меток товара', 'usam'), array(&$this, 'product_tag_slug_input'), 'permalink','optional' );
		add_settings_field('usam_product_attribute_slug', __('База атрибутов товара', 'usam'), array(&$this, 'product_attribute_slug_input'), 'permalink',  'optional' );
		add_settings_field('usam_agreement_slug', __('База лицензионных соглашений', 'usam'), array(&$this, 'agreement_slug_input'), 'permalink',  'optional' );
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
			add_settings_field('usam_seller_slug_slug', __('База продавцов маркетплейса', 'usam'), [&$this, 'seller_slug_input'], 'permalink',  'optional' );
	}
	
	public function catalog_slug_input()
	{
		$permalinks = get_option( 'usam_permalinks' );
		$catalog_base = empty($permalinks['catalog_base']) ? 'catalog' : $permalinks['catalog_base'];
		?>
		<input name="usam_catalog" type="text" class="regular-text code" value="<?php echo esc_attr( $catalog_base ); ?>">
		<?php
	}
					
	public function agreement_slug_input()
	{
		$permalinks = get_option( 'usam_permalinks' );
		$agreement = empty($permalinks['agreement']) ? 'agreement' : $permalinks['agreement'];
		?>
		<input name="usam_agreement" type="text" class="regular-text code" value="<?php echo esc_attr( $agreement ); ?>">
		<?php
	}
		
	public function seller_slug_input()
	{
		$permalinks = get_option( 'usam_permalinks' );
		$seller_base = empty($permalinks['seller_base']) ? 'seller' : $permalinks['seller_base'];
		?>
		<input name="usam_seller" type="text" class="regular-text code" value="<?php echo esc_attr( $seller_base ); ?>">
		<?php
	}
		
	public function product_category_slug_input()
	{
		$permalinks = get_option( 'usam_permalinks' );
		$category_base = empty($permalinks['category_base']) ? 'product-category' : $permalinks['category_base'];
		?>
		<input name="usam_product_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $category_base ); ?>">
		<?php
	}
			
	public function product_selection_slug_input()
	{
		$permalinks = get_option( 'usam_permalinks' );
		$selection_base = empty($permalinks['selection_base']) ? 'selection' : $permalinks['selection_base'];
		?>
		<input name="usam_selection" type="text" class="regular-text code" value="<?php echo esc_attr( $selection_base ); ?>">
		<?php
	}
	
	public function product_category_sale_slug_input()
	{
		$permalinks = get_option( 'usam_permalinks' );
		$category_sale_base = empty($permalinks['category_sale_base']) ? 'category_sale' : $permalinks['category_sale_base'];
		?>
		<input name="usam_product_category_sale_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $category_sale_base ); ?>">
		<?php
	}
	
	public function product_brand_slug_input()
	{
		$permalinks = get_option( 'usam_permalinks' );
		$brand = empty($permalinks['brand_base']) ? 'brand' : $permalinks['brand_base'];
		?>
		<input name="usam_brand_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $brand ); ?>">
		<?php
	}
	
	public function product_tag_slug_input() 
	{
		$permalinks = get_option( 'usam_permalinks' );
		?>
		<input name="usam_product_tag_slug" type="text" class="regular-text code" value="<?php if ( isset($permalinks['tag_base'] ) ) echo esc_attr( $permalinks['tag_base'] ); ?>" placeholder="<?php echo esc_attr_x('product-tag', 'slug', 'usam') ?>">
		<?php
	}

	public function product_attribute_slug_input() 
	{
		$permalinks = get_option( 'usam_permalinks' );
		?>
		<input name="usam_product_attribute_slug" type="text" class="regular-text code" value="<?php if ( isset($permalinks['attribute_base'] ) ) echo esc_attr( $permalinks['attribute_base'] ); ?>"><code>/attribute-name/attribute/</code>
		<?php
	}

	public function display_settings() 
	{
		echo wpautop( __('Эти настройки контролируют постоянные ссылки, используемые для товаров. Настройки применяются только тогда, когда не используются постоянные ссылки "по умолчанию" выше.', 'usam') );

		$permalinks = get_option( 'usam_permalinks' );
		$product_permalink = $permalinks['product_base'];
		
		$structures = array(
			0 => '',
			1 => '/' . 'products',
			2 => '/' . 'products-page',
			3 => '/' . 'products/%product_cat%',
			4 => '/' . 'products/%sku%',
		);			
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" class="wctog" <?php checked( $structures[0], $product_permalink ); ?>> <?php _e( 'По умолчанию', 'usam'); ?></label></th>
					<td><code><?php echo esc_html( home_url() ); ?>/?product=sample-product</code></td>
				</tr>
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" class="wctog" <?php checked( $structures[1], $product_permalink ); ?>> <?php _e( 'Товар', 'usam'); ?></label></th>
					<td><code><?php echo esc_html( home_url() ); ?>/products/sample-product/</code></td>
				</tr>				
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[2] ); ?>" class="wctog" <?php checked( $structures[2], $product_permalink ); ?>> <?php _e( 'Страница товаров', 'usam'); ?></label></th>
					<td><code><?php echo esc_html( home_url() ); ?>/products-page/sample-product/</code></td>
				</tr>	
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[3] ); ?>" class="wctog" <?php checked( $structures[3], $product_permalink ); ?>> <?php _e( 'Категория и товар', 'usam'); ?></label></th>
					<td><code><?php echo esc_html( home_url() ); ?>/products/product-category/sample-product/</code></td>
				</tr>
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[4] ); ?>" class="wctog" <?php checked( $structures[4], $product_permalink ); ?>> <?php _e( 'Артикул', 'usam'); ?></label></th>
					<td><code><?php echo esc_html( home_url() ); ?>/products/sku/</code></td>
				</tr>
				<tr>
					<th><label><input name="product_permalink" id="usam_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array($product_permalink, $structures), false ); ?>>
						<?php _e( 'Произвольная база', 'usam'); ?></label></th>
					<td>
						<input name="product_permalink_structure" id="usam_permalink_structure" type="text" value="<?php echo esc_attr( $product_permalink ); ?>" class="regular-text code">
					</td>
				</tr>
			</tbody>
		</table>
		<script>
			jQuery( function() {
				jQuery('input.wctog').change(function() {
					jQuery('#usam_permalink_structure').val( jQuery( this ).val() );
				});

				jQuery('#usam_permalink_structure').focus( function(){
					jQuery('#usam_custom_selection').click();
				} );
			} );
		</script>
		<?php
	}
	
	public function settings_save()
	{
		global $wpdb;
		if ( ! is_admin() ) {
			return;
		}
		
		if ( isset($_POST['permalink_structure']) || isset($_POST['category_base']) && isset($_POST['product_permalink']) ) 
		{		
			$permalinks = get_option( 'usam_permalinks' );
			if ( ! $permalinks ) 
				$permalinks = array();
			$permalinks['category_sale_base'] = untrailingslashit(sanitize_text_field( $_POST['usam_product_category_sale_slug'] ));
			$permalinks['category_base']  = untrailingslashit(sanitize_text_field( $_POST['usam_product_category_slug'] ));
			$permalinks['brand_base']     = untrailingslashit(sanitize_text_field( $_POST['usam_brand_slug'] ));
			$permalinks['tag_base']       = untrailingslashit(sanitize_text_field( $_POST['usam_product_tag_slug'] ));
			$permalinks['attribute_base'] = untrailingslashit(sanitize_text_field( $_POST['usam_product_attribute_slug'] ));
			$permalinks['agreement']      = untrailingslashit(sanitize_text_field( $_POST['usam_agreement'] ));	
			$permalinks['catalog_base']   = untrailingslashit(sanitize_text_field( $_POST['usam_catalog'] ));		
			$permalinks['selection_base'] = untrailingslashit(sanitize_text_field( $_POST['usam_selection'] ));	
			if ( isset($_POST['usam_seller']) ) 
				$permalinks['seller_base']    = untrailingslashit(sanitize_text_field( $_POST['usam_seller'] ));
			
			$product_permalink = sanitize_text_field($_POST['product_permalink']);
			if ( $product_permalink == 'custom' ) 
			{
				$product_permalink = trim( sanitize_text_field( $_POST['product_permalink_structure'] ), '/' );
				if ( '%product_cat%' == $product_permalink ) {
					$product_permalink = 'products/' . $product_permalink;
				}			
				$product_permalink = '/' . $product_permalink;
			} 			
			elseif ( empty( $product_permalink ) ) 
			{
				$product_permalink = false;
			}

			$permalinks['product_base'] = untrailingslashit( $product_permalink );
	
			$product_page_id = usam_get_system_page_id('products-list');
			$shop_permalink = ( $product_page_id > 0 && get_post( $product_page_id ) ) ? get_page_uri( $product_page_id ) : 'shop';

			if ( $product_page_id && trim($permalinks['product_base'], '/') === $shop_permalink ) {
				$permalinks['use_verbose_page_rules'] = true;
			}
			update_option( 'usam_permalinks', $permalinks );			
		}
	}
}
return new Usam_Admin_Permalink_Settings();