<?php
// Описание: Заказы
?>
<div v-if="tab=='list'">		
	<div class = 'profile__title'>				
		<h1 class="title"><?php _e('Заказы', 'usam'); ?></h1>
	</div>
	<?php 
	usam_include_template_file( "orders_interface_filters.class", 'interface-filters' );
	$interface_filters = new Orders_Interface_Filters();	
	$interface_filters->display(); 
	?>
	<?php usam_include_template_file( 'shippeds_lists', 'lists' ); ?>
</div>
<div class = 'order_details' v-else-if="tab=='order' && loaded">
	<?php usam_include_template_file('seller-order', 'your-account'); ?>
</div>
<?php usam_include_template_file('loading', 'template-parts'); ?>	