<?php $total_stock = usam_product_remaining_stock($this->id, 'stock'); ?>
<div class="edit_form">
	<?php if( $this->product_type !== 'variable' ) { ?>
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_attr_e( 'Запас не ограничен', 'usam'); ?>:</div>
		<div class ="edit_form__item_option"><input type='checkbox' name='not_limited[<?php echo $this->id; ?>]' value="1" v-model="data.not_limited"></div>
	</div>	
	<?php } ?>
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_attr_e( 'Максимальный запас', 'usam'); ?>:</div>
		<div class ="edit_form__item_option"><?php echo usam_get_product_stock($this->id, 'max_stock'); ?></div>
	</div>	
	<?php if ( usam_get_product_meta($this->id, 'balance_update') ) { ?>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label><?php esc_html_e( 'Последний обмен', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<?php echo usam_local_date( strtotime(usam_get_product_meta($this->id, 'balance_update')), 'd.m.Y H:i' ); ?>
			</div>
		</div>
	<?php } ?>		
</div>			
<div class="usam_table_container" v-if="!data.not_limited">
	<table class = "widefat product_table storage_table">
		<thead>
			<tr>
				<td><?php _e( 'Склад', 'usam'); ?></td>
				<td class="storage_table__number"><?php _e( 'Остаток', 'usam'); ?></td>	
				<td class="storage_table__number"><?php _e( 'Резерв', 'usam'); ?></td>	
				<td class="storage_table__number"><?php _e( 'Доступно', 'usam'); ?></td>	
			</tr>
		</thead>
		<tbody>
		<?php						
		$storages = usam_get_storages(['cache_results' => true]);					
		$total_reserve = 0;
		foreach ( $storages as $storage )
		{						
			$stock = usam_get_stock_in_storage($storage->id, $this->id);						
			if ( $stock == USAM_UNLIMITED_STOCK )
				$stock = 0;								
			$reserve = usam_get_reserve_in_storage($storage->id, $this->id);
			$total_reserve += $reserve;
			?>
			<tr>
				<td>
					<a class="row_name <?php echo ($storage->shipping?'row_important':''); ?>" href='<?php echo admin_url("admin.php?page=storage&tab=storage&form=edit&form_name=storage&id=".$storage->id); ?>'><?php echo htmlspecialchars($storage->title); ?></a>
					<div><?php echo htmlspecialchars(usam_get_storage_metadata($storage->id, 'address')); ?></div>
				</td>							
				<td class="storage_table__number">
					<?php	
					if ( !get_option("usam_inventory_control") && $this->product_type !== 'variable' )
					{
						?><input type='text' id='storage_<?php echo $storage->id; ?>' name='<?php echo 'storage_'.$storage->id; ?>[<?php echo $this->id; ?>]' value='<?php echo $stock; ?>'><?php	
					}
					elseif ( $stock !== 0 )
						echo $stock; 
					?>
				</td>
				<td class="storage_table__number"><?php echo $reserve>0?"<a href='".admin_url("admin.php?page=storage&tab=warehouse_documents&table=shipping_documents&s=".usam_get_product_meta($this->id, 'sku') )."'><span class='item_status item_status_valid'>$reserve</span></a>":""; ?></td>
				<td class="storage_table__number"><?php echo $stock==='' || ($stock-$reserve) === 0 ?'':$stock-$reserve; ?></td>
			</tr>
			<?php			
		}				
		?>
		</tbody>
		<tfoot>		
			<?php if ( $total_stock != USAM_UNLIMITED_STOCK ) { ?>						
				<tr>
					<td></td>
					<td><?php echo $total_stock===''?'':$total_reserve+$total_stock; ?></td>
					<td class="table_cel_right"><?php echo $total_reserve; ?></td>
					<td class="table_cel_right"><?php echo $total_stock; ?></td>
				</tr>							
			<?php } ?>	
		</tfoot>
	</table>
</div>	