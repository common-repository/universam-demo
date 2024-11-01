<?php 
/* 
Описание: Шаблон отображения выбора разных единиц измерения при покупки. Шт, коробки и т.д.
*/
?>
<?php
$additional_units = usam_get_product_property( $product_id, 'additional_units' );
if ( !empty($additional_units) )
{
	$stock = usam_product_remaining_stock( $product_id );
	$main_unit = usam_get_product_property($product_id, 'unit');		
	?>
	<div class="selection_list">
		<div class="selection_list__title"><span class="js-unit-measure" unit_measure="<?php echo usam_get_product_property($product_id, 'unit_measure_code'); ?>"><?php echo usam_get_product_unit_name($product_id); ?></span><?php usam_svg_icon("angle-down-solid"); ?></div>
		<div class="selection_list__items">
			<div class="selection_list__item hide js-product-unit" unit_measure="<?php echo usam_get_product_property( $product_id, 'unit_measure_code' ); ?>" max="<?php echo $stock; ?>" step='<?php echo usam_get_product_property( $product_id, 'unit' ); ?>' unit_price="<?php echo usam_get_product_price_currency( $product_id, false ); ?>"><?php echo usam_get_product_unit_name( $product_id ); ?></div>
			<?php
			$price = usam_get_product_price( $product_id );
			foreach( $additional_units as $additional_unit )
			{
				$unit = usam_get_unit_measure($additional_unit['unit_measure']);
				?>
				<div class="selection_list__item js-product-unit" unit_measure="<?php echo $additional_unit['unit_measure']; ?>" step="<?php echo usam_is_weighted_product( $product_id )?0.5:1; ?>" max="<?php echo $stock/$additional_unit['unit']; ?>" unit_price="<?php echo usam_get_formatted_price( ($price/$main_unit)*$additional_unit['unit'] ); ?>"><?php echo $unit['title']; ?></div><?php
			}
			?> 
		</div>	
	</div>	
	<?php
}
?> 