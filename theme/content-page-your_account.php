<?php
/*
Описание: Шаблон личного кабинета пользователя
*/ 

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}	
?>		
<div class="profile">
	<div class="profile__menu sidebar">
		<div class="widget">
			<?php		
				$tabs = usam_get_menu_your_account();
				$account_current = usam_your_account_current_tab();	
				$vue = false;
				foreach ( $tabs as $menu )
				{
					if ( $menu['slug']==$account_current['tab'] && !empty($menu['vue']) )
						$vue = true;
					?><a class="profile__menu_item <?php echo $menu['slug']==$account_current['tab']?'active':''; ?>" href="<?php echo usam_get_user_account_url( $menu['slug'] ); ?>"><?php echo $menu['title']; ?></a><?php	
				}
			?>	
		</div>
	</div>
	<div id="<?php echo $account_current['tab']; ?>" class="profile_content profile_<?php echo $account_current['tab']; ?>" <?php echo $vue?'v-cloak':''; ?>>
		<?php usam_include_template_file( $account_current['tab'], 'your-account' ); ?>
	</div>
</div>
<?php
add_action('wp_footer', function() 
{ 
	usam_include_template_file( 'paginated-list', 'vue-templates' ); 
});
?>