<?php
/*
Printing Forms:Печать товаров
type:product
object_type:product
object_name:product
Description: Печать товаров из списка товаров
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
	<title><?php echo esc_html__('Печать товара', 'usam'); ?></title>
	<style type="text/css">
		body {font-family:"Helvetica Neue", Helvetica, Arial, Verdana, sans-serif;}
		h1 {font-size:14px;}
		*{font-size:12px;}
		h2 {color: #333;}
		#wrapper {margin:0 auto; width:95%;}	
		table {border:1px solid #000; border-collapse:collapse;	margin-top:1em; width:100%;	}
		th {background-color:#efefef; text-align:left;}
		th, td { padding:5px;}
		#print-items td.amount {text-align:right; }
		td, tbody th { border-top:1px solid #ccc; }
		.column_product_cat,
		.column_product_sku,
		.column_product_title{text-align:left;}
		.column_product_price{white-space: nowrap;}
		tfoot{background-color:#efefef;}		
	</style>
</head>
<body <?php echo $print; ?> style="margin: 0pt; padding: 15pt; width: 555pt; background: #ffffff">	
	<div id="wrapper">
		<h1><?php echo get_bloginfo('name'); ?> - <span><?php esc_html_e( 'Товары', 'usam'); ?></span></h1>		
		<table id="product">
			<thead>
				<tr>
					<th class="column_product_n"><?php echo esc_html_x( 'Номер', 'printing products', 'usam'); ?></th>
					<th class="column_product_sku"><?php echo esc_html_x( 'Артикул', 'printing products', 'usam'); ?></th>
					<th class="column_product_title"><?php echo esc_html_x( 'Имя', 'printing products', 'usam'); ?></th>
					<th class="column_product_price"><?php echo esc_html_x( 'Цена', 'printing products', 'usam'); ?></th>					
					<th class="column_product_stock"><?php echo esc_html_x( 'Остаток', 'printing products', 'usam'); ?></th>
					<th class="column_product_cat"><?php echo esc_html_x( 'Категория', 'printing products', 'usam'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$i = 0;				
				$totalprice = 0;
				$allstock = 0;
				if ( !empty($this->id) )
					$args = array( 'post__in' => $this->id );
				else
					$args = array( 'posts_per_page' => 20 );
				
				$products = usam_get_products( $args );
				$totalprice = 0;
				foreach( $products as $product )
				{
					$terms = wp_get_object_terms( $product->ID , 'usam-category' );		
					$cat = array();
					foreach ($terms as $term) 												
						$cat[] = $term->name;
					$i++;
					$price = usam_get_product_price( $product->ID );
					$stock = usam_get_product_stock( $product->ID, 'stock' );
					$allstock += $stock;
					$totalprice += $price;
					?>
					<tr>
						<td class="column_product_n"><?php echo $i; ?></td>
						<td class="column_product_sku"><?php echo usam_get_product_meta( $product->ID, 'sku' ); ?></td>
						<td class="column_product_title"><?php echo $product->post_title; ?></td>
						<td class="column_product_price"><?php echo usam_get_formatted_price( $price ); ?></td>						
						<td class="column_product_stock"><?php echo $stock; ?></td>
						<td class="column_product_cat"><?php echo implode(', ',$cat); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
			<tfoot>	
				<tr class="usam_purchaselog_start_totals">
					<td><?php echo $i ?></td>
					<td colspan="2"></td>	
					<td class="column_product_price"><?php echo usam_get_formatted_price( $totalprice ); ?></td>	
					<td class="amount"><?php echo $allstock ?></td>					
					<td></td>
				</tr>
			</tfoot>
		</table>		
	</div>
</body>
</html>