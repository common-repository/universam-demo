<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
?>
<hr class="usam_update_warning__separator" />
<div class="usam_update_warning">
	<div class="usam_update_warning__icon">
		<span class="dashicons dashicons-info-outline"></span>
	</div>
	<div class="usam_update_warning__message">
		<strong>
			<?php esc_html_e( 'Внимание, сделайте резервную копию перед обновлением!', 'usam' ); ?>
		</strong> -
		<?php printf( esc_html__( 'Последнее обновление включает в себя некоторые существенные изменения в различных областях плагина. Мы настоятельно рекомендуем сделать резервную копию вашего сайта перед обновлением и для начала попробовать обновить на тестовом сайте. Так же если вы используете официальную тему, обязательно её обновите.', 'usam' )); ?>
	</div>
</div>
