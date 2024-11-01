<?php
/*
Шаблон список купленных товаров. Используется обычно в письме и на странице результаты покупки
*/ 
?>
<div class ="usam_transaction_results_table_wrapper">
<table class="usam_transaction_results_table table_message" style='width:100%; border-spacing:0; border:none'>
	<?php if ( !empty( $headings ) ): ?>
		<thead class="transaction_results_thead">
			<?php foreach ( $headings as $key => $heading ): ?>
				<th class="table_message_th colum_<?php echo esc_html( $key ); ?>" style="text-align:<?php echo $heading['alignment']; ?>; padding:5px 10px; text-transform:uppercase; font-size:16px; border-width:0 0 3px 0; border-style:solid; border-color:#ececec;"><?php echo esc_html( $heading['name'] ); ?></th>
			<?php endforeach; ?>
		</thead>
	<?php endif; ?>
	<tbody>		
		<?php 
		foreach ( $rows as $row ): 				
			?>				
			<tr class="transaction_results_row">
				<?php foreach ( $headings as $key => $heading ):	?>
					<td class="transaction_results_cell colum_<?php echo esc_html( $key ); ?>" style="text-align:<?php echo $heading['alignment']; ?>;<?php echo !empty($heading['style'])?$heading['style']:''; ?>;padding:5px 10px;border:none"><?php echo $row[$key]; ?></td>
				<?php endforeach; ?>
			</tr>
		<?php
		endforeach; ?>
	</tbody>
</table>	
</div>	