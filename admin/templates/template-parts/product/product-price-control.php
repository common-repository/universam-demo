<?php
$prefix = "pmeta[$this->id][product_metadata]";
$product_bonuses = usam_get_product_property( $this->id, 'bonuses' );		

$discounts = [];
$discounts_rule = usam_get_discount_rules(['fields' => 'id=>data', 'discount_products' => $this->id, 'add_fields' => ['type_price']]);
foreach ( $discounts_rule as $discount )
{
	$discounts[$discount->type_price][] = $discount;
}
//	$fix_price_rule = usam_get_discount_rules(['type_rule' => 'fix_price']);

$option = get_site_option('usam_underprice_rules');
$underprice_rules = maybe_unserialize( $option );			
if ( $this->product_type == 'variable' ) 
{
	?>			
	<p><?php _e('Чтобы изменить цены, используйте.', 'usam'); ?> &laquo;<a @click="sectionTab='variations'"><?php _e('Управление вариациями.', 'usam'); ?></a>&raquo;</p>
	<?php		
}		
$prices = usam_get_prices(['type' => 'all', 'orderby' => ['type' => 'R', 'sort' => 'ASC']]);	
$available = false;
foreach ( $prices as $key => $type_price )
{		
	if ( !empty($type_price['available']) )
	{
		$available = true;
		break;
	}
}
if ( !$available )
{
	?><div class="usam_message message_error"><p><?php _e( 'Нет доступных цен для отображения клиентам, сделайте доступным хотя бы одну цену', 'usam'); ?></p></div><?php
}		
?>			
<div class="usam_table_container">
<table class = "widefat product_table product_prices">
	<thead>
	<tr>
		<td class="column-price_name"><?php _e( 'Название цены', 'usam'); ?></td>
		<?php if ( $this->product_type != 'variable' ) { ?>
		<td><a href='<?php echo admin_url('admin.php?page=manage_prices&tab=underprice'); ?>' target='_blank' title="<?php _e( 'Открыть список наценок', 'usam'); ?>"><?php _e( 'Наценка для товара', 'usam'); ?></a></td>
		<?php } ?>
		<td><?php _e( 'Цена', 'usam'); ?></td>
		<?php if ( $this->product_type != 'variable' ) { ?>
		<td><?php _e( 'Скидки и акции', 'usam'); ?></td>	
		<?php } ?>				
		<td><?php _e( 'Бонусы', 'usam'); ?></td>				
	</tr>
	</thead>
	<tbody>
	<?php
	$product_id = usam_get_post_id_main_site( $this->id );
	foreach ( $prices as $key => $type_price )
	{
		$price_key = "price_".$type_price['code'];
		$old_price_key = "old_price_".$type_price['code'];
		$price = (float)usam_get_product_metaprice( $product_id, $price_key );
		$old_price = (float)usam_get_product_metaprice( $product_id, $old_price_key );
		?>
		<tr>
			<td class="column-price_name"><a href='<?php echo admin_url('admin.php?page=shop_settings&tab=directories&view=settings&table=prices&form=edit&form_name=price&id='.$type_price['id']); ?>' target='_blank' title="<?php _e( 'Открыть список цен', 'usam'); ?>" class="row_name <?php echo $type_price['type']=='P'?'row_important':''; ?>"><?php echo $type_price['title'].' '.usam_get_currency_sign($type_price['currency']); ?></a><?php
			if ( !empty($type_price['available']) )
			{
				?>&nbsp;<span class='item_status_valid item_status'><?php _e( 'Доступна на сайте', 'usam'); ?></span><?php 
			}					
			if ( !empty($type_price['base_type']) )
			{
				?><div class='base_type'><?php printf(__( 'Рассчитывается на основе %s', 'usam'), usam_get_name_price_by_code($type_price['base_type']) ); ?></div><?php 
			}
			elseif ( $type_price['type']=='P' )
			{
				?><div class='base_type'><?php _e( 'Закупочная', 'usam'); ?></div><?php
			}
			?>
			</td>
			<?php if ( $this->product_type != 'variable' ) { ?>
			<td><?php 
				if ( $underprice_rules ) 
				{
					$underprice = (int)usam_get_product_metaprice( $product_id, "underprice_".$type_price['code'] ); ?>
					<select name="prices[<?php echo $this->id; ?>][<?php echo "underprice_".$type_price['code']; ?>]">
						<option value="0" <?php selected( 0, $underprice ); ?>><?php _e( 'Не установлено', 'usam'); ?></option>
						<?php 							
						foreach ( $underprice_rules as $rule )
						{
							if ( empty($rule['category']) && empty($rule['brands']) && empty($rule['category_sale']) && empty($rule['catalogs']))
								$disabled = false;
							else
								$disabled = true;
							?><option value="<?php echo $rule['id']; ?>" <?php selected($rule['id'], $underprice); ?> <?php disabled($disabled); ?>><?php echo $rule['title']." (".$rule['value']."%)"; ?></option><?php											
						}
						?>
					</select>
					<?php 
				}
				else
				{
					?><input type="hidden" name="prices[<?php echo $this->id; ?>][<?php echo "underprice_".$type_price['code']; ?>]" value="0"/><?php 
				}
				if ( !$underprice && !empty($type_price['underprice']) ) 
				{
					echo "<div>".__('Наценка в типе цены', 'usam')." ".$type_price['underprice']."%</div>";
				}

			?></td>
			<?php } ?>
			<td>
			<?php  
			if ( $this->product_type == 'variable' ) 
			{
				?><span><?php echo _e( 'Цена от', 'usam')." ".number_format($price, 2, '.', ''); ?></span><?php
			}
			elseif ( $type_price['type'] == 'P' ) 
			{
				?>
				<input type='text' size='12' class="js-price-<?php echo $type_price['code']; ?>" name='prices[<?php echo $this->id; ?>][<?php echo $price_key; ?>]' <?php disabled( $old_price>0 ); ?> value='<?php echo $price==0?'':number_format($price, 2, '.', ''); ?>' placeholder='0.00'/>
				<?php
			}
			elseif ( $type_price['base_type'] ) 
			{
				echo '<div>'.number_format($price, 2, '.', ' ' ).'</div>';
				$discont = $old_price == 0 ? 0 : round(100 - $price*100/$old_price, 1);									
				if ( $discont )
				{
					echo '<span class="discont">'.$discont.'%</span>';
					echo '<span class="old_price">'.usam_get_formatted_price($old_price, ['type_price' => $type_price['code']]).'</span>';
				}
			}
			else
			{
				?><input type='text' size='12' class="js-price-<?php echo $type_price['code']; ?>"  name='prices[<?php echo $this->id; ?>][<?php echo $price_key; ?>]' <?php disabled( $old_price > 0 ); ?> value='<?php echo $price==0?'':number_format($price, 2, '.', ''); ?>' placeholder='0.00'/><?php
				if ( $type_price['type'] == 'R' && $old_price > 0 )
				{
					$discont = $old_price == 0 ? 0 : round(100 - $price*100/$old_price, 1);	
					echo '<span class="discont">'.$discont.'%</span>';
					echo '<span class="old_price">'.usam_get_formatted_price($old_price, ['type_price' => $type_price['code']]).'</span>';
				}
			}
			?></td>
			<?php if ( $this->product_type != 'variable' ) { ?>
			<td>
			<?php
			if ( $type_price['type'] == 'R' )
			{
?>
<div class='product_discounts'>
<?php	
$product = usam_get_active_products_day_by_codeprice( $type_price['code'], $this->id );			
if ( !empty($product) )
{
	$rule = usam_get_data($product->rule_id, 'usam_product_day_rules');
	?>
	<strong><?php _e( 'Товар дня', 'usam'); ?></strong>
	<div class = 'product_discounts__rules'>
		<?php 							
		switch ( $product->dtype ) 
		{
			case 'p' :																									
				$value = $product->discount."%";
			break;
			case 'f' :
				$value = usam_get_formatted_price($product->discount, ['type_price' => $type_price['code']]);
			break;
			case 't' :
				$value = __('точная цена:').' '. usam_get_formatted_price($product->discount, ['type_price' => $type_price['code']]);
			break;
		}
		$class = $rule['active']?'item_status_valid':'status_flagged';				
		?>								
		<a href="<?php echo add_query_arg( array('id' => $rule['id'], 'page' => 'manage_discounts', 'tab' => 'product_day', 'form' => 'edit', 'form_name' => 'product_day'),admin_url('admin.php') ) ?>" target="_blank"><span class="<?php echo $class; ?> item_status"><?php echo $rule['name']. ' '.$value; ?></span></a>
	</div>
	<?php 
}
else
{
	if ( !empty($discounts[$type_price['code']]) ) 
	{			
		?>
		<strong><?php _e( 'Установленные скидки', 'usam'); ?></strong>
		<div class = 'product_discounts__rules'>	
		<?php 								
		foreach( $discounts[$type_price['code']] as $discount )
		{					
			$class = $discount->active?'item_status_valid':'status_flagged';
			?><a href="<?php echo add_query_arg(['id' => $discount->id, 'page' => 'manage_discounts', 'tab' => 'discount', 'form' => 'edit', 'form_name' => $discount->type_rule.'_discount'], admin_url('admin.php')) ?>" target="_blank"><span class="<?php echo $class; ?> item_status"><?php echo usam_get_discount_rule_name($discount, $type_price['code']); ?></span></a><?php 
		}											
		?>	
		</div>
		<?php 
	} 
	else
	{
		?><a href='<?php echo admin_url('admin.php?page=manage_discounts&tab=discount&view=table&table=discount&form=edit&form_name=fix_price_discount'); ?>' target='_blank'><?php _e('Добавить скидку', 'usam'); ?></a><?php 
	}
}
?>			
</div>	
<?php 
}	
		?></td>
		<?php } ?>
		<td><?php 
		if ( $this->product_type !== 'variable' && $type_price['type'] == 'R' ) 
		{					
			$bonuses = isset($product_bonuses[$type_price['code']])? $product_bonuses[$type_price['code']] : array('value' => 0, 'type' => 'p');
			?>
			<input style="width:100px;" id='form_bonuses_<?php echo $type_price['code']; ?>' name="<?php echo $prefix; ?>[bonuses][<?php echo $type_price['code']; ?>][value]" size="5" type="text" value="<?php echo $bonuses['value']?$bonuses['value']:''; ?>" placeholder='0'/>
			<select style="width:100px;" class="select_bonuses" name="<?php echo $prefix; ?>[bonuses][<?php echo $type_price['code']; ?>][type]">
				<option value="p" <?php echo ($bonuses['type'] == 'p'?'selected':''); ?>><?php echo esc_html__('В процентах', 'usam'); ?></option>
				<option value="f"<?php echo ($bonuses['type'] == 'f'?'selected':''); ?>><?php echo esc_html__('Фиксированные', 'usam'); ?></option>
			</select>
			<?php 
		}
		?></td>	
		</tr>			
	<?php }	?>
	</tbody>
</table>
</div>