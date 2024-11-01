<?php
// Описание: Прайс-лист
?>
<div class = 'profile__title'>
	<h1 class="title"><?php _e( 'Прайс-лист', 'usam'); ?></h1>	
</div>
<div class="edit_form price_list">		
<?php
$user_id = get_current_user_id();
$user = get_userdata( $user_id );			
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
$rules = usam_get_exchange_rules(['type' => 'pricelist', 'schedule' => 1]);		
foreach( $rules as $rule )	
{			
	?>
	<div class="edit_form__row">
		<div class ="edit_form__item">
			<div class ="edit_form__name"><label><?php echo $rule->name; ?>:</label></div>
			<div class ="edit_form__option">
				<a href="<?php echo usam_get_link_pricelist( $rule->id ); ?>" download target="_blank"><?php _e('Скачать','usam'); ?></a>
			</div>
		</div>
	</div>
	<?php
}
?>
</div>
