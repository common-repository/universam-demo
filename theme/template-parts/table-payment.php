<?php
/*
Шаблон истории оплаты. Используется обычно в письме администратору
*/ 
?>
<div class ="usam_transaction_results_table_wrapper">
<table class="usam_transaction_results_table table_message" style='width:100%; border-spacing:0; border:none'>
	<?php if ( !empty( $headings ) ): ?>
		<thead class="transaction_results_thead">
			<th class = "table_message_th colum_number" style="padding:10px; text-transform: uppercase; font-size:16px; border-width:0 0 3px 0; border-style:solid; border-color:#ececec;"></th>
			<?php foreach ( $headings as $key => $heading ): ?>
				<th class="table_message_th colum_<?php echo esc_html( $key ); ?>" style="text-align:<?php echo $heading['alignment']; ?>; padding:10px; text-transform:uppercase; font-size:16px; border-width:0 0 3px 0; border-style:solid; border-color:#ececec;"><?php echo esc_html( $heading['name'] ); ?></th>
			<?php endforeach; ?>
		</thead>
	<?php endif; ?>
	<tbody>		
		<?php 
		$j = 1;	
		foreach ( $rows as $row ): 				
			?>				
			<tr class="transaction_results_row">
				<td class="transaction_results_cell colum_number" style="padding:10px; border:none;"><?php echo esc_html( $j ); ?></td>
				<?php foreach ( $headings as $key => $heading ):	?>
					<td class="transaction_results_cell colum_<?php echo esc_html( $key ); ?>" style="text-align:<?php echo $heading['alignment']; ?>;<?php echo !empty($heading['style'])?$heading['style']:''; ?>;padding:10px;border:none"><?php echo esc_html( $row[$key] ); ?></td>
				<?php endforeach; ?>
			</tr>
		<?php 
		$j++;
		endforeach; ?>
	</tbody>
</table>	
</div>	