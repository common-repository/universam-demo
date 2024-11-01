<?php
// Описание: Авторизация перед покупкой
?>
<div id="login" class="modal fade">
	<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
	<div class="modal-body modal-scroll">
		<?php
		if (function_exists('user_management')) 
			user_management(['register_form' => 'login', 'show_title' => false]);
		?>
	</div>
</div>