<?php
// Описание: Товары

?>
<div v-if="tab=='list'">		
	<div class = 'profile__title'>				
		<div class = 'profile__title_actions'>
			<h1 class="title"><?php _e('Товары и услуги', 'usam'); ?></h1>
			<div class = 'profile__title_buttons'>
				<button class="button profile__title_button" @click="tab='product'"><?php _e('Добавить товар или услугу','usam'); ?></button>
			</div>
		</div>
	</div>
	<?php 
	usam_include_template_file( "products_interface_filters.class", 'interface-filters' );
	$interface_filters = new Products_Interface_Filters();	
	$interface_filters->display(); 
	?>
	<?php usam_include_template_file( 'products_lists', 'lists' ); ?>
</div>
<div class = '' v-else-if="loaded">
	<div class = 'profile__title'>		
		<button @click="tab='list'" class="button go_back"><?php usam_svg_icon("angle-down-solid")?><span class="go_back_name"><?php _e('Назад', 'usam'); ?></span></button>
		<h1 class="title"><?php _e('Добавить товар или услугу', 'usam'); ?></h1>
	</div>
	<?php usam_include_template_file('add_product', 'template-parts'); ?>
</div>
<?php usam_include_template_file('loading', 'template-parts'); ?>
