<?php
// Описание: Шаблон если список пустой
?>
<div class="empty_page" v-if="items.length==0 && loaded && !request">
	<div class="empty_page__icon"><?php usam_svg_icon('empty') ?></div>
	<div class="empty_page__title"><?php _e('Ничего не найдено', 'usam'); ?></div>	
</div>