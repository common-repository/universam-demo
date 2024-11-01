<?php 
// Name: Вывод плитки баннеров
if ( !empty($block['options']['ids']) )
{
	?>
	<div class="grid_columns grid_columns_<?php echo $block['options']['columns']; ?>">
		<?php usam_theme_banners(['ids' => $block['options']['ids']]); ?>
	</div>	
	<?php
}	