<div id="quick_order_payment" class="modal fade modal-large">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Оплатить заказ','usam'); ?></div>
	</div>
	<div class="modal-body">					
		<form class='usam_checkout_forms' action='<?php echo usam_get_url_system_page('pay_order') ?>' method='get'>	
			<div class ="view_form">			
				<div class ="view_form__row">	
					<div class ="view_form__name"><?php _e( 'Номер заказа', 'usam'); ?></div>	
					<div class ="view_form__option"><input class="option-input" type='text' name="id" /></div>	
				</div>				
			</div>			
			<div class="modal__buttons">	
				<button type="submit" class="button main-button"><?php _e( 'Оплатить', 'usam'); ?></button>
			</div>	
		</form>
	</div>	
</div>
<?php
