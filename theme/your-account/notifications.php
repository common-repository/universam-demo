<?php
// Описание: Выводит подробные сведения уведомления
?>	
<div class = 'profile__title'>				
	<div class = 'profile__title_actions'>
		<h1 class="title"><?php _e('Уведомления', 'usam'); ?></h1>
	</div>
</div>	
<?php usam_include_template_file( 'notifications_lists', 'lists' ); ?>
<?php usam_include_template_file('loading', 'template-parts'); ?>