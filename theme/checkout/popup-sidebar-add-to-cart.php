<?php 
// Описание: Шаблон окна добавления в корзину
?>
<div id="sidebar_addtocart" class = "usam_sidebar usam_sidebar_right" :class="{'show_menu':show}" v-if="basket!==null">
	<div class = "usam_sidebar__header">
		<h4><?php _e('Корзина', 'usam'); ?></h4>
		<div class="usam_sidebar__close" @click='show=0'>×</div>
	</div>
	<div class = "usam_sidebar__content">
		<?php include( usam_get_template_file_path( 'widget-basket' ) ); ?>
	</div>
</div>
<?php 