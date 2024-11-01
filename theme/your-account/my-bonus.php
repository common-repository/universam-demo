<?php 
require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
$user_id = get_current_user_id();	
$bonus_account = usam_get_bonuses(['user_id' => $user_id, 'orderby' => 'date', 'order' => 'DESC']); 
$rules = get_option('usam_bonus_rules', ['percent' => 0] );
?>
<div class = 'profile__title'>
	<h1 class="title"><?php _e( 'Мои бонусы', 'usam'); ?></h1>	
</div>
<div class = 'bonuses_blocks'>
	<div class = 'bonuses_blocks__block available_bonuses'>
		<div class='available_bonuses__text'><?php _e('Накопленные бонусы','usam'); ?></div>
		<div class="available_bonuses__sum"><?php echo usam_get_available_user_bonuses(); ?></div>				
	</div>	
	<div class = 'bonuses_blocks__block available_bonuses'>
		<div class='available_bonuses__text'><?php printf( __('Баллы списываются в %s от стоимости заказа.','usam'), $rules['percent'].'%'); ?></div>
		<div class="available_bonuses__sum">1 балл = 1 рубль.</div>				
	</div>
</div>
<div class = "profile_table_wrapper">
	<table class = "profile_table bonus_table">
		<thead>
			<tr>
				<th class="column_date"><?php _e('Дата', 'usam'); ?></th>
				<th class="column_bonus"><?php _e('Бонусы', 'usam'); ?></th>	
				<th class="column_description"><?php _e('Описание', 'usam'); ?></th>														
			</tr>
		</thead>
		<tbody>
		<?php 
		foreach ( $bonus_account as $bonus )
		{ 
			if ( $bonus->type_transaction == 0 )
				$class = 'item_status_valid';
			elseif ( $bonus->type_transaction == 1 )
				$class = 'item_status_attention';
			elseif ( $bonus->type_transaction == 2 )
				$class = 'status_black';
			?>
			<tr>
				<td class="column_date"><?php echo usam_local_date( $bonus->date_insert, get_option( 'date_format', 'Y/m/d' ) ); ?></td>				
				<td class="column_bonus"><span class="item_status <?php echo $class; ?>"><?php echo ($bonus->type_transaction == 1 ? '-' : '+').$bonus->sum; ?></span></td>
				<td class="column_description"><?php echo $bonus->description; ?></td>
			</tr>	
		<?php						
		} ?>
		</tbody>		
	</table>
</div>
