<?php
/*
Шаблон вывода свойств, для просмотра. Используется, например, для вывода данных профиля в личном кабинете.
*/ 
if( usam_has_multi_adding() && usam_product_has_stock() )
{
	?>		
	<div class="usam_quantity">
		<span class="usam_quantity__minus js_button_minus" data-title = "<?php _e('Уменьшить количество', 'usam'); ?>">-</span>
		<?php usam_field_product_number(); ?>
		<span class="usam_quantity__plus js_button_plus" data-title = "<?php _e('Увеличить количество', 'usam'); ?>">+</span>
	</div>
	<?php usam_selection_product_units(); ?>
<?php } ?>