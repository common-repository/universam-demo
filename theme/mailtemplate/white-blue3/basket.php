<div class ="usam_transaction_results_table_wrapper">
<table class="usam_transaction_results_table table_message" style='width:100%; border-spacing:0; border:none'>
	<thead class="transaction_results_thead">
		<td class="transaction_results_cell" style="text-align:left;padding:5px 10px;text-transform: uppercase; border-width: 0 0 3px 0; border-style: solid; border-color: #ececec; font-size: 0.7rem; color:#242424;"></td>
		<td class="transaction_results_cell" style="text-align:left;padding:5px 10px;text-transform: uppercase; border-width: 0 0 3px 0; border-style: solid; border-color: #ececec; font-size: 0.7rem; color:#242424;"><?php _e('Наименование', 'usam'); ?></td>
		<td class="transaction_results_cell" style="text-align:right;padding:5px 10px;text-transform: uppercase; border-width: 0 0 3px 0; border-style: solid; border-color: #ececec; font-size: 0.7rem; color:#242424;"><?php _e('Цена', 'usam'); ?></td>
		<td class="transaction_results_cell" style="text-align:right;padding:5px 10px;text-transform: uppercase; border-width: 0 0 3px 0; border-style: solid; border-color: #ececec; font-size: 0.7rem; color:#242424;"><?php _e('Количество', 'usam'); ?></td>
		<td class="transaction_results_cell" style="text-align:right;padding:5px 10px;text-transform: uppercase; border-width: 0 0 3px 0; border-style: solid; border-color: #ececec; font-size: 0.7rem; color:#242424;"><?php _e('Сумма', 'usam'); ?></td>
	</thead>
	<tbody>
		<?php foreach( $products as $item ) :	?>
			<tr class="transaction_results_row">
				<td class="transaction_results_cell" style="text-align:left;padding:5px 10px;border:none">
					<img src='<?php echo usam_get_product_thumbnail_src($item->product_id, 'small-product-thumbnail'); ?>' width="100" height="100" alt='<?php echo $item->name; ?>'>
				</td>
				<td class="transaction_results_cell" style="text-align:left;padding:5px 10px;border:none"><?php echo $item->name; ?></td>
				<td class="transaction_results_cell" style="text-align:right;padding:5px 10px;border:none"><?php echo usam_get_formatted_price( $item->price ); ?></td>
				<td class="transaction_results_cell" style="text-align:right;padding:5px 10px;border:none"><?php echo $item->quantity; ?></td>
				<td class="transaction_results_cell" style="text-align:right;padding:5px 10px;border:none"><?php echo usam_get_formatted_price( $item->quantity * $item->price ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>	
</div>	