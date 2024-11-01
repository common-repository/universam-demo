<?php
/*
Printing Forms:Печать QR-кода добавления в корзину товара
type:product
object_name:product
Description: Печать QR-кодов добавления в корзину из списка товаров
Shortcode: 
*/

if ( $this->edit )
{
	$print = '';
}
else
	$print = 'onload="window.print()"';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php echo esc_html__('Печать QR-кода добавления в корзину товара', 'usam'); ?></title>	
	<?php $this->style(); ?>
	<style type="text/css">
		body {font-family:"Helvetica Neue", Helvetica, Arial, Verdana, sans-serif; margin:0;}
		h1 {font-size:14px;}
		*{font-size:12px;}
		h2 {color: #333;}
		#wrapper {margin:0 auto; width:100%;}	
		table {border:none; border-collapse:collapse; margin-bottom:1em; width:100%;	}
		th {background-color:#efefef; text-align:left;}
		th, td { padding:5px; border:none;}
		#print-items td.amount {text-align:right; }
		.column_product_cat,
		.column_product_sku,
		.column_product_title{text-align:left;}
		.column_product_price{white-space: nowrap;}
		tfoot{background-color:#efefef;}
		.price{float: right; display: block; font-size:14px; font-weight:600;}
	</style>	
</head>
<body <?php echo $print; ?> style="margin: 0pt; padding:0pt; width:155pt; background: #ffffff">	
	<div id="wrapper">	
		<?php
		if ( !empty($this->id) )
			$args = array( 'post__in' => $this->id );
		else
			$args = array( 'posts_per_page' => 20 );				
		$args['stocks_cache'] = false;
		$args['prices_cache'] = false;
	
		$products = usam_get_products( $args );
		$args = usam_shop_requisites_shortcode();
		foreach( $products as $product )
		{						
			?>
			<table class="product" style="margin:5px;">
				<tr><td colspan = "2" class="column_product_title"><?php echo $product->post_title; ?></td></tr>
				<tr><td class="column_product_sku"><?php echo __("Артикул:","usam").": ".usam_get_product_meta( $product->ID, 'sku' ); ?></td><td class="price"><?php echo usam_get_product_price_currency($product->ID); ?></td></tr>						
				<tr><td colspan = "2" class="column_product_stock"><img class="qr" src="<?php echo usam_get_qr( add_query_arg(['product_id' => $product->ID, 'usam_action' => 'buy_product', 'empty' => 1, 'qr' => 'add_product_basket'] , usam_get_url_system_page('basket') ) ); ?>"></td></tr>
			</table>
			<p class="more" style="page-break-after: always;"></p>		
			<?php
		}
		?>		
	</div>
</body>
</html>